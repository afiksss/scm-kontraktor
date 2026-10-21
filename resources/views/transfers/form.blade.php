@extends('layouts.app')
@section('title', 'Mutasi Barang Antargudang')
@section('crumb', 'Beranda / Inventaris')

@section('content')
<x-page-header title="Mutasi Barang Antargudang" subtitle="Mutasi langsung berlaku saat dikonfirmasi: stok gudang asal berkurang dan gudang tujuan bertambah.">
    <a href="{{ route('transfers.index') }}" class="btn btn-secondary">Kembali</a>
</x-page-header>

<form method="POST" action="{{ route('transfers.store') }}" x-data="lineForm(@js($itemOptions), @js($lines), false, @js($stockMap))" x-init="warehouse = '{{ old('from_warehouse_id') }}'" class="space-y-5">
    @csrf
    <section class="card p-6 grid sm:grid-cols-3 gap-5">
        <div>
            <label for="date" class="label">Tanggal <span class="text-red-600">*</span></label>
            <input id="date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="field">
        </div>
        <div>
            <label for="from" class="label">Gudang asal <span class="text-red-600">*</span></label>
            <select id="from" name="from_warehouse_id" x-model="warehouse" required class="field">
                <option value="">Pilih gudang asal</option>
                @foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(old('from_warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="to" class="label">Gudang tujuan <span class="text-red-600">*</span></label>
            <select id="to" name="to_warehouse_id" required class="field">
                <option value="">Pilih gudang tujuan</option>
                @foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(old('to_warehouse_id') == $w->id) :disabled="warehouse == {{ $w->id }}">{{ $w->name }}</option>@endforeach
            </select>
        </div>
    </section>

    <section class="card p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">Daftar Barang</h2>
            <button type="button" class="btn btn-secondary" @click="add()"><i data-lucide="plus" class="w-4 h-4"></i>Tambah Baris</button>
        </div>
        <template x-for="(l, i) in lines" :key="l.key">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start border-t border-slate-100 py-3">
                <div class="sm:col-span-6">
                    <select :name="`items[${i}][item_id]`" x-model="l.item_id" required class="field" aria-label="Nama barang">
                        <option value="">Pilih barang</option>
                        <template x-for="o in options" :key="o.id">
                            <option :value="o.id" :disabled="taken(o.id, l)" :selected="o.id == l.item_id" x-text="o.name + ' (' + o.unit + ')'"></option>
                        </template>
                    </select>
                </div>
                <div class="sm:col-span-3 flex items-center gap-2">
                    <input type="number" step="any" min="0.01" :name="`items[${i}][quantity]`" x-model.number="l.quantity" required class="field" aria-label="Jumlah">
                    <span class="text-xs text-slate-500 w-10" x-text="unit(l)"></span>
                </div>
                <div class="sm:col-span-2 pt-2.5 text-xs">
                    <template x-if="available(l) !== null">
                        <span :class="short(l) ? 'text-red-600 font-bold' : 'text-slate-500'" x-text="'Tersedia: ' + available(l)"></span>
                    </template>
                </div>
                <div class="sm:text-right"><button type="button" class="icon-btn text-red-600" @click="remove(i)" :disabled="lines.length === 1" aria-label="Hapus baris">✕</button></div>
            </div>
        </template>
    </section>

    <section class="card p-6">
        <label for="notes" class="label">Catatan</label>
        <textarea id="notes" name="notes" rows="3" class="field" placeholder="Opsional">{{ old('notes') }}</textarea>
    </section>

    <div class="flex justify-end gap-2">
        <a href="{{ route('transfers.index') }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary" onclick="return confirm('Konfirmasi mutasi barang?')">Konfirmasi Mutasi</button>
    </div>
</form>
@endsection

@include('partials.line-form-script')
