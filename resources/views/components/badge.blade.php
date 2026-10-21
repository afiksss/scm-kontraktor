@props(['status'])
@php
    $tone = match (true) {
        in_array($status, ['Diterima', 'Disetujui', 'Selesai', 'Tersedia', 'Aktif', 'Masuk', 'Mutasi Masuk']) => 'bg-emerald-100 text-emerald-800',
        in_array($status, ['Menunggu Persetujuan', 'Diproses Gudang', 'Stok Menipis', 'Draft', 'Diterima Sebagian', 'Penyesuaian']) => 'bg-amber-100 text-amber-800',
        in_array($status, ['Ditolak', 'Dibatalkan', 'Stok Habis', 'Nonaktif', 'Keluar', 'Mutasi Keluar']) => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold whitespace-nowrap $tone"]) }}>{{ $status }}</span>
