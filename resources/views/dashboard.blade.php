@extends('layouts.app')
@section('title', 'Dashboard')
@section('crumb', 'Beranda')

@section('content')
<x-page-header title="Dashboard" :subtitle="'Ringkasan operasional • ' . $fmt->date(now(), 'F Y')">
    @can('purchase.create')<a href="{{ route('purchases.create') }}" class="btn btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Buat Pembelian</a>@endcan
    @can('usage.create')<a href="{{ route('usage.create') }}" class="btn btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Permintaan Barang</a>@endcan
</x-page-header>

@if (($pendingPurchases && auth()->user()->can('purchase.approve')) || ($pendingUsage && auth()->user()->can('usage.approve')))
    <div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-[13px] text-amber-900 flex flex-wrap gap-x-6 gap-y-1 items-center">
        <i data-lucide="clock" class="w-4 h-4"></i><strong>Perlu tindakan Anda:</strong>
        @can('purchase.approve')@if ($pendingPurchases)<a class="underline font-semibold" href="{{ route('purchases.index', ['status' => 'Menunggu Persetujuan']) }}">{{ $pendingPurchases }} pembelian menunggu persetujuan</a>@endif @endcan
        @can('usage.approve')@if ($pendingUsage)<a class="underline font-semibold" href="{{ route('usage.index', ['status' => 'Menunggu Persetujuan']) }}">{{ $pendingUsage }} permintaan barang menunggu persetujuan</a>@endif @endcan
    </div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
    <x-kpi label="Total Jenis Barang" :value="number_format($kpi['items'], 0, ',', '.') . ' jenis'" hint="Barang aktif" icon="boxes" />
    <x-kpi label="Total Stok Barang" :value="$fmt->qty($kpi['stock']) . ' unit'" hint="Seluruh gudang" icon="package" tone="green" />
    <x-kpi label="Pembelian Bulan Ini" :value="$fmt->money($kpi['purchase'])" hint="Disetujui & diterima" icon="shopping-cart" />
    <x-kpi label="Pemakaian Bulan Ini" :value="$fmt->qty($kpi['usage']) . ' unit'" hint="Pengeluaran terkonfirmasi" icon="clipboard-check" tone="orange" />
    <x-kpi label="Barang Stok Kritis" :value="$kpi['critical'] . ' item'" hint="Di bawah stok minimum" icon="triangle-alert" tone="red" />
</div>

<div class="grid xl:grid-cols-[1.65fr_1fr] gap-4 mt-5">
    <section class="card p-5">
        <h2 class="font-bold mb-4">Pembelian dan Pemakaian Barang <span class="text-xs font-medium text-slate-500">· 6 bulan terakhir</span></h2>
        <div class="h-64"><canvas id="trend-chart" role="img" aria-label="Grafik pembelian dan pemakaian enam bulan terakhir"></canvas></div>
    </section>
    <section class="card p-5">
        <h2 class="font-bold mb-4">Distribusi Stok per Kategori</h2>
        <div class="h-64">
            @if (count($distribution['labels']))<canvas id="donut-chart" role="img" aria-label="Distribusi stok per kategori"></canvas>
            @else<div class="h-full flex items-center justify-center text-sm text-slate-500">Belum ada stok tercatat.</div>@endif
        </div>
    </section>
</div>

<div class="grid xl:grid-cols-2 gap-4 mt-5">
    <section>
        <div class="flex justify-between items-center mb-3">
            <h2 class="font-bold">Transaksi Pembelian Terbaru</h2>
            @can('purchase.view')<a href="{{ route('purchases.index') }}" class="btn btn-secondary">Lihat semua</a>@endcan
        </div>
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[560px]">
                <thead><tr><th class="th">Nomor</th><th class="th">Supplier</th><th class="th text-right">Total</th><th class="th">Status</th></tr></thead>
                <tbody>
                @forelse ($latestPurchases as $p)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td font-bold text-brand">
                            @can('purchase.view')<a href="{{ route('purchases.show', $p) }}">{{ $p->number }}</a>@else{{ $p->number }}@endcan
                            <div class="text-[11px] font-normal text-slate-500">{{ $fmt->date($p->date) }}</div>
                        </td>
                        <td class="td">{{ $p->supplier->name }}</td>
                        <td class="td text-right font-semibold">{{ $fmt->money($p->total) }}</td>
                        <td class="td"><x-badge :status="$p->status" /></td>
                    </tr>
                @empty<x-empty cols="4" />@endforelse
                </tbody>
            </table>
        </div>
    </section>
    <section>
        <div class="flex justify-between items-center mb-3">
            <h2 class="font-bold">Permintaan Barang Terbaru</h2>
            <a href="{{ route('usage.index') }}" class="btn btn-secondary">Lihat semua</a>
        </div>
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[560px]">
                <thead><tr><th class="th">Nomor</th><th class="th">Proyek</th><th class="th">Pemohon</th><th class="th">Status</th></tr></thead>
                <tbody>
                @forelse ($latestUsage as $r)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td font-bold text-brand"><a href="{{ route('usage.show', $r) }}">{{ $r->number }}</a>
                            <div class="text-[11px] font-normal text-slate-500">{{ $fmt->date($r->date) }}</div></td>
                        <td class="td">{{ $r->project->name }}</td>
                        <td class="td">{{ $r->requester?->name ?? '-' }}</td>
                        <td class="td"><x-badge :status="$r->status" /></td>
                    </tr>
                @empty<x-empty cols="4" />@endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="grid xl:grid-cols-2 gap-4 mt-5">
    <section class="card p-5">
        <h2 class="font-bold flex gap-2 items-center mb-3"><i data-lucide="triangle-alert" class="w-4 h-4 text-red-600"></i>Stok Minimum &amp; Habis</h2>
        @forelse ($lowStock as $g)
            <a href="{{ route('inventory.show', $g) }}" class="py-3 border-t border-slate-100 flex justify-between gap-3 text-xs hover:bg-slate-50 -mx-2 px-2 rounded">
                <span class="font-semibold">{{ $g->name }}</span>
                <span class="text-red-700 font-bold text-right">{{ $fmt->qty($g->total_stock) }} {{ $g->unit->symbol }}
                    <span class="text-slate-500 font-normal">/ min. {{ $fmt->qty($g->min_stock) }}</span></span>
            </a>
        @empty
            <p class="text-sm text-slate-500 py-6 text-center">Semua barang berada di atas stok minimum.</p>
        @endforelse
    </section>
    <section class="card p-5">
        <h2 class="font-bold mb-3">Proyek dengan Pemakaian Material Tertinggi</h2>
        @php $max = max(1, (float) $topProjects->max('qty')); @endphp
        @forelse ($topProjects as $p)
            <div class="py-3 border-t border-slate-100 text-xs">
                <div class="flex justify-between gap-3"><span class="font-semibold">{{ $p->name }}</span><strong>{{ $fmt->qty($p->qty) }} unit</strong></div>
                <div class="h-1.5 rounded bg-blue-100 mt-2 overflow-hidden"><div class="h-full bg-brand" style="width: {{ round($p->qty / $max * 100) }}%"></div></div>
            </div>
        @empty
            <p class="text-sm text-slate-500 py-6 text-center">Belum ada pemakaian terkonfirmasi.</p>
        @endforelse
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    const trend = @js($trend), dist = @js($distribution);
    new Chart(document.getElementById('trend-chart'), {
        type: 'line',
        data: {labels: trend.labels, datasets: [
            {label: 'Pembelian (juta Rp)', data: trend.purchase, borderColor: '#2563EB', backgroundColor: '#2563EB18', fill: true, tension: .35, yAxisID: 'y'},
            {label: 'Pemakaian (unit)', data: trend.usage, borderColor: '#F59E0B', tension: .35, yAxisID: 'y1'},
        ]},
        options: {responsive: true, maintainAspectRatio: false, interaction: {mode: 'index', intersect: false},
            plugins: {legend: {position: 'bottom', labels: {usePointStyle: true, boxWidth: 9, font: {size: 11}}}},
            scales: {y: {beginAtZero: true, grid: {color: '#EEF2F7'}}, y1: {beginAtZero: true, position: 'right', grid: {drawOnChartArea: false}}, x: {grid: {display: false}}}}
    });
    const donut = document.getElementById('donut-chart');
    if (donut) new Chart(donut, {
        type: 'doughnut',
        data: {labels: dist.labels, datasets: [{data: dist.data, backgroundColor: ['#2563EB', '#142B4A', '#35A888', '#F59E0B', '#93B8E9', '#8B5CF6', '#EC4899', '#64748B'], borderWidth: 0}]},
        options: {responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: {legend: {position: 'bottom', labels: {usePointStyle: true, boxWidth: 9, font: {size: 11}}}}}
    });
</script>
@endpush
