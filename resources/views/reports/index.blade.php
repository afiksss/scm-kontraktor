@extends('layouts.app')
@section('title', 'Laporan')
@section('crumb', 'Beranda / Laporan')

@section('content')
@php
    // Filter yang relevan untuk tiap jenis laporan.
    $show = [
        'pembelian' => ['period', 'supplier'],
        'penerimaan' => ['period', 'supplier', 'warehouse'],
        'stok' => ['warehouse', 'category'],
        'mutasi' => ['period', 'warehouse', 'category'],
        'pemakaian-proyek' => ['period', 'project', 'warehouse', 'category'],
        'pemakaian-periode' => ['period', 'project', 'warehouse', 'category'],
        'stok-minimum' => ['category'],
    ][$type];
@endphp

<x-page-header title="Laporan" subtitle="Pratinjau siap cetak. Gunakan Ekspor Excel (CSV) untuk analisis lanjutan.">
    <button class="btn btn-primary no-print" onclick="window.print()"><i data-lucide="printer" class="w-4 h-4"></i>Cetak Laporan</button>
    <a class="btn btn-secondary no-print" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i>Ekspor Excel</a>
</x-page-header>

<div class="card mb-4 overflow-x-auto flex no-print">
    @foreach ($types as $key => $label)
        <a href="{{ route('reports.index', ['type' => $key]) }}"
           class="px-4 py-3 text-xs font-bold whitespace-nowrap border-b-2 {{ $type === $key ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-navy' }}">{{ $label }}</a>
    @endforeach
</div>

<form method="GET" class="card p-4 mb-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 items-end no-print">
    <input type="hidden" name="type" value="{{ $type }}">
    @if (in_array('period', $show))
        <div><label for="start" class="label">Tanggal awal</label><input id="start" type="date" name="start" value="{{ $filters['start'] }}" class="field"></div>
        <div><label for="end" class="label">Tanggal akhir</label><input id="end" type="date" name="end" value="{{ $filters['end'] }}" class="field"></div>
    @endif
    @if (in_array('project', $show))
        <div><label for="project_id" class="label">Proyek</label>
            <select id="project_id" name="project_id" class="field"><option value="">Semua proyek</option>
                @foreach ($projects as $x)<option value="{{ $x->id }}" @selected(($filters['project_id'] ?? '') == $x->id)>{{ $x->name }}</option>@endforeach</select></div>
    @endif
    @if (in_array('supplier', $show))
        <div><label for="supplier_id" class="label">Supplier</label>
            <select id="supplier_id" name="supplier_id" class="field"><option value="">Semua supplier</option>
                @foreach ($suppliers as $x)<option value="{{ $x->id }}" @selected(($filters['supplier_id'] ?? '') == $x->id)>{{ $x->name }}</option>@endforeach</select></div>
    @endif
    @if (in_array('warehouse', $show))
        <div><label for="warehouse_id" class="label">Gudang</label>
            <select id="warehouse_id" name="warehouse_id" class="field"><option value="">Semua gudang</option>
                @foreach ($warehouses as $x)<option value="{{ $x->id }}" @selected(($filters['warehouse_id'] ?? '') == $x->id)>{{ $x->name }}</option>@endforeach</select></div>
    @endif
    @if (in_array('category', $show))
        <div><label for="category_id" class="label">Kategori barang</label>
            <select id="category_id" name="category_id" class="field"><option value="">Semua kategori</option>
                @foreach ($categories as $x)<option value="{{ $x->id }}" @selected(($filters['category_id'] ?? '') == $x->id)>{{ $x->name }}</option>@endforeach</select></div>
    @endif
    <div class="flex gap-2"><button class="btn btn-primary">Tampilkan</button><a href="{{ route('reports.index', ['type' => $type]) }}" class="btn btn-secondary">Reset</a></div>
</form>

<div class="grid sm:grid-cols-3 gap-4 mb-5">
    @foreach ($report['summary'] as $i => [$label, $value])
        <x-kpi :label="$label" :value="$value" :icon="['file-text', 'boxes', 'wallet'][$i] ?? 'file-text'" />
    @endforeach
</div>

<section class="card p-5">
    <div class="border-b-2 border-navy pb-4 mb-5">
        <div class="text-xs font-extrabold tracking-widest text-blue-700">{{ strtoupper(config('scm.company')) }}</div>
        <h2 class="text-xl font-bold mt-2">{{ $report['title'] }}</h2>
        <div class="text-xs text-slate-500 mt-1.5">Periode: {{ $report['period'] }} • Tanggal cetak: {{ $fmt->date(now(), 'd F Y') }} • oleh {{ auth()->user()->name }}</div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px]">
            <thead><tr>@foreach ($report['columns'] as $i => $c)<th class="th {{ in_array($i, $report['right']) ? 'text-right' : '' }}">{{ $c }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $i => $cell)
                        <td class="td {{ in_array($i, $report['right']) ? 'text-right' : '' }}">
                            @if ($report['columns'][$i] === 'Status' || $report['columns'][$i] === 'Jenis' && str_contains($report['title'], 'Mutasi'))<x-badge :status="(string) $cell" />@else{{ $cell }}@endif
                        </td>
                    @endforeach
                </tr>
            @empty<x-empty :cols="count($report['columns'])" message="Tidak ada data pada filter ini" />@endforelse
            </tbody>
        </table>
    </div>
    <div class="text-xs text-slate-500 mt-4">{{ count($report['rows']) }} baris data.</div>
</section>
@endsection
