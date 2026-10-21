<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /** Subquery total stok semua gudang (portabel untuk MySQL/SQLite/PostgreSQL). */
    public const TOTAL_SQL = '(select coalesce(sum(s.quantity), 0) from stocks s where s.item_id = items.id)';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'min_stock' => 'float',
        'price' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeActive($q)
    {
        return $q->where('items.is_active', true);
    }

    public function scopeWithTotalStock($q)
    {
        return $q->select('items.*')->selectRaw(self::TOTAL_SQL . ' as total_stock');
    }

    public function scopeOutOfStock($q)
    {
        return $q->whereRaw(self::TOTAL_SQL . ' <= 0');
    }

    /** Stok > 0 tetapi di bawah minimum. */
    public function scopeRunningLow($q)
    {
        return $q->whereRaw(self::TOTAL_SQL . ' > 0')->whereRaw(self::TOTAL_SQL . ' < items.min_stock');
    }

    /** Stok di bawah minimum, termasuk yang habis. */
    public function scopeCritical($q)
    {
        return $q->whereRaw(self::TOTAL_SQL . ' < items.min_stock');
    }

    public function scopeAvailable($q)
    {
        return $q->whereRaw(self::TOTAL_SQL . ' >= items.min_stock')->whereRaw(self::TOTAL_SQL . ' > 0');
    }

    public static function stockStatus(float $total, float $min): string
    {
        if ($total <= 0) {
            return 'Stok Habis';
        }

        return $total < $min ? 'Stok Menipis' : 'Tersedia';
    }
}
