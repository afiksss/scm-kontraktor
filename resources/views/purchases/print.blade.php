@extends('layouts.print')
@section('title', $po->number)
@section('doc-title', 'Pesanan Pembelian ' . $po->number)

@section('content')
<div class="meta">
    <div><span>Tanggal:</span> {{ $fmt->date($po->date, 'd F Y') }}</div>
    <div><span>Status:</span> {{ $po->status }}</div>
    <div><span>Supplier:</span> {{ $po->supplier->name }}</div>
    <div><span>Kontak:</span> {{ $po->supplier->contact_person }} {{ $po->supplier->phone }}</div>
</div>

<table>
    <thead><tr><th>No</th><th>Kode</th><th>Nama Barang</th><th class="r">Jumlah</th><th class="r">Harga Satuan</th><th class="r">Subtotal</th></tr></thead>
    <tbody>
    @foreach ($po->items as $i => $l)
        <tr><td>{{ $i + 1 }}</td><td>{{ $l->item->code }}</td><td>{{ $l->item->name }}</td>
            <td class="r">{{ $fmt->qty($l->quantity) }} {{ $l->item->unit->symbol }}</td>
            <td class="r">{{ $fmt->money($l->price) }}</td><td class="r">{{ $fmt->money($l->subtotal()) }}</td></tr>
    @endforeach
    <tr><td colspan="5" class="r"><strong>Total</strong></td><td class="r"><strong>{{ $fmt->money($po->total) }}</strong></td></tr>
    </tbody>
</table>
@if ($po->notes)<p><strong>Catatan:</strong> {{ $po->notes }}</p>@endif

<div class="sign">
    <div>Dibuat oleh<br>{{ $po->creator?->name }}</div>
    <div>Disetujui oleh<br>{{ $po->approver?->name }}</div>
    <div>Supplier</div>
</div>
@endsection
