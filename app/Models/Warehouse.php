<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
