<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $guarded = [];

    protected $casts = ['date' => 'date'];

    public function from()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function to()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
