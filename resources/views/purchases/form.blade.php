@extends('layouts.app')
@php $title = $po ? 'Edit Pembelian' : 'Tambah Pembelian'; @endphp
@section('title', $title)
@section('crumb', 'Beranda / Pembelian')

@section('content')
<x-page-header :title="$title" subtitle="Ketik atau pilih supplier dan barang. Yang belum ada otomatis ditambahkan ke Data Master.">
    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Kembali</a>
</x-page-header>

<form method="POST" action="{{ $po ? route('purchases.update', $po) : route('purchases.store') }}"
      x-data="poForm(@js($itemOptions), @js($lines), @js(auth()->user()->can('quick.item')))" class="space-y-5">
    @csrf @if ($po) @method('PUT') @endif

    <section class="card p-6 grid sm:grid-cols-3 gap-5">
        <div>
            <label class="label">Nomor otomatis</label>
            <input class="field" value="{{ $nextNumber }}" readonly>
        </div>
        <div>
            <label for="date" class="label">Tanggal <span class="text-red-600">*</span></label>
            <input id="date" type="date" name="date" value="{{ old('date', $po?->date?->toDateString() ?? now()->toDateString()) }}" required class="field">
        </div>
        <div>
            <label for="supplier_name" class="label">Supplier <span class="text-red-600">*</span></label>
            <input id="supplier_name" name="supplier_name" list="supplier-list" required maxlength="150" autocomplete="off"
                   value="{{ old('supplier_name', $po?->supplier?->name) }}" class="field" placeholder="Pilih atau ketik nama supplier">
            <datalist id="supplier-list">@foreach ($suppliers as $s)<option value="{{ $s->name }}">@endforeach</datalist>
            @can('quick.supplier')
                <p class="text-[11px] text-slate-500 mt-1">Belum ada di daftar? Cukup ketik nama baru, otomatis tersimpan.</p>
            @endcan
        </div>
    </section>

    <section class="card p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="font-bold">Daftar Barang</h2>
            <button type="button" class="btn btn-secondary" @click="add()"><i data-lucide="plus" class="w-4 h-4"></i>Tambah Baris</button>
        </div>

        <div class="hidden sm:grid grid-cols-12 gap-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-500 px-1 pb-2">
            <div class="col-span-4">Nama barang</div><div class="col-span-1">Satuan</div><div class="col-span-2">Jumlah</div><div class="col-span-2">Harga satuan (Rp)</div><div class="col-span-2 text-right">Subtotal</div><div></div>
        </div>
        <datalist id="item-list">@foreach ($itemOptions as $o)<option value="{{ $o['name'] }}">@endforeach</datalist>
        <datalist id="unit-list">@foreach ($units as $u)<option value="{{ $u }}">@endforeach</datalist>

        <template x-for="(l, i) in lines" :key="l.key">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start border-t border-slate-100 py-3">
                <div class="sm:col-span-4">
                    <input :name="`items[${i}][name]`" x-model="l.name" @input="sync(l)" @change="sync(l)" list="item-list" required maxlength="150" autocomplete="off" class="field" placeholder="Pilih atau ketik nama barang" aria-label="Nama barang">
                    <p x-show="isNew(l) && canNew" x-cloak class="text-[11px] text-emerald-700 mt-1">Barang baru — akan ditambahkan ke Data Master.</p>
                    <p x-show="isNew(l) && !canNew" x-cloak class="text-[11px] text-red-600 mt-1">Barang tidak ada di daftar dan role Anda tidak boleh menambah barang baru.</p>
                </div>
                <div class="sm:col-span-1">
                    <input :name="`items[${i}][unit]`" x-model="l.unit" list="unit-list" maxlength="30" :readonly="!!match(l)" :required="isNew(l)" class="field" :class="match(l) ? 'bg-slate-50' : ''" placeholder="Satuan" aria-label="Satuan">
                </div>
                <div class="sm:col-span-2">
                    <input type="number" step="any" min="0.01" :name="`items[${i}][quantity]`" x-model.number="l.quantity" required class="field" aria-label="Jumlah">
                </div>
                <div class="sm:col-span-2">
                    <input type="number" step="any" min="0" :name="`items[${i}][price]`" x-model.number="l.price" required class="field" aria-label="Harga satuan">
                </div>
                <div class="sm:col-span-2 sm:text-right pt-2.5 text-[13px] font-semibold" x-text="rp(sub(l))"></div>
                <div class="sm:text-right">
                    <button type="button" class="icon-btn text-red-600" @click="remove(i)" :disabled="lines.length === 1" aria-label="Hapus baris">✕</button>
                </div>
            </div>
        </template>
    </section>

    <div class="grid sm:grid-cols-2 gap-5">
        <section class="card p-6">
            <label for="notes" class="label">Catatan</label>
            <textarea id="notes" name="notes" rows="4" class="field" placeholder="Catatan tambahan (opsional)">{{ old('notes', $po?->notes) }}</textarea>
        </section>
        <section class="card p-6 bg-blue-50/60 border-blue-100">
            <div class="text-xs font-bold text-slate-600">Total Pembelian</div>
            <div class="text-3xl font-extrabold text-brand mt-2" x-text="rp(total())"></div>
            <p class="text-xs text-slate-500 mt-3 leading-relaxed">Stok tidak berubah saat pembelian dibuat atau disetujui. Stok bertambah setelah gudang memverifikasi penerimaan barang.</p>
        </section>
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Batal</a>
        <button name="action" value="draft" class="btn btn-secondary">Simpan sebagai Draft</button>
        <button name="action" value="submit" class="btn btn-primary">Ajukan Persetujuan</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    /** Baris barang pembelian: nama diketik/dipilih; bila cocok dengan master -> satuan & harga terisi otomatis. */
    function poForm(options, initial, canNew) {
        const blank = () => ({key: Math.random(), name: '', unit: '', quantity: 1, price: 0});
        return {
            options, canNew,
            lines: (initial && initial.length ? initial : [blank()]).map(l => ({
                key: Math.random(), name: l.name ?? '', unit: l.unit ?? '', quantity: l.quantity ?? 1, price: l.price ?? 0,
            })),
            match(l) { const n = (l.name || '').trim().toLowerCase(); return n ? this.options.find(o => o.name.toLowerCase() === n) : null; },
            isNew(l) { return !!(l.name || '').trim() && !this.match(l); },
            sync(l) {
                const o = this.match(l);
                if (o) { l.unit = o.unit; if (!Number(l.price)) l.price = o.price; }
            },
            add() { this.lines.push(blank()); },
            remove(i) { if (this.lines.length > 1) this.lines.splice(i, 1); },
            sub(l) { return (Number(l.quantity) || 0) * (Number(l.price) || 0); },
            total() { return this.lines.reduce((s, l) => s + this.sub(l), 0); },
            rp(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); },
        };
    }
</script>
@endpush
