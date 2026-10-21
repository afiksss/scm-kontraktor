<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    public const DRAFT = 'Draft';
    public const PENDING = 'Menunggu Persetujuan';
    public const APPROVED = 'Disetujui';
    public const PARTIAL = 'Diterima Sebagian';
    public const RECEIVED = 'Diterima';
    public const REJECTED = 'Ditolak';
    public const CANCELLED = 'Dibatalkan';

    public const STATUSES = [
        self::DRAFT, self::PENDING, self::APPROVED, self::PARTIAL, self::RECEIVED, self::REJECTED, self::CANCELLED,
    ];

    protected $guarded = [];

    protected $casts = ['date' => 'date', 'approved_at' => 'datetime', 'total' => 'float'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isEditable(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function canReceive(): bool
    {
        return in_array($this->status, [self::APPROVED, self::PARTIAL], true);
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::DRAFT, self::PENDING, self::APPROVED], true)
            && !$this->receipts()->exists();
    }
}
