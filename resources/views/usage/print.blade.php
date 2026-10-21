@extends('layouts.print')
@section('title', $req->number)
@section('doc-title', ($req->status === 'Selesai' ? 'Bukti Pengeluaran Barang ' : 'Permintaan Barang ') . $req->number)

@section('content')
<div class="meta">
    <div><span>Tanggal:</span> {{ $fmt->date($req->date, 'd F Y') }}</div>
    <div><span>Status:</span> {{ $req->status }}</div>
    <div><span>Proyek:</span> {{ $req->project->name }}</div>
    <div><span>Gudang asal:</span> {{ $req->warehouse->name }}</div>
    <div><span>Pemohon:</span> {{ $req->requester?->name }}</div>
    <div><span>Keperluan:</span> {{ $req->purpose }}</div>
</div>

<table>
    <thead><tr><th>No</th><th>Kode</th><th>Nama Barang</th><th class="r">Diminta</th><th class="r">Disetujui</th><th class="r">Dikeluarkan</th></tr></thead>
    <tbody>
    @foreach ($req->items as $i => $l)
        <tr><td>{{ $i + 1 }}</td><td>{{ $l->item->code }}</td><td>{{ $l->item->name }}</td>
            <td class="r">{{ $fmt->qty($l->qty_requested) }} {{ $l->item->unit->symbol }}</td>
            <td class="r">{{ $fmt->qty($l->qty_approved) }}</td>
            <td class="r">{{ $fmt->qty($l->qty_released) }}</td></tr>
    @endforeach
    </tbody>
</table>

<div class="sign">
    <div>Pemohon<br>{{ $req->requester?->name }}</div>
    <div>Disetujui oleh<br>{{ $req->approver?->name }}</div>
    <div>Petugas Gudang<br>{{ $req->releaser?->name }}</div>
</div>
@endsection
