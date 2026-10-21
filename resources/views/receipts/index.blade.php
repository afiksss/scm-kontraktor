@extends('layouts.app')
@section('title', 'Penerimaan Barang')
@section('crumb', 'Beranda / Penerimaan Barang')

@section('content')
<x-page-header title="Penerimaan Barang" subtitle="Riwayat verifikasi barang masuk. Setiap penerimaan menambah stok gudang dan tercatat pada riwayat pergerakan.">
    @can('purchase.receive')
        <a href="{{ route('purchases.index', ['status' => 'Disetujui']) }}" class="btn btn-primary"><i data-lucide="package-check" class="w-4 h-4"></i>Pembelian Siap Diterima</a>
    @endcan
</x-page-header>

<form method="GET" class="card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[220px]">
        <label for="q" class="label">Cari data</label>
        <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="Nomor penerimaan, nomor pembelian, atau surat jalan...">
    </div>
    <button class="btn btn-primary">Cari</button>
    @if (request('q'))<a href="{{ route('receipts.index') }}" class="btn btn-secondary">Reset</a>@endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px]">
            <thead><tr><th class="th">Nomor Penerimaan</th><th class="th">Tanggal</th><th class="th">No. Pembelian</th><th class="th">Supplier</th><th class="th">Gudang</th><th class="th">Surat Jalan</th><th class="th">Penerima</th></tr></thead>
            <tbody>
            @forelse ($receipts as $r)
                <tr class="hover:bg-slate-50/70">
                    <td class="td font-bold text-brand"><a href="{{ route('receipts.show', $r) }}">{{ $r->number }}</a></td>
                    <td class="td">{{ $fmt->date($r->date) }}</td>
                    <td class="td"><a class="text-brand" href="{{ route('purchases.show', $r->purchaseOrder) }}">{{ $r->purchaseOrder->number }}</a></td>
                    <td class="td">{{ $r->purchaseOrder->supplier->name }}</td>
                    <td class="td">{{ $r->warehouse->name }}</td>
                    <td class="td">{{ $r->delivery_note ?? '-' }}</td>
                    <td class="td">{{ $r->receiver?->name ?? '-' }}</td>
                </tr>
            @empty<x-empty cols="7" />@endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $receipts->links() }}</div>
@endsection
