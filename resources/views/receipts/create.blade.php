@extends('layouts.app')
@section('title', 'Konfirmasi Penerimaan')
@section('crumb', 'Beranda / Pembelian / ' . $po->number)

@section('content')
<x-page-header title="Konfirmasi Penerimaan" :subtitle="'Verifikasi barang yang tiba untuk ' . $po->number . ' dari ' . $po->supplier->name . '. Stok bertambah setelah Anda menyimpan.'">
    <a href="{{ route('purchases.show', $po) }}" class="btn btn-secondary">Kembali</a>
</x-page-header>

<form method="POST" action="{{ route('receipts.store', $po) }}" class="space-y-5">
    @csrf
    <section class="card p-6 grid sm:grid-cols-3 gap-5">
        <div>
            <label for="date" class="label">Tanggal terima <span class="text-red-600">*</span></label>
            <input id="date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="field">
        </div>
        <div>
            <label for="warehouse_name" class="label">Gudang tujuan <span class="text-red-600">*</span></label>
            <input id="warehouse_name" name="warehouse_name" list="warehouse-list" required maxlength="150" autocomplete="off"
                   value="{{ old('warehouse_name', $warehouses->count() === 1 ? $warehouses->first()->name : '') }}" class="field" placeholder="Pilih atau ketik nama gudang">
            <datalist id="warehouse-list">@foreach ($warehouses as $w)<option value="{{ $w->name }}">@endforeach</datalist>
            @can('quick.warehouse')<p class="text-[11px] text-slate-500 mt-1">Gudang belum ada? Ketik nama baru, otomatis tersimpan.</p>@endcan
        </div>
        <div>
            <label for="delivery_note" class="label">No. surat jalan supplier</label>
            <input id="delivery_note" name="delivery_note" value="{{ old('delivery_note') }}" class="field" placeholder="Opsional">
        </div>
    </section>

    <section class="card overflow-hidden" x-data="{fill(){ document.querySelectorAll('[data-out]').forEach(i => i.value = i.dataset.out) }}">
        <div class="flex justify-between items-center p-5 pb-3">
            <h2 class="font-bold">Barang Diterima</h2>
            <button type="button" class="btn btn-secondary" @click="fill()">Isi semua sesuai sisa pesanan</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[620px]">
                <thead><tr><th class="th">Barang</th><th class="th text-right">Dipesan</th><th class="th text-right">Sudah diterima</th><th class="th text-right">Sisa</th><th class="th w-44">Diterima sekarang</th></tr></thead>
                <tbody>
                @foreach ($po->items as $l)
                    <tr>
                        <td class="td font-semibold">{{ $l->item->name }}<div class="text-[11px] font-normal text-slate-500">{{ $l->item->code }}</div></td>
                        <td class="td text-right">{{ $fmt->qty($l->quantity) }} {{ $l->item->unit->symbol }}</td>
                        <td class="td text-right">{{ $fmt->qty($l->received_qty) }}</td>
                        <td class="td text-right font-bold">{{ $fmt->qty($l->outstanding()) }}</td>
                        <td class="td">
                            @if ($l->outstanding() > 0)
                                <div class="flex items-center gap-2">
                                    <input type="number" step="any" min="0" max="{{ $l->outstanding() }}" name="lines[{{ $l->id }}]" data-out="{{ $l->outstanding() }}"
                                           value="{{ old('lines.' . $l->id, $l->outstanding()) }}" class="field" aria-label="Jumlah diterima {{ $l->item->name }}">
                                    <span class="text-xs text-slate-500">{{ $l->item->unit->symbol }}</span>
                                </div>
                            @else
                                <span class="text-xs text-emerald-700 font-semibold">Lengkap</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="card p-6">
        <label for="notes" class="label">Catatan penerimaan</label>
        <textarea id="notes" name="notes" rows="3" class="field" placeholder="Mis. kondisi barang, kekurangan, atau selisih">{{ old('notes') }}</textarea>
    </section>

    <div class="flex justify-end gap-2">
        <a href="{{ route('purchases.show', $po) }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-success" onclick="return confirm('Verifikasi penerimaan dan tambahkan stok?')">Verifikasi &amp; Tambah Stok</button>
    </div>
</form>
@endsection
