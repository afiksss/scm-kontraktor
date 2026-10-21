<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageRequest extends Model
{
    public const PENDING = 'Menunggu Persetujuan';
    public const APPROVED = 'Disetujui';
    public const REJECTED = 'Ditolak';
    public const PROCESSING = 'Diproses Gudang';
    public const DONE = 'Selesai';
    public const CANCELLED = 'Dibatalkan';

    public const STATUSES = [
        self::PENDING, self::APPROVED, self::REJECTED, self::PROCESSING, self::DONE, self::CANCELLED,
    ];

    protected $guarded = [];

    protected $casts = ['date' => 'date', 'approved_at' => 'datetime', 'released_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function releaser()
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function items()
    {
        return $this->hasMany(UsageRequestItem::class);
    }
}
