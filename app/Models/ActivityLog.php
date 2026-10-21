<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $module, string $description): void
    {
        static::create([
            'user_id' => auth()->id(),
            'module' => $module,
            'description' => $description,
            'ip_address' => request()?->ip(),
        ]);
    }
}
