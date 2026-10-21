<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index()
    {
        return view('transfers.index', [
            'transfers' => StockTransfer::with(['from', 'to', 'user', 'items.item.unit'])->latest('date')->latest('id')->paginate(10),
        ]);
    }

    public function create()
    {
        $stockMap = Stock::where('quantity', '>', 0)->get()->groupBy('item_id')
            ->map(fn ($g) => $g->pluck('quantity', 'warehouse_id'));

        return view('transfers.form', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'itemOptions' => Item::active()->with('unit')->orderBy('name')->get()
                ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit->symbol])->values(),
            'stockMap' => $stockMap,
            'lines' => array_values(old('items') ?? [['item_id' => '', 'quantity' => 1]]),
        ]);
    }

    public function store(Request $request, StockService $stock)
    {
        $data = $request->validate([
            'date' => 'required|date',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id|distinct',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'to_warehouse_id.different' => 'Gudang asal dan tujuan harus berbeda.',
            'items.required' => 'Tambahkan minimal satu barang.',
        ]);

        try {
            $transfer = DB::transaction(function () use ($data, $stock) {
                $transfer = StockTransfer::create([
                    'number' => DocumentNumber::next('MT', 'stock_transfers'),
                    'date' => $data['date'],
                    'from_warehouse_id' => $data['from_warehouse_id'],
                    'to_warehouse_id' => $data['to_warehouse_id'],
                    'user_id' => auth()->id(),
                    'notes' => $data['notes'] ?? null,
                ]);

                foreach ($data['items'] as $line) {
                    $transfer->items()->create(['item_id' => $line['item_id'], 'quantity' => $line['quantity']]);
                    $stock->transfer((int) $line['item_id'], (int) $data['from_warehouse_id'], (int) $data['to_warehouse_id'],
                        (float) $line['quantity'], $transfer->id, $transfer->number, $data['notes'] ?? null);
                }
                ActivityLog::record('Mutasi', "Mutasi antargudang {$transfer->number}");

                return $transfer;
            });
        } catch (InsufficientStockException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('transfers.index')->with('success', "Mutasi {$transfer->number} dikonfirmasi. Total stok perusahaan tidak berubah.");
    }
}
