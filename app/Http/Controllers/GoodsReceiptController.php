<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\DocumentNumber;
use App\Support\QuickMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = GoodsReceipt::with(['purchaseOrder.supplier', 'warehouse', 'receiver'])->withCount('items');

        if ($s = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('number', 'like', "%{$s}%")
                ->orWhere('delivery_note', 'like', "%{$s}%")
                ->orWhereHas('purchaseOrder', fn ($x) => $x->where('number', 'like', "%{$s}%")));
        }

        return view('receipts.index', ['receipts' => $query->latest('date')->latest('id')->paginate(10)->withQueryString()]);
    }

    public function show(GoodsReceipt $receipt)
    {
        $receipt->load(['purchaseOrder.supplier', 'warehouse', 'receiver', 'items.item.unit']);

        return view('receipts.show', compact('receipt'));
    }

    public function create(PurchaseOrder $purchase)
    {
        if (!$purchase->canReceive()) {
            return redirect()->route('purchases.show', $purchase)->with('error', 'Pembelian ini belum disetujui atau sudah selesai diterima.');
        }

        return view('receipts.create', [
            'po' => $purchase->load(['supplier', 'items.item.unit']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, PurchaseOrder $purchase, StockService $stock)
    {
        if (!$purchase->canReceive()) {
            return back()->with('error', 'Pembelian ini tidak dapat menerima barang.');
        }

        $data = $request->validate([
            'date' => 'required|date',
            'warehouse_name' => 'required|string|max:150',
            'delivery_note' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'lines' => 'required|array',
            'lines.*' => 'nullable|numeric|min:0',
        ], ['lines.required' => 'Isi jumlah yang diterima.']);

        $purchase->load('items.item');

        // Validasi per baris terhadap sisa yang belum diterima.
        $received = [];
        foreach ($purchase->items as $line) {
            $qty = round((float) ($data['lines'][$line->id] ?? 0), 2);
            if ($qty <= 0) {
                continue;
            }
            if ($qty > $line->outstanding() + 0.0001) {
                return back()->withInput()->with('error', "Jumlah {$line->item->name} melebihi sisa pesanan ({$line->outstanding()}).");
            }
            $received[$line->id] = $qty;
        }
        if (!$received) {
            return back()->withInput()->with('error', 'Isi minimal satu jumlah penerimaan lebih dari nol.');
        }

        $receipt = DB::transaction(function () use ($data, $purchase, $received, $stock) {
            // Gudang yang belum ada dibuat otomatis (hanya role dengan izin quick.warehouse).
            $warehouseId = app(QuickMaster::class)->warehouse($data['warehouse_name'], 'warehouse_name')->id;

            $receipt = GoodsReceipt::create([
                'number' => DocumentNumber::next('PN', 'goods_receipts'),
                'date' => $data['date'],
                'purchase_order_id' => $purchase->id,
                'warehouse_id' => $warehouseId,
                'received_by' => auth()->id(),
                'delivery_note' => $data['delivery_note'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($purchase->items as $line) {
                if (!isset($received[$line->id])) {
                    continue;
                }
                $qty = $received[$line->id];
                $receipt->items()->create(['item_id' => $line->item_id, 'quantity' => $qty]);
                $line->increment('received_qty', $qty);
                $stock->increase($line->item_id, $warehouseId, $qty, 'purchase_receipt', $receipt->id, $receipt->number, "Penerimaan {$purchase->number}");
            }

            $purchase->load('items');
            $done = $purchase->items->every(fn ($l) => $l->fresh()->outstanding() <= 0);
            $purchase->update(['status' => $done ? PurchaseOrder::RECEIVED : PurchaseOrder::PARTIAL]);

            ActivityLog::record('Penerimaan', "Memverifikasi penerimaan {$receipt->number} untuk {$purchase->number}");

            return $receipt;
        });

        return redirect()->route('receipts.show', $receipt)->with('success', 'Penerimaan tercatat dan stok gudang bertambah.');
    }
}
