@extends('layouts.app')
@section('title', 'Hak Akses')
@section('crumb', 'Beranda / Hak Akses')

@section('content')
<x-page-header title="Hak Akses Per Role" subtitle="Setiap aktor hanya melihat dan melakukan hal yang menjadi tugasnya. Pembuat, penyetuju, dan pelaksana sengaja dipisahkan agar transaksi terkontrol.">
    @can('roles.manage')
        <a href="{{ route('roles.edit') }}" class="btn btn-primary">Ubah Pengaturan</a>
    @endcan
</x-page-header>

<section class="grid sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
    @foreach ($roles as $key => $label)
        <div class="card p-4 {{ $key === $mine ? 'ring-2 ring-brand' : '' }}">
            <div class="flex items-center justify-between gap-2">
                <strong class="text-sm">{{ $label }}</strong>
                @if ($key === $mine)<span class="text-[10px] font-bold text-brand bg-blue-50 rounded-full px-2 py-0.5">Role Anda</span>@endif
            </div>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $info[$key] ?? '' }}</p>
        </div>
    @endforeach
</section>

<section class="card p-5 mb-6">
    <h2 class="font-bold text-sm mb-3">Alur barang: dari pembelian sampai dipakai</h2>
    <ol class="grid md:grid-cols-5 gap-3 text-xs">
        @foreach ([
            ['1', 'Purchasing', 'Membuat pembelian. Supplier & barang cukup diketik; yang belum ada otomatis dibuat.'],
            ['2', 'Pimpinan', 'Menyetujui atau menolak pembelian.'],
            ['3', 'Petugas Gudang', 'Memverifikasi barang tiba → stok bertambah. Gudang baru bisa diketik langsung.'],
            ['4', 'Manajer Proyek', 'Meminta barang untuk proyek (proyek baru bisa diketik langsung).'],
            ['5', 'Pimpinan → Gudang', 'Pimpinan menyetujui, gudang mengeluarkan barang → stok berkurang.'],
        ] as [$n, $who, $what])
            <li class="rounded-xl bg-slate-50 border border-slate-100 p-3">
                <div class="flex items-center gap-2 mb-1"><span class="w-5 h-5 rounded-full bg-brand text-white text-[10px] font-bold flex items-center justify-center">{{ $n }}</span><strong>{{ $who }}</strong></div>
                <span class="text-slate-500 leading-relaxed">{{ $what }}</span>
            </li>
        @endforeach
    </ol>
</section>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px]">
            <thead>
                <tr>
                    <th class="th">Hak akses</th>
                    @foreach ($roles as $key => $label)
                        <th class="th text-center {{ $key === $mine ? '!bg-blue-50 !text-brand' : '' }}">{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach ($matrix as $group => $rows)
                <tr><td colspan="{{ count($roles) + 1 }}" class="px-4 py-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 bg-slate-50/60 border-t border-slate-100">{{ $group }}</td></tr>
                @foreach ($rows as $row)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td">{{ $row['label'] }}</td>
                        @foreach ($roles as $key => $label)
                            <td class="td text-center {{ $key === $mine ? 'bg-blue-50/40' : '' }}">
                                @if (in_array($key, $row['roles'], true))
                                    <span class="text-emerald-600 font-bold" title="Diizinkan">✓</span>
                                @else
                                    <span class="text-slate-300" title="Tidak diizinkan">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<p class="text-xs text-slate-500 mt-4 max-w-3xl leading-relaxed">Pengaturan ini berlaku langsung pada menu, tombol, dan pemeriksaan di server. Hanya admin yang dapat mengubahnya.</p>
@endsection
