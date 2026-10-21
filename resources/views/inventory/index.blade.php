@extends('layouts.app')
@section('title', 'Inventaris Barang')
@section('crumb', 'Beranda / Inventaris')

@section('content')
<x-page-header title="Inventaris Barang" subtitle="Pantau saldo, batas minimum, dan lokasi material di setiap gudang.">
    @can('master.manage')<a href="{{ route('master.create', 'barang') }}" class="btn btn-secondary"><i data-lucide="plus" class="w-4 h-4"></i>Tambah Barang</a>@endcan
    @can('inventory.manage')<a href="{{ route('transfers.create') }}" class="btn btn-primary"><i data-lucide="arrow-left-right" class="w-4 h-4"></i>Mutasi Barang</a>@endcan
</x-page-header>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <x-kpi label="Stok Tersedia" :value="$fmt->qty($kpi['units']) . ' unit'" hint="Di seluruh gudang" icon="package" tone="green" />
    <x-kpi label="Stok Kritis" :value="$kpi['critical'] . ' item'" hint="Perlu pengadaan" icon="triangle-alert" tone="red" />
    <x-kpi label="Total Kategori" :value="$kpi['categories'] . ' kategori'" hint="Material dan peralatan" icon="layers" />
    <x-kpi label="Nilai Persediaan" :value="$fmt->money($kpi['value'])" hint="Estimasi (harga acuan × stok)" icon="wallet" tone="orange" />
</div>

<form method="GET" class="card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="label">Cari data</label>
        <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="Kode atau nama barang...">
    </div>
    <div class="min-w-[150px]">
        <label for="category_id" class="label">Kategori</label>
        <select id="category_id" name="category_id" class="field">
            <option value="">Semua kategori</option>
            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="min-w-[180px]">
        <label for="warehouse_id" class="label">Gudang</label>
        <select id="warehouse_id" name="warehouse_id" class="field">
            <option value="">Semua gudang</option>
            @foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
        </select>
    </div>
    <div class="min-w-[150px]">
        <label for="stock_status" class="label">Status stok</label>
        <select id="stock_status" name="stock_status" class="field">
            <option value="">Semua status</option>
            @foreach (['Tersedia', 'Stok Menipis', 'Stok Habis', 'Kritis'] as $s)<option @selected(request('stock_status') === $s)>{{ $s }}</option>@endforeach
        </select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    @if (request()->hasAny(['q', 'category_id', 'warehouse_id', 'stock_status']))<a href="{{ route('inventory.index') }}" class="btn btn-secondary">Reset</a>@endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px]">
            <thead><tr>
                <th class="th">Kode</th><th class="th">Nama Barang</th><th class="th">Kategori</th><th class="th">Stok Tersedia</th>
                <th class="th">Stok Minimum</th><th class="th">Lokasi Gudang</th><th class="th">Status Stok</th><th class="th">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($items as $g)
                @php
                    $total = (float) $g->total_stock;
                    $st = \App\Models\Item::stockStatus($total, $g->min_stock);
                    $pct = $g->min_stock > 0 ? min(100, $total / $g->min_stock * 100) : 100;
                @endphp
                <tr class="hover:bg-slate-50/70">
                    <td class="td font-bold text-brand"><a href="{{ route('inventory.show', $g) }}">{{ $g->code }}</a></td>
                    <td class="td font-semibold">{{ $g->name }}</td>
                    <td class="td">{{ $g->category->name }}</td>
                    <td class="td">
                        <strong>{{ $fmt->qty($total) }} {{ $g->unit->symbol }}</strong>
                        <div class="h-1.5 w-24 rounded bg-slate-200 mt-2 overflow-hidden"><div class="h-full {{ $total < $g->min_stock ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $pct }}%"></div></div>
                    </td>
                    <td class="td">{{ $fmt->qty($g->min_stock) }} {{ $g->unit->symbol }}</td>
                    <td class="td text-xs">
                        @forelse ($g->stocks->where('quantity', '>', 0) as $s)
                            <div>{{ $s->warehouse->name }}: <strong>{{ $fmt->qty($s->quantity) }}</strong></div>
                        @empty<span class="text-slate-400">—</span>@endforelse
                    </td>
                    <td class="td"><x-badge :status="$st" /></td>
                    <td class="td"><a href="{{ route('inventory.show', $g) }}" class="icon-btn" title="Detail & riwayat" aria-label="Detail {{ $g->name }}"><i data-lucide="eye" class="w-4 h-4"></i></a></td>
                </tr>
            @empty<x-empty cols="8" />@endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
