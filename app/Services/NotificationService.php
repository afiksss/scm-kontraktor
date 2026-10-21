<?php

namespace App\Services;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\UsageRequest;
use App\Models\User;

/**
 * Notifikasi dihitung langsung dari data (tanpa tabel notifikasi),
 * disaring sesuai izin role pengguna.
 */
class NotificationService
{
    /** @return array<int, array{text:string,url:string,tone:string}> */
    public function for(User $user): array
    {
        $n = [];
        $can = fn (string $p) => $user->can($p);

        if ($can('inventory.view') && ($c = Item::active()->critical()->count()) > 0) {
            $n[] = ['text' => "$c barang stok kritis / di bawah minimum", 'url' => route('inventory.index', ['stock_status' => 'Kritis']), 'tone' => 'red'];
        }

        if ($can('purchase.approve') && ($c = PurchaseOrder::where('status', PurchaseOrder::PENDING)->count()) > 0) {
            $n[] = ['text' => "$c pembelian menunggu persetujuan", 'url' => route('purchases.index', ['status' => PurchaseOrder::PENDING]), 'tone' => 'amber'];
        }

        if ($can('usage.approve') && ($c = UsageRequest::where('status', UsageRequest::PENDING)->count()) > 0) {
            $n[] = ['text' => "$c permintaan barang menunggu persetujuan", 'url' => route('usage.index', ['status' => UsageRequest::PENDING]), 'tone' => 'amber'];
        }

        if ($can('purchase.receive') && ($c = PurchaseOrder::whereIn('status', [PurchaseOrder::APPROVED, PurchaseOrder::PARTIAL])->count()) > 0) {
            $n[] = ['text' => "$c pembelian menunggu penerimaan barang", 'url' => route('purchases.index', ['status' => PurchaseOrder::APPROVED]), 'tone' => 'blue'];
        }

        if ($can('usage.release') && ($c = UsageRequest::whereIn('status', [UsageRequest::APPROVED, UsageRequest::PROCESSING])->count()) > 0) {
            $n[] = ['text' => "$c permintaan siap dikeluarkan dari gudang", 'url' => route('usage.index', ['status' => UsageRequest::APPROVED]), 'tone' => 'blue'];
        }

        return $n;
    }
}
