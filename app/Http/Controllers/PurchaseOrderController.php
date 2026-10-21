<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Unit;
use App\Support\DocumentNumber;
use App\Support\QuickMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::with('supplier')->withCount('items');

        if ($s = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('number', 'like', "%{$s}%")
                ->orWhereHas('supplier', fn ($x) => $x->where('name', 'like', "%{$s}%")));
        }
        foreach (['status', 'supplier_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->query($f));
            }
        }
        if ($request->filled('date')) {
            $query->whereDate('date', $request->query('date'));
        }

        return view('purchases.index', [
            'orders' => $query->latest('date')->latest('id')->paginate(10)->withQueryString(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'counts' => PurchaseOrder::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function create()
    {
        return view('purchases.form', $this->formData(null));
    }

    public function store(Request $request)
    {
        $po = $this->persist($request, null);

        return redirect()->route('purchases.show', $po)
            ->with('success', "Pembelian {$po->number} berhasil " . ($po->status === PurchaseOrder::DRAFT ? 'disimpan sebagai draft.' : 'diajukan untuk persetujuan.'));
    }

    public function show(PurchaseOrder $purchase)
    {
        $purchase->load(['supplier', 'items.item.unit', 'receipts.warehouse', 'receipts.receiver', 'receipts.items.item', 'creator', 'approver']);

        return view('purchases.show', ['po' => $purchase]);
    }

    public function printout(PurchaseOrder $purchase)
    {
        $purchase->load(['supplier', 'items.item.unit', 'creator', 'approver']);

        return view('purchases.print', ['po' => $purchase]);
    }

    public function edit(PurchaseOrder $purchase)
    {
        abort_unless($purchase->isEditable(), 403, 'Hanya pembelian berstatus Draft yang dapat diedit.');

        return view('purchases.form', $this->formData($purchase));
    }

    public function update(Request $request, PurchaseOrder $purchase)
    {
        abort_unless($purchase->isEditable(), 403, 'Hanya pembelian berstatus Draft yang dapat diedit.');
        $po = $this->persist($request, $purchase);

        return redirect()->route('purchases.show', $po)
            ->with('success', "Pembelian {$po->number} berhasil diperbarui.");
    }

    public function submit(PurchaseOrder $purchase)
    {
        if ($purchase->status !== PurchaseOrder::DRAFT) {
            return back()->with('error', 'Hanya draft yang dapat diajukan.');
        }
        $purchase->update(['status' => PurchaseOrder::PENDING]);
        ActivityLog::record('Pembelian', "Mengajukan pembelian {$purchase->number}");

        return back()->with('success', 'Pembelian diajukan untuk persetujuan.');
    }

    public function approve(PurchaseOrder $purchase)
    {
        if ($purchase->status !== PurchaseOrder::PENDING) {
            return back()->with('error', 'Pembelian ini tidak sedang menunggu persetujuan.');
        }
        $purchase->update(['status' => PurchaseOrder::APPROVED, 'approved_by' => auth()->id(), 'approved_at' => now()]);
        ActivityLog::record('Pembelian', "Menyetujui pembelian {$purchase->number}");

        return back()->with('success', 'Pembelian disetujui. Gudang dapat mengonfirmasi penerimaan barang.');
    }

    public function reject(Request $request, PurchaseOrder $purchase)
    {
        $data = $request->validate(['rejection_reason' => 'required|string|max:255']);
        if ($purchase->status !== PurchaseOrder::PENDING) {
            return back()->with('error', 'Pembelian ini tidak sedang menunggu persetujuan.');
        }
        $purchase->update(['status' => PurchaseOrder::REJECTED, 'rejection_reason' => $data['rejection_reason']]);
        ActivityLog::record('Pembelian', "Menolak pembelian {$purchase->number}");

        return back()->with('success', 'Pembelian ditolak.');
    }

    public function cancel(PurchaseOrder $purchase)
    {
        if (!$purchase->canCancel()) {
            return back()->with('error', 'Pembelian tidak dapat dibatalkan (sudah ada penerimaan atau status final).');
        }
        $purchase->update(['status' => PurchaseOrder::CANCELLED]);
        ActivityLog::record('Pembelian', "Membatalkan pembelian {$purchase->number}");

        return back()->with('success', 'Pembelian dibatalkan.');
    }

    private function formData(?PurchaseOrder $po): array
    {
        $po?->load('items.item.unit', 'supplier');

        $lines = old('items') ?? ($po
            ? $po->items->map(fn ($l) => ['name' => $l->item->name, 'unit' => $l->item->unit->symbol, 'quantity' => $l->quantity, 'price' => $l->price])->all()
            : [['name' => '', 'unit' => '', 'quantity' => 1, 'price' => 0]]);

        return [
            'po' => $po,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('symbol')->pluck('symbol'),
            'itemOptions' => Item::active()->with('unit')->orderBy('name')->get()
                ->map(fn ($i) => ['name' => $i->name, 'unit' => $i->unit->symbol, 'price' => $i->price])->values(),
            'lines' => array_values($lines),
            'nextNumber' => $po?->number ?? DocumentNumber::next('PB', 'purchase_orders'),
        ];
    }

    private function persist(Request $request, ?PurchaseOrder $po): PurchaseOrder
    {
        $data = $request->validate([
            'date' => 'required|date',
            'supplier_name' => 'required|string|max:150',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:150',
            'items.*.unit' => 'nullable|string|max:30',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
        ], [
            'items.required' => 'Tambahkan minimal satu barang.',
            'supplier_name.required' => 'Isi nama supplier (pilih dari daftar atau ketik nama baru).',
            'items.*.name.required' => 'Nama barang pada setiap baris wajib diisi.',
        ]);

        $status = $request->input('action') === 'submit' ? PurchaseOrder::PENDING : PurchaseOrder::DRAFT;
        $quick = app(QuickMaster::class);

        return DB::transaction(function () use ($data, $status, $po, $quick) {
            // Supplier & barang yang belum ada dibuat di sini (satu transaksi: gagal => semuanya dibatalkan).
            $supplier = $quick->supplier($data['supplier_name'], 'supplier_name');

            $lines = [];
            $seen = [];
            foreach ($data['items'] as $i => $line) {
                $item = $quick->item($line['name'], $line['unit'] ?? null, (float) $line['price'], "items.$i.name");

                if (isset($seen[$item->id])) {
                    throw ValidationException::withMessages([
                        "items.$i.name" => "Barang \"{$item->name}\" muncul dua kali; gabungkan jumlahnya dalam satu baris.",
                    ]);
                }
                $seen[$item->id] = true;
                $lines[] = ['item_id' => $item->id, 'quantity' => $line['quantity'], 'price' => $line['price']];
            }

            $header = [
                'date' => $data['date'], 'supplier_id' => $supplier->id,
                'notes' => $data['notes'] ?? null, 'status' => $status,
            ];

            if ($po) {
                $po->update($header);
                $po->items()->delete();
            } else {
                $po = PurchaseOrder::create($header + [
                    'number' => DocumentNumber::next('PB', 'purchase_orders'),
                    'created_by' => auth()->id(),
                ]);
            }

            $total = 0;
            foreach ($lines as $line) {
                $po->items()->create($line);
                $total += $line['quantity'] * $line['price'];
            }
            $po->update(['total' => $total]);

            ActivityLog::record('Pembelian', ($po->wasRecentlyCreated ? 'Membuat' : 'Mengubah') . " pembelian {$po->number} ({$status})");

            return $po;
        });
    }
}
