<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const LABELS = [
        'in' => 'Masuk',
        'out' => 'Keluar',
        'transfer_in' => 'Mutasi Masuk',
        'transfer_out' => 'Mutasi Keluar',
        'adjustment' => 'Penyesuaian',
    ];

    protected $guarded = [];

    protected $casts = ['qty_in' => 'float', 'qty_out' => 'float', 'balance' => 'float'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->type] ?? $this->type;
    }
}
