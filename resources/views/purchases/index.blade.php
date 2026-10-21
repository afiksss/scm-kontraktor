@extends('layouts.app')
@section('title', 'Pembelian Barang')
@section('crumb', 'Beranda / Pembelian')

@section('content')
<x-page-header title="Pembelian Barang" subtitle="Pemesanan dipisahkan dari penerimaan: stok baru bertambah setelah gudang memverifikasi penerimaan barang.">
    @can('purchase.create')
        <a href="{{ route('purchases.create') }}" class="btn btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Tambah Pembelian</a>
    @endcan
</x-page-header>

<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('purchases.index') }}" class="btn {{ request('status') ? 'btn-secondary' : 'btn-primary' }}">Semua</a>
    @foreach (\App\Models\PurchaseOrder::STATUSES as $s)
        <a href="{{ route('purchases.index', ['status' => $s]) }}" class="btn {{ request('status') === $s ? 'btn-primary' : 'btn-secondary' }}">
            {{ $s }} <span class="opacity-70">({{ $counts[$s] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<form method="GET" class="card p-4 mb-4 flex flex-wrap gap-3 items-end">
    @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="label">Cari data</label>
        <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="Nomor pembelian atau nama supplier...">
    </div>
    <div class="min-w-[150px]">
        <label for="date" class="label">Tanggal</label>
        <input id="date" type="date" name="date" value="{{ request('date') }}" class="field">
    </div>
    <div class="min-w-[200px]">
        <label for="supplier_id" class="label">Supplier</label>
        <select id="supplier_id" name="supplier_id" class="field">
            <option value="">Semua supplier</option>
            @foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>@endforeach
        </select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    @if (request()->hasAny(['q', 'date', 'supplier_id', 'status']))<a href="{{ route('purchases.index') }}" class="btn btn-secondary">Reset</a>@endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px]">
            <thead><tr>
                <th class="th">Nomor Pembelian</th><th class="th">Tanggal</th><th class="th">Supplier</th>
                <th class="th">Jenis</th><th class="th text-right">Total Harga</th><th class="th">Status</th><th class="th">Aksi</th>
            </tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr class="hover:bg-slate-50/70">
                    <td class="td font-bold text-brand"><a href="{{ route('purchases.show', $o) }}">{{ $o->number }}</a></td>
                    <td class="td">{{ $fmt->date($o->date) }}</td>
                    <td class="td">{{ $o->supplier->name }}</td>
                    <td class="td">{{ $o->items_count }} jenis</td>
                    <td class="td text-right font-semibold">{{ $fmt->money($o->total) }}</td>
                    <td class="td"><x-badge :status="$o->status" /></td>
                    <td class="td">
                        <div class="flex gap-1.5">
                            <a href="{{ route('purchases.show', $o) }}" class="icon-btn" title="Lihat detail" aria-label="Detail {{ $o->number }}"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            @can('purchase.create')
                                @if ($o->isEditable())<a href="{{ route('purchases.edit', $o) }}" class="icon-btn" title="Edit draft" aria-label="Edit {{ $o->number }}"><i data-lucide="pencil" class="w-4 h-4"></i></a>@endif
                            @endcan
                            @can('purchase.receive')
                                @if ($o->canReceive())<a href="{{ route('receipts.create', $o) }}" class="icon-btn text-emerald-700" title="Terima barang" aria-label="Terima barang {{ $o->number }}"><i data-lucide="package-check" class="w-4 h-4"></i></a>@endif
                            @endcan
                            <a href="{{ route('purchases.print', $o) }}" target="_blank" class="icon-btn" title="Cetak" aria-label="Cetak {{ $o->number }}"><i data-lucide="printer" class="w-4 h-4"></i></a>
                        </div>
                    </td>
                </tr>
            @empty
                <x-empty cols="7" />
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
