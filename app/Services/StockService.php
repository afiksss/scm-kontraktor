<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya pintu perubahan stok. Setiap perubahan:
 *  - mengunci baris saldo (lockForUpdate) di dalam transaksi database,
 *  - memperbarui saldo per gudang,
 *  - mencatat satu baris di buku besar stock_movements.
 */
class StockService
{
    public function increase(
        int $itemId,
        int $warehouseId,
        float $qty,
        string $refType,
        ?int $refId,
        string $refNumber,
        ?string $note = null,
        string $type = 'in'
    ): StockMovement {
        $this->assertPositive($qty);

        return DB::transaction(function () use ($itemId, $warehouseId, $qty, $refType, $refId, $refNumber, $note, $type) {
            $stock = $this->lockedStock($itemId, $warehouseId);
            $stock->quantity = round($stock->quantity + $qty, 2);
            $stock->save();

            return $this->record($stock, $type, $qty, 0, $refType, $refId, $refNumber, $note);
        });
    }

    public function decrease(
        int $itemId,
        int $warehouseId,
        float $qty,
        string $refType,
        ?int $refId,
        string $refNumber,
        ?string $note = null,
        string $type = 'out'
    ): StockMovement {
        $this->assertPositive($qty);

        return DB::transaction(function () use ($itemId, $warehouseId, $qty, $refType, $refId, $refNumber, $note, $type) {
            $stock = $this->lockedStock($itemId, $warehouseId);

            if ($stock->quantity + 0.0001 < $qty) {
                $item = Item::with('unit')->find($itemId);
                $wh = Warehouse::find($warehouseId);
                throw new InsufficientStockException(sprintf(
                    'Stok %s di %s tidak cukup (tersedia %s %s, dibutuhkan %s).',
                    $item?->name, $wh?->name, $this->num($stock->quantity), $item?->unit?->symbol, $this->num($qty)
                ));
            }

            $stock->quantity = round($stock->quantity - $qty, 2);
            $stock->save();

            return $this->record($stock, $type, 0, $qty, $refType, $refId, $refNumber, $note);
        });
    }

    /** Pindahkan stok antargudang; total stok perusahaan tidak berubah. */
    public function transfer(
        int $itemId,
        int $fromWarehouseId,
        int $toWarehouseId,
        float $qty,
        ?int $refId,
        string $refNumber,
        ?string $note = null
    ): void {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new \InvalidArgumentException('Gudang asal dan tujuan harus berbeda.');
        }

        DB::transaction(function () use ($itemId, $fromWarehouseId, $toWarehouseId, $qty, $refId, $refNumber, $note) {
            $this->decrease($itemId, $fromWarehouseId, $qty, 'transfer', $refId, $refNumber, $note, 'transfer_out');
            $this->increase($itemId, $toWarehouseId, $qty, 'transfer', $refId, $refNumber, $note, 'transfer_in');
        });
    }

    /** Set saldo gudang ke angka hasil hitung fisik (stok opname). */
    public function adjust(int $itemId, int $warehouseId, float $newQty, string $refNumber, ?string $note = null): ?StockMovement
    {
        if ($newQty < 0) {
            throw new \InvalidArgumentException('Saldo stok tidak boleh negatif.');
        }

        return DB::transaction(function () use ($itemId, $warehouseId, $newQty, $refNumber, $note) {
            $stock = $this->lockedStock($itemId, $warehouseId);
            $diff = round($newQty - $stock->quantity, 2);

            if ($diff == 0.0) {
                return null;
            }

            $stock->quantity = round($newQty, 2);
            $stock->save();

            return $this->record(
                $stock, 'adjustment', max($diff, 0), max(-$diff, 0), 'adjustment', null, $refNumber, $note
            );
        });
    }

    public function balance(int $itemId, int $warehouseId): float
    {
        return (float) Stock::where('item_id', $itemId)->where('warehouse_id', $warehouseId)->value('quantity');
    }

    protected function lockedStock(int $itemId, int $warehouseId): Stock
    {
        $stock = Stock::where('item_id', $itemId)->where('warehouse_id', $warehouseId)->lockForUpdate()->first();

        return $stock ?: Stock::create(['item_id' => $itemId, 'warehouse_id' => $warehouseId, 'quantity' => 0]);
    }

    protected function record(
        Stock $stock, string $type, float $in, float $out,
        string $refType, ?int $refId, string $refNumber, ?string $note
    ): StockMovement {
        return StockMovement::create([
            'item_id' => $stock->item_id,
            'warehouse_id' => $stock->warehouse_id,
            'type' => $type,
            'qty_in' => $in,
            'qty_out' => $out,
            'balance' => $stock->quantity,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'reference_number' => $refNumber,
            'user_id' => auth()->id(),
            'note' => $note,
        ]);
    }

    protected function assertPositive(float $qty): void
    {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Jumlah harus lebih besar dari nol.');
        }
    }

    protected function num(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', '.'), '0'), ',');
    }
}
