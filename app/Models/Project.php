<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function usageRequests()
    {
        return $this->hasMany(UsageRequest::class);
    }
}
