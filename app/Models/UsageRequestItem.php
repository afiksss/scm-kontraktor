<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageRequestItem extends Model
{
    protected $guarded = [];

    protected $casts = ['qty_requested' => 'float', 'qty_approved' => 'float', 'qty_released' => 'float'];

    public function usageRequest()
    {
        return $this->belongsTo(UsageRequest::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
