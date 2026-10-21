@extends('layouts.app')
@section('title', 'Penerimaan ' . $receipt->number)
@section('crumb', 'Beranda / Penerimaan Barang')

@section('content')
<x-page-header :title="$receipt->number" subtitle="Bukti verifikasi penerimaan barang.">
    <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Kembali</a>
    <button class="btn btn-secondary" onclick="window.print()"><i data-lucide="printer" class="w-4 h-4"></i>Cetak</button>
</x-page-header>

<section class="card p-6 mb-5">
    <div class="grid sm:grid-cols-3 gap-5 text-sm">
        <div><div class="text-xs text-slate-500">Pembelian</div><a class="font-bold text-brand mt-1 block" href="{{ route('purchases.show', $receipt->purchaseOrder) }}">{{ $receipt->purchaseOrder->number }}</a></div>
        <div><div class="text-xs text-slate-500">Supplier</div><div class="font-bold mt-1">{{ $receipt->purchaseOrder->supplier->name }}</div></div>
        <div><div class="text-xs text-slate-500">Tanggal terima</div><div class="font-bold mt-1">{{ $fmt->date($receipt->date, 'd F Y') }}</div></div>
        <div><div class="text-xs text-slate-500">Gudang</div><div class="mt-1">{{ $receipt->warehouse->name }}</div></div>
        <div><div class="text-xs text-slate-500">Surat jalan</div><div class="mt-1">{{ $receipt->delivery_note ?? '-' }}</div></div>
        <div><div class="text-xs text-slate-500">Diverifikasi oleh</div><div class="mt-1">{{ $receipt->receiver?->name ?? '-' }}</div></div>
    </div>
    @if ($receipt->notes)<p class="text-sm text-slate-600 mt-5 pt-4 border-t border-slate-100"><strong>Catatan:</strong> {{ $receipt->notes }}</p>@endif
</section>

<section class="card overflow-hidden">
    <table class="w-full">
        <thead><tr><th class="th">Kode</th><th class="th">Barang</th><th class="th text-right">Jumlah Diterima</th></tr></thead>
        <tbody>
        @foreach ($receipt->items as $i)
            <tr><td class="td font-bold text-brand">{{ $i->item->code }}</td><td class="td">{{ $i->item->name }}</td><td class="td text-right font-semibold">{{ $fmt->qty($i->quantity) }} {{ $i->item->unit->symbol }}</td></tr>
        @endforeach
        </tbody>
    </table>
</section>
@endsection
