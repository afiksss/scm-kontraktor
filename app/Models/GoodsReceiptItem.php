<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceiptItem extends Model
{
    protected $guarded = [];

    protected $casts = ['quantity' => 'float'];

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
