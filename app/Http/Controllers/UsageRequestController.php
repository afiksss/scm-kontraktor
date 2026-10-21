<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Project;
use App\Models\Stock;
use App\Models\UsageRequest;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\DocumentNumber;
use App\Support\QuickMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UsageRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = UsageRequest::with(['project', 'requester', 'warehouse'])->withCount('items');

        if ($s = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('number', 'like', "%{$s}%")
                ->orWhere('purpose', 'like', "%{$s}%")
                ->orWhereHas('requester', fn ($x) => $x->where('name', 'like', "%{$s}%")));
        }
        foreach (['status', 'project_id'] as $f) {
            if ($request->filled($f)) {
                $query->where($f, $request->query($f));
            }
        }

        return view('usage.index', [
            'requests' => $query->latest('date')->latest('id')->paginate(10)->withQueryString(),
            'projects' => Project::orderBy('name')->get(),
            'counts' => UsageRequest::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function create()
    {
        $stockMap = Stock::where('quantity', '>', 0)->get()->groupBy('item_id')
            ->map(fn ($g) => $g->pluck('quantity', 'warehouse_id'));

        return view('usage.form', [
            'projects' => Project::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'itemOptions' => Item::active()->with('unit')->orderBy('name')->get()
                ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit->symbol])->values(),
            'stockMap' => $stockMap,
            'lines' => array_values(old('items') ?? [['item_id' => '', 'quantity' => 1]]),
            'nextNumber' => DocumentNumber::next('PR', 'usage_requests'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'project_name' => 'required|string|max:200',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purpose' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id|distinct',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'items.required' => 'Tambahkan minimal satu barang.',
            'items.*.item_id.distinct' => 'Barang yang sama tidak boleh dipilih dua kali.',
            'items.*.item_id.required' => 'Pilih barang pada setiap baris.',
        ]);

        $req = DB::transaction(function () use ($data) {
            // Proyek yang belum ada dibuat otomatis (hanya role dengan izin quick.project).
            $projectId = app(QuickMaster::class)->project($data['project_name'], 'project_name')->id;

            $req = UsageRequest::create([
                'number' => DocumentNumber::next('PR', 'usage_requests'),
                'date' => $data['date'],
                'project_id' => $projectId,
                'warehouse_id' => $data['warehouse_id'],
                'requester_id' => auth()->id(),
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => UsageRequest::PENDING,
            ]);
            foreach ($data['items'] as $line) {
                $req->items()->create(['item_id' => $line['item_id'], 'qty_requested' => $line['quantity']]);
            }
            ActivityLog::record('Pemakaian', "Mengajukan permintaan barang {$req->number}");

            return $req;
        });

        return redirect()->route('usage.show', $req)->with('success', "Permintaan {$req->number} diajukan dan menunggu persetujuan.");
    }

    public function show(UsageRequest $usage)
    {
        $usage->load(['project', 'warehouse', 'requester', 'approver', 'releaser', 'items.item.unit']);

        $available = Stock::where('warehouse_id', $usage->warehouse_id)
            ->whereIn('item_id', $usage->items->pluck('item_id'))->pluck('quantity', 'item_id');

        return view('usage.show', ['req' => $usage, 'available' => $available]);
    }

    public function printout(UsageRequest $usage)
    {
        $usage->load(['project', 'warehouse', 'requester', 'approver', 'releaser', 'items.item.unit']);

        return view('usage.print', ['req' => $usage]);
    }

    public function approve(Request $request, UsageRequest $usage)
    {
        if ($usage->status !== UsageRequest::PENDING) {
            return back()->with('error', 'Permintaan ini tidak sedang menunggu persetujuan.');
        }

        $data = $request->validate(['approved' => 'required|array', 'approved.*' => 'nullable|numeric|min:0']);
        $usage->load('items.item');
        $total = 0;

        foreach ($usage->items as $line) {
            $qty = round((float) ($data['approved'][$line->id] ?? 0), 2);
            if ($qty > $line->qty_requested + 0.0001) {
                return back()->with('error', "Jumlah disetujui {$line->item->name} melebihi jumlah yang diminta.");
            }
            $total += $qty;
        }
        if ($total <= 0) {
            return back()->with('error', 'Tidak ada jumlah yang disetujui. Gunakan tombol Tolak bila permintaan tidak disetujui.');
        }

        DB::transaction(function () use ($usage, $data) {
            foreach ($usage->items as $line) {
                $line->update(['qty_approved' => round((float) ($data['approved'][$line->id] ?? 0), 2)]);
            }
            $usage->update(['status' => UsageRequest::APPROVED, 'approved_by' => auth()->id(), 'approved_at' => now()]);
            ActivityLog::record('Pemakaian', "Menyetujui permintaan {$usage->number}");
        });

        return back()->with('success', 'Permintaan disetujui dan diteruskan ke gudang.');
    }

    public function reject(Request $request, UsageRequest $usage)
    {
        $data = $request->validate(['rejection_reason' => 'required|string|max:255']);
        if ($usage->status !== UsageRequest::PENDING) {
            return back()->with('error', 'Permintaan ini tidak sedang menunggu persetujuan.');
        }
        $usage->update(['status' => UsageRequest::REJECTED, 'rejection_reason' => $data['rejection_reason'],
            'approved_by' => auth()->id(), 'approved_at' => now()]);
        ActivityLog::record('Pemakaian', "Menolak permintaan {$usage->number}");

        return back()->with('success', 'Permintaan ditolak.');
    }

    public function process(UsageRequest $usage)
    {
        if ($usage->status !== UsageRequest::APPROVED) {
            return back()->with('error', 'Hanya permintaan berstatus Disetujui yang dapat diproses.');
        }
        $usage->update(['status' => UsageRequest::PROCESSING]);
        ActivityLog::record('Pemakaian', "Memproses permintaan {$usage->number} di gudang");

        return back()->with('success', 'Permintaan diproses gudang. Konfirmasi pengeluaran setelah barang diserahkan.');
    }

    public function release(Request $request, UsageRequest $usage, StockService $stock)
    {
        if (!in_array($usage->status, [UsageRequest::APPROVED, UsageRequest::PROCESSING], true)) {
            return back()->with('error', 'Permintaan ini tidak dapat dikeluarkan.');
        }

        $data = $request->validate(['released' => 'required|array', 'released.*' => 'nullable|numeric|min:0']);
        $usage->load('items.item');

        $plan = [];
        foreach ($usage->items as $line) {
            $qty = round((float) ($data['released'][$line->id] ?? 0), 2);
            if ($qty > $line->qty_approved + 0.0001) {
                return back()->with('error', "Pengeluaran {$line->item->name} melebihi jumlah yang disetujui.");
            }
            $plan[$line->id] = $qty;
        }
        if (array_sum($plan) <= 0) {
            return back()->with('error', 'Isi minimal satu jumlah pengeluaran lebih dari nol.');
        }

        try {
            DB::transaction(function () use ($usage, $plan, $stock) {
                foreach ($usage->items as $line) {
                    $qty = $plan[$line->id];
                    if ($qty > 0) {
                        $stock->decrease($line->item_id, $usage->warehouse_id, $qty, 'usage', $usage->id, $usage->number, "Pemakaian {$usage->project->name}");
                    }
                    $line->update(['qty_released' => $qty]);
                }
                $usage->update(['status' => UsageRequest::DONE, 'released_by' => auth()->id(), 'released_at' => now()]);
                ActivityLog::record('Pemakaian', "Mengonfirmasi pengeluaran barang {$usage->number}");
            });
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengeluaran dikonfirmasi dan stok gudang berkurang.');
    }

    public function cancel(UsageRequest $usage)
    {
        $isOwner = $usage->requester_id === auth()->id();
        if ($usage->status !== UsageRequest::PENDING || !($isOwner || auth()->user()->can('usage.approve'))) {
            return back()->with('error', 'Permintaan tidak dapat dibatalkan.');
        }
        $usage->update(['status' => UsageRequest::CANCELLED]);
        ActivityLog::record('Pemakaian', "Membatalkan permintaan {$usage->number}");

        return back()->with('success', 'Permintaan dibatalkan.');
    }
}
