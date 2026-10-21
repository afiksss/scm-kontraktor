<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = ['quantity' => 'float', 'price' => 'float', 'received_qty' => 'float'];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function outstanding(): float
    {
        return max(0, round($this->quantity - $this->received_qty, 2));
    }

    public function subtotal(): float
    {
        return $this->quantity * $this->price;
    }
}
