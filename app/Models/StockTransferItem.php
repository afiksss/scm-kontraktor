<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $guarded = [];

    protected $casts = ['quantity' => 'float'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
