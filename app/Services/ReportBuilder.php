<?php

namespace App\Services;

use App\Models\GoodsReceiptItem;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\UsageRequest;
use App\Models\UsageRequestItem;
use App\Support\Fmt;
use Illuminate\Support\Carbon;

/**
 * Menyusun tujuh jenis laporan menjadi struktur seragam:
 *   ['title', 'columns', 'rows', 'summary' => [[label, nilai], ...], 'right' => [indeks kolom rata kanan]]
 * sehingga satu tampilan dan satu ekspor CSV melayani semuanya.
 */
class ReportBuilder
{
    public const TYPES = [
        'pembelian' => 'Laporan Pembelian',
        'penerimaan' => 'Penerimaan Barang',
        'stok' => 'Stok Barang',
        'mutasi' => 'Mutasi Barang',
        'pemakaian-proyek' => 'Pemakaian per Proyek',
        'pemakaian-periode' => 'Pemakaian per Periode',
        'stok-minimum' => 'Stok Minimum dan Barang Habis',
    ];

    private Fmt $f;

    public function __construct()
    {
        $this->f = new Fmt();
    }

    public function build(string $type, array $filters): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        $from = Carbon::parse($filters['start'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($filters['end'] ?? now())->endOfDay();

        $report = match ($type) {
            'pembelian' => $this->purchases($from, $to, $filters),
            'penerimaan' => $this->receipts($from, $to, $filters),
            'stok' => $this->stock($filters),
            'mutasi' => $this->movements($from, $to, $filters),
            'pemakaian-proyek' => $this->usageByProject($from, $to, $filters),
            'pemakaian-periode' => $this->usageByPeriod($from, $to, $filters),
            'stok-minimum' => $this->critical($filters),
        };

        $report['title'] = self::TYPES[$type];
        $report['period'] = in_array($type, ['stok', 'stok-minimum'], true)
            ? 'Posisi per ' . $this->f->date(now())
            : $this->f->date($from) . ' – ' . $this->f->date($to);

        return $report;
    }

    private function purchases(Carbon $from, Carbon $to, array $f): array
    {
        $orders = PurchaseOrder::with('supplier')->withCount('items')
            ->whereBetween('date', [$from, $to])
            ->when($f['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->orderBy('date')->orderBy('id')->get();

        $counted = $orders->whereIn('status', [PurchaseOrder::APPROVED, PurchaseOrder::PARTIAL, PurchaseOrder::RECEIVED]);

        return [
            'columns' => ['Nomor', 'Tanggal', 'Supplier', 'Jenis Barang', 'Total', 'Status'],
            'right' => [3, 4],
            'rows' => $orders->map(fn ($o) => [$o->number, $this->f->date($o->date), $o->supplier->name, $o->items_count, $this->f->money($o->total), $o->status])->all(),
            'summary' => [
                ['Total Transaksi', $orders->count()],
                ['Nilai Pembelian Sah', $this->f->money($counted->sum('total'))],
                ['Menunggu / Draft', $orders->whereIn('status', [PurchaseOrder::DRAFT, PurchaseOrder::PENDING])->count()],
            ],
        ];
    }

    private function receipts(Carbon $from, Carbon $to, array $f): array
    {
        $lines = GoodsReceiptItem::with(['goodsReceipt.purchaseOrder.supplier', 'goodsReceipt.warehouse', 'item.unit'])
            ->whereHas('goodsReceipt', fn ($q) => $q->whereBetween('date', [$from, $to])
                ->when($f['warehouse_id'] ?? null, fn ($w, $v) => $w->where('warehouse_id', $v))
                ->when($f['supplier_id'] ?? null, fn ($w, $v) => $w->whereHas('purchaseOrder', fn ($p) => $p->where('supplier_id', $v))))
            ->get()->sortBy(fn ($l) => $l->goodsReceipt->date->format('Ymd') . $l->goodsReceipt->number);

        return [
            'columns' => ['Nomor Penerimaan', 'Tanggal', 'No. Pembelian', 'Supplier', 'Gudang', 'Barang', 'Jumlah'],
            'right' => [6],
            'rows' => $lines->map(fn ($l) => [
                $l->goodsReceipt->number, $this->f->date($l->goodsReceipt->date), $l->goodsReceipt->purchaseOrder->number,
                $l->goodsReceipt->purchaseOrder->supplier->name, $l->goodsReceipt->warehouse->name,
                $l->item->name, $this->f->qty($l->quantity) . ' ' . $l->item->unit->symbol,
            ])->values()->all(),
            'summary' => [
                ['Dokumen Penerimaan', $lines->pluck('goods_receipt_id')->unique()->count()],
                ['Baris Barang', $lines->count()],
                ['Total Kuantitas', $this->f->qty($lines->sum('quantity'))],
            ],
        ];
    }

    private function stock(array $f): array
    {
        $rows = Stock::with(['item.category', 'item.unit', 'warehouse'])
            ->where('quantity', '>', 0)
            ->when($f['warehouse_id'] ?? null, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->whereHas('item', fn ($i) => $i->where('category_id', $v)))
            ->get()->sortBy(fn ($s) => $s->item->name . $s->warehouse->name);

        return [
            'columns' => ['Kode', 'Barang', 'Kategori', 'Gudang', 'Stok', 'Minimum (total)', 'Nilai'],
            'right' => [4, 5, 6],
            'rows' => $rows->map(fn ($s) => [
                $s->item->code, $s->item->name, $s->item->category->name, $s->warehouse->name,
                $this->f->qty($s->quantity) . ' ' . $s->item->unit->symbol,
                $this->f->qty($s->item->min_stock) . ' ' . $s->item->unit->symbol,
                $this->f->money($s->quantity * $s->item->price),
            ])->values()->all(),
            'summary' => [
                ['Baris Stok', $rows->count()],
                ['Total Kuantitas', $this->f->qty($rows->sum('quantity'))],
                ['Nilai Persediaan', $this->f->money($rows->sum(fn ($s) => $s->quantity * $s->item->price))],
            ],
        ];
    }

    private function movements(Carbon $from, Carbon $to, array $f): array
    {
        $rows = StockMovement::with(['item.unit', 'warehouse', 'user'])
            ->whereBetween('created_at', [$from, $to])
            ->when($f['warehouse_id'] ?? null, fn ($q, $v) => $q->where('warehouse_id', $v))
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->whereHas('item', fn ($i) => $i->where('category_id', $v)))
            ->orderBy('id')->get();

        return [
            'columns' => ['Tanggal', 'Barang', 'Gudang', 'Jenis', 'Masuk', 'Keluar', 'Saldo', 'Referensi', 'Pengguna'],
            'right' => [4, 5, 6],
            'rows' => $rows->map(fn ($m) => [
                $this->f->datetime($m->created_at), $m->item->name, $m->warehouse->name, $m->label(),
                $m->qty_in ? $this->f->qty($m->qty_in) : '-', $m->qty_out ? $this->f->qty($m->qty_out) : '-',
                $this->f->qty($m->balance), $m->reference_number ?? '-', $m->user?->name ?? '-',
            ])->all(),
            'summary' => [
                ['Jumlah Pergerakan', $rows->count()],
                ['Total Masuk', $this->f->qty($rows->sum('qty_in'))],
                ['Total Keluar', $this->f->qty($rows->sum('qty_out'))],
            ],
        ];
    }

    private function usageLines(Carbon $from, Carbon $to, array $f)
    {
        return UsageRequestItem::with(['usageRequest.project', 'item.unit'])
            ->where('qty_released', '>', 0)
            ->whereHas('usageRequest', fn ($q) => $q->where('status', UsageRequest::DONE)
                ->whereBetween('date', [$from, $to])
                ->when($f['project_id'] ?? null, fn ($w, $v) => $w->where('project_id', $v))
                ->when($f['warehouse_id'] ?? null, fn ($w, $v) => $w->where('warehouse_id', $v)))
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->whereHas('item', fn ($i) => $i->where('category_id', $v)))
            ->get();
    }

    private function usageByProject(Carbon $from, Carbon $to, array $f): array
    {
        $lines = $this->usageLines($from, $to, $f);
        $grouped = $lines->groupBy(fn ($l) => $l->usageRequest->project->name . '|' . $l->item_id)
            ->map(fn ($g) => [
                $g->first()->usageRequest->project->name, $g->first()->item->name, $g->first()->item->unit->symbol,
                $g->sum('qty_released'), $g->sum(fn ($l) => $l->qty_released * $l->item->price),
            ])->sortKeys()->values();

        return [
            'columns' => ['Proyek', 'Barang', 'Satuan', 'Jumlah Dikeluarkan', 'Estimasi Nilai'],
            'right' => [3, 4],
            'rows' => $grouped->map(fn ($r) => [$r[0], $r[1], $r[2], $this->f->qty($r[3]), $this->f->money($r[4])])->all(),
            'summary' => [
                ['Proyek Terlibat', $grouped->pluck(0)->unique()->count()],
                ['Total Kuantitas', $this->f->qty($grouped->sum(3))],
                ['Estimasi Nilai', $this->f->money($grouped->sum(4))],
            ],
        ];
    }

    private function usageByPeriod(Carbon $from, Carbon $to, array $f): array
    {
        $lines = $this->usageLines($from, $to, $f)->sortBy(fn ($l) => $l->usageRequest->released_at?->timestamp ?? 0);

        return [
            'columns' => ['Tanggal', 'Nomor', 'Proyek', 'Barang', 'Jumlah', 'Estimasi Nilai'],
            'right' => [4, 5],
            'rows' => $lines->map(fn ($l) => [
                $this->f->date($l->usageRequest->date), $l->usageRequest->number, $l->usageRequest->project->name,
                $l->item->name, $this->f->qty($l->qty_released) . ' ' . $l->item->unit->symbol,
                $this->f->money($l->qty_released * $l->item->price),
            ])->values()->all(),
            'summary' => [
                ['Baris Pemakaian', $lines->count()],
                ['Total Kuantitas', $this->f->qty($lines->sum('qty_released'))],
                ['Estimasi Nilai', $this->f->money($lines->sum(fn ($l) => $l->qty_released * $l->item->price))],
            ],
        ];
    }

    private function critical(array $f): array
    {
        $items = Item::active()->withTotalStock()->with(['category', 'unit'])->critical()
            ->when($f['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->orderBy('name')->get();

        return [
            'columns' => ['Kode', 'Barang', 'Kategori', 'Stok', 'Minimum', 'Kekurangan', 'Status'],
            'right' => [3, 4, 5],
            'rows' => $items->map(fn ($i) => [
                $i->code, $i->name, $i->category->name,
                $this->f->qty($i->total_stock) . ' ' . $i->unit->symbol, $this->f->qty($i->min_stock) . ' ' . $i->unit->symbol,
                $this->f->qty($i->min_stock - $i->total_stock) . ' ' . $i->unit->symbol,
                Item::stockStatus((float) $i->total_stock, $i->min_stock),
            ])->all(),
            'summary' => [
                ['Barang Kritis', $items->count()],
                ['Stok Habis', $items->filter(fn ($i) => $i->total_stock <= 0)->count()],
                ['Stok Menipis', $items->filter(fn ($i) => $i->total_stock > 0)->count()],
            ],
        ];
    }
}
