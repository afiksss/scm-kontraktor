@extends('layouts.app')
@section('title', 'Detail Barang · ' . $item->code)
@section('crumb', 'Beranda / Inventaris')

@section('content')
<x-page-header :title="$item->name" :subtitle="$item->code . ' • ' . $item->category->name . ' • satuan ' . $item->unit->name">
    <a href="{{ route('inventory.index') }}" class="btn btn-secondary">Kembali</a>
</x-page-header>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <x-kpi label="Stok Tersedia" :value="$fmt->qty($total) . ' ' . $item->unit->symbol" :hint="$status" icon="package" :tone="$total < $item->min_stock ? 'red' : 'green'" />
    <x-kpi label="Stok Minimum" :value="$fmt->qty($item->min_stock) . ' ' . $item->unit->symbol" hint="Batas pengadaan" icon="triangle-alert" tone="orange" />
    <x-kpi label="Masuk 30 Hari" :value="$fmt->qty($in30) . ' ' . $item->unit->symbol" hint="Penerimaan, mutasi masuk, penyesuaian" icon="arrow-down-to-line" tone="green" />
    <x-kpi label="Keluar 30 Hari" :value="$fmt->qty($out30) . ' ' . $item->unit->symbol" hint="Pemakaian, mutasi keluar, penyesuaian" icon="arrow-up-from-line" />
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <section class="card overflow-hidden">
            <h2 class="font-bold p-5 pb-3">Saldo per Gudang</h2>
            <table class="w-full">
                <thead><tr><th class="th">Gudang</th><th class="th text-right">Saldo</th></tr></thead>
                <tbody>
                @forelse ($item->stocks->sortByDesc('quantity') as $s)
                    <tr><td class="td">{{ $s->warehouse->name }}</td><td class="td text-right font-semibold">{{ $fmt->qty($s->quantity) }} {{ $item->unit->symbol }}</td></tr>
                @empty
                    <x-empty cols="2" message="Belum ada stok di gudang mana pun" />
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="card overflow-hidden">
            <div class="flex flex-wrap justify-between items-center gap-3 p-5 pb-3">
                <h2 class="font-bold">Riwayat Pergerakan</h2>
                <form method="GET">
                    <select name="warehouse_id" class="field !min-h-[34px] !py-1" onchange="this.form.submit()" aria-label="Filter gudang">
                        <option value="">Semua gudang</option>
                        @foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
                    </select>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px]">
                    <thead><tr><th class="th">Tanggal</th><th class="th">Nomor Transaksi</th><th class="th">Jenis</th><th class="th">Gudang</th><th class="th text-right">Masuk</th><th class="th text-right">Keluar</th><th class="th text-right">Saldo</th><th class="th">Pengguna</th></tr></thead>
                    <tbody>
                    @forelse ($movements as $m)
                        <tr>
                            <td class="td whitespace-nowrap">{{ $fmt->datetime($m->created_at) }}</td>
                            <td class="td font-semibold">{{ $m->reference_number ?? '-' }}</td>
                            <td class="td"><x-badge :status="$m->label()" /></td>
                            <td class="td text-xs">{{ $m->warehouse->name }}</td>
                            <td class="td text-right">{{ $m->qty_in ? $fmt->qty($m->qty_in) : '-' }}</td>
                            <td class="td text-right">{{ $m->qty_out ? $fmt->qty($m->qty_out) : '-' }}</td>
                            <td class="td text-right font-bold">{{ $fmt->qty($m->balance) }}</td>
                            <td class="td text-xs">{{ $m->user?->name ?? '-' }}</td>
                        </tr>
                    @empty<x-empty cols="8" message="Belum ada pergerakan" />@endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $movements->links() }}</div>
        </section>
    </div>

    <aside class="space-y-5">
        @can('inventory.manage')
            <section class="card p-5">
                <h2 class="font-bold text-sm mb-1">Penyesuaian Stok (Stok Opname)</h2>
                <p class="text-xs text-slate-500 mb-4 leading-relaxed">Masukkan jumlah hasil hitung fisik. Selisih dicatat pada riwayat pergerakan sebagai penyesuaian.</p>
                <form method="POST" action="{{ route('inventory.adjust', $item) }}" class="space-y-3" onsubmit="return confirm('Simpan penyesuaian stok?')">
                    @csrf
                    <div>
                        <label for="adj-wh" class="label">Gudang</label>
                        <select id="adj-wh" name="warehouse_id" required class="field">
                            @foreach ($warehouses as $w)<option value="{{ $w->id }}" @selected(old('warehouse_id') == $w->id)>{{ $w->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label for="adj-qty" class="label">Jumlah hasil hitung ({{ $item->unit->symbol }})</label>
                        <input id="adj-qty" name="new_quantity" type="number" step="any" min="0" required value="{{ old('new_quantity') }}" class="field">
                    </div>
                    <div>
                        <label for="adj-note" class="label">Alasan penyesuaian</label>
                        <input id="adj-note" name="note" required maxlength="200" value="{{ old('note') }}" class="field" placeholder="Mis. hasil opname Oktober">
                    </div>
                    <button class="btn btn-primary w-full">Simpan Penyesuaian</button>
                </form>
            </section>
        @endcan
        <section class="card p-5 text-xs text-slate-600 space-y-1.5">
            <div><span class="text-slate-500">Harga acuan:</span> <strong>{{ $fmt->money($item->price) }}</strong></div>
            <div><span class="text-slate-500">Nilai stok:</span> <strong>{{ $fmt->money($total * $item->price) }}</strong></div>
            @if ($item->description)<div class="pt-2 border-t border-slate-100">{{ $item->description }}</div>@endif
        </section>
    </aside>
</div>
@endsection
