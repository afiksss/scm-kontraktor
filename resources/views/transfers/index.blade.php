@extends('layouts.app')
@section('title', 'Mutasi Antargudang')
@section('crumb', 'Beranda / Inventaris')

@section('content')
<x-page-header title="Mutasi Antargudang" subtitle="Pemindahan stok antar gudang. Total stok perusahaan tidak berubah; hanya lokasinya.">
    @can('inventory.manage')<a href="{{ route('transfers.create') }}" class="btn btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Mutasi Baru</a>@endcan
</x-page-header>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px]">
            <thead><tr><th class="th">Nomor</th><th class="th">Tanggal</th><th class="th">Dari</th><th class="th">Ke</th><th class="th">Barang</th><th class="th">Petugas</th></tr></thead>
            <tbody>
            @forelse ($transfers as $t)
                <tr class="hover:bg-slate-50/70">
                    <td class="td font-bold text-brand">{{ $t->number }}</td>
                    <td class="td">{{ $fmt->date($t->date) }}</td>
                    <td class="td">{{ $t->from->name }}</td>
                    <td class="td">{{ $t->to->name }}</td>
                    <td class="td text-xs">@foreach ($t->items as $i)<div>{{ $i->item->name }} — <strong>{{ $fmt->qty($i->quantity) }} {{ $i->item->unit->symbol }}</strong></div>@endforeach</td>
                    <td class="td">{{ $t->user?->name ?? '-' }}</td>
                </tr>
            @empty<x-empty cols="6" message="Belum ada mutasi" />@endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $transfers->links() }}</div>
@endsection
