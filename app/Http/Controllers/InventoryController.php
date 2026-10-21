<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::active()->withTotalStock()->with(['category', 'unit', 'stocks.warehouse']);

        if ($s = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('code', 'like', "%{$s}%")->orWhere('name', 'like', "%{$s}%"));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }
        if ($request->filled('warehouse_id')) {
            $wh = $request->query('warehouse_id');
            $query->whereHas('stocks', fn ($q) => $q->where('warehouse_id', $wh)->where('quantity', '>', 0));
        }
        match ($request->query('stock_status')) {
            'Tersedia' => $query->available(),
            'Stok Menipis' => $query->runningLow(),
            'Stok Habis' => $query->outOfStock(),
            'Kritis' => $query->critical(),
            default => null,
        };

        $value = (float) DB::table('stocks')->join('items', 'items.id', '=', 'stocks.item_id')
            ->sum(DB::raw('stocks.quantity * items.price'));

        return view('inventory.index', [
            'items' => $query->orderBy('name')->paginate(10)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'kpi' => [
                'units' => (float) Stock::sum('quantity'),
                'critical' => Item::active()->critical()->count(),
                'categories' => Category::where('is_active', true)->count(),
                'value' => $value,
            ],
        ]);
    }

    public function show(Request $request, Item $item)
    {
        $item->load(['category', 'unit', 'stocks.warehouse']);
        $total = (float) $item->stocks->sum('quantity');

        $movements = StockMovement::with(['warehouse', 'user'])->where('item_id', $item->id)
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->query('warehouse_id')))
            ->latest('id')->paginate(10)->withQueryString();

        // Total masuk/keluar 30 hari terakhir untuk ringkasan.
        $recent = StockMovement::where('item_id', $item->id)->where('created_at', '>=', now()->subDays(30));

        return view('inventory.show', [
            'item' => $item,
            'total' => $total,
            'status' => Item::stockStatus($total, $item->min_stock),
            'movements' => $movements,
            'in30' => (float) (clone $recent)->sum('qty_in'),
            'out30' => (float) (clone $recent)->sum('qty_out'),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /** Penyesuaian saldo hasil stok opname (fisik vs sistem). */
    public function adjust(Request $request, Item $item, StockService $stock)
    {
        $data = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'new_quantity' => 'required|numeric|min:0',
            'note' => 'required|string|max:200',
        ], ['note.required' => 'Alasan penyesuaian wajib diisi.']);

        $move = $stock->adjust($item->id, (int) $data['warehouse_id'], (float) $data['new_quantity'], 'ADJ-' . now()->format('YmdHis'), $data['note']);

        if (!$move) {
            return back()->with('success', 'Saldo sudah sama dengan hasil hitung; tidak ada perubahan.');
        }
        ActivityLog::record('Inventaris', "Penyesuaian stok {$item->code} ({$item->name}): {$data['note']}");

        return back()->with('success', 'Penyesuaian stok dicatat pada riwayat pergerakan.');
    }
}
