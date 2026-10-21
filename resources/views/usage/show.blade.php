@extends('layouts.app')
@section('title', 'Permintaan ' . $req->number)
@section('crumb', 'Beranda / Pemakaian Barang')

@section('content')
@php
    $flow = ['Menunggu Persetujuan', 'Disetujui', 'Diproses Gudang', 'Selesai'];
    $current = array_search($req->status, $flow, true);
    $canApproveNow = $req->status === 'Menunggu Persetujuan' && auth()->user()->can('usage.approve');
    $canReleaseNow = in_array($req->status, ['Disetujui', 'Diproses Gudang']) && auth()->user()->can('usage.release');
@endphp

<x-page-header :title="$req->number" subtitle="Detail permintaan barang proyek dan alur persetujuan hingga pengeluaran gudang.">
    <a href="{{ route('usage.index') }}" class="btn btn-secondary">Kembali</a>
    <a href="{{ route('usage.print', $req) }}" target="_blank" class="btn btn-secondary"><i data-lucide="printer" class="w-4 h-4"></i>
        {{ $req->status === 'Selesai' ? 'Cetak Bukti Pengeluaran' : 'Cetak' }}</a>
</x-page-header>

<div class="card p-4 mb-5">
    @if ($current !== false)
        <ol class="flex flex-wrap gap-2 items-center text-xs">
            @foreach ($flow as $i => $step)
                <li class="px-3 py-2 rounded-lg font-semibold {{ $i <= $current ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500' }}">{{ $i + 1 }}. {{ $step }}</li>
            @endforeach
        </ol>
    @else
        <div class="flex flex-wrap items-center gap-3"><x-badge :status="$req->status" />
            @if ($req->rejection_reason)<span class="text-sm text-slate-600">Alasan: {{ $req->rejection_reason }}</span>@endif</div>
    @endif
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <section class="card p-6">
            <div class="grid sm:grid-cols-2 gap-5 text-sm">
                <div><div class="text-xs text-slate-500">Proyek</div><div class="font-bold mt-1">{{ $req->project->name }}</div></div>
                <div><div class="text-xs text-slate-500">Tanggal</div><div class="font-bold mt-1">{{ $fmt->date($req->date, 'd F Y') }}</div></div>
                <div><div class="text-xs text-slate-500">Pemohon</div><div class="mt-1">{{ $req->requester?->name ?? '-' }}</div></div>
                <div><div class="text-xs text-slate-500">Gudang asal</div><div class="mt-1">{{ $req->warehouse->name }}</div></div>
                <div class="sm:col-span-2"><div class="text-xs text-slate-500">Keperluan</div><div class="mt-1">{{ $req->purpose }}</div></div>
                <div><div class="text-xs text-slate-500">Disetujui oleh</div><div class="mt-1">{{ $req->approver?->name ?? '-' }}</div></div>
                <div><div class="text-xs text-slate-500">Dikeluarkan oleh</div><div class="mt-1">{{ $req->releaser?->name ?? '-' }} @if ($req->released_at)<span class="text-xs text-slate-500">· {{ $fmt->datetime($req->released_at) }}</span>@endif</div></div>
            </div>
            @if ($req->notes)<p class="text-sm text-slate-600 mt-5 pt-4 border-t border-slate-100"><strong>Catatan:</strong> {{ $req->notes }}</p>@endif
        </section>

        {{-- Tabel barang sekaligus form persetujuan / pengeluaran --}}
        <form method="POST" id="line-form"
              action="{{ $canApproveNow ? route('usage.approve', $req) : ($canReleaseNow ? route('usage.release', $req) : '#') }}"
              @if ($canApproveNow) onsubmit="return confirm('Setujui permintaan sesuai jumlah yang diisi?')" @endif
              @if ($canReleaseNow) onsubmit="return confirm('Konfirmasi pengeluaran dan kurangi stok?')" @endif>
            @csrf
            <section class="card overflow-hidden">
                <h2 class="font-bold p-5 pb-3">Rincian Barang</h2>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px]">
                        <thead><tr><th class="th">Barang</th><th class="th text-right">Diminta</th><th class="th text-right">Stok gudang</th>
                            <th class="th text-right {{ $canApproveNow ? 'w-40' : '' }}">Disetujui</th><th class="th text-right {{ $canReleaseNow ? 'w-40' : '' }}">Dikeluarkan</th></tr></thead>
                        <tbody>
                        @foreach ($req->items as $l)
                            @php $stock = (float) ($available[$l->item_id] ?? 0); @endphp
                            <tr>
                                <td class="td font-semibold">{{ $l->item->name }}<div class="text-[11px] font-normal text-slate-500">{{ $l->item->code }}</div></td>
                                <td class="td text-right">{{ $fmt->qty($l->qty_requested) }} {{ $l->item->unit->symbol }}</td>
                                <td class="td text-right {{ $stock < max($l->qty_approved, 0.0001) && $req->status !== 'Selesai' && $l->qty_requested > $stock ? 'text-red-600 font-bold' : '' }}">{{ $fmt->qty($stock) }}</td>
                                <td class="td text-right">
                                    @if ($canApproveNow)
                                        <input type="number" step="any" min="0" max="{{ $l->qty_requested }}" name="approved[{{ $l->id }}]" value="{{ $l->qty_requested }}" class="field text-right" aria-label="Jumlah disetujui {{ $l->item->name }}">
                                    @else
                                        {{ $req->status === 'Menunggu Persetujuan' || $l->qty_approved == 0 && in_array($req->status, ['Ditolak', 'Dibatalkan']) ? '-' : $fmt->qty($l->qty_approved) }}
                                    @endif
                                </td>
                                <td class="td text-right">
                                    @if ($canReleaseNow)
                                        <input type="number" step="any" min="0" max="{{ min($l->qty_approved, max($stock, 0)) }}" name="released[{{ $l->id }}]"
                                               value="{{ min($l->qty_approved, max($stock, 0)) }}" class="field text-right" aria-label="Jumlah dikeluarkan {{ $l->item->name }}">
                                    @else
                                        {{ $req->status === 'Selesai' ? $fmt->qty($l->qty_released) . ' ' . $l->item->unit->symbol : '-' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($canApproveNow || $canReleaseNow)
                    <div class="p-5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-xs text-slate-500 max-w-md">
                            @if ($canApproveNow) Ubah jumlah bila hanya sebagian yang disetujui. @else Jumlah awal disesuaikan dengan stok gudang; ubah bila serah-terima aktual berbeda. @endif
                        </p>
                        <button class="btn btn-success">{{ $canApproveNow ? 'Setujui Permintaan' : 'Konfirmasi Pengeluaran' }}</button>
                    </div>
                @endif
            </section>
        </form>
    </div>

    <aside class="space-y-4" x-data="{reject:false}">
        <section class="card p-5">
            <h2 class="font-bold mb-3 text-sm">Tindakan</h2>
            <div class="space-y-2">
                @if ($canApproveNow)
                    <p class="text-xs text-slate-500">Isi jumlah disetujui pada tabel lalu tekan <strong>Setujui Permintaan</strong>, atau tolak di bawah.</p>
                    <button type="button" class="btn btn-danger w-full" @click="reject=!reject">Tolak Permintaan</button>
                    <form x-show="reject" x-cloak method="POST" action="{{ route('usage.reject', $req) }}" class="space-y-2">@csrf
                        <textarea name="rejection_reason" required rows="3" class="field" placeholder="Alasan penolakan"></textarea>
                        <button class="btn btn-danger w-full">Kirim Penolakan</button>
                    </form>
                @endif

                @can('usage.release')
                    @if ($req->status === 'Disetujui')
                        <form method="POST" action="{{ route('usage.process', $req) }}">@csrf<button class="btn btn-primary w-full">Proses di Gudang</button></form>
                        <p class="text-xs text-slate-500">Tandai bahwa gudang mulai menyiapkan barang. Anda juga dapat langsung mengonfirmasi pengeluaran.</p>
                    @endif
                @endcan

                @can('usage.create')
                    @if ($req->status === 'Menunggu Persetujuan' && ($req->requester_id === auth()->id() || auth()->user()->can('usage.approve')))
                        <form method="POST" action="{{ route('usage.cancel', $req) }}" onsubmit="return confirm('Batalkan permintaan ini?')">@csrf<button class="btn btn-secondary w-full">Batalkan Permintaan</button></form>
                    @endif
                @endcan

                @if (in_array($req->status, ['Selesai', 'Ditolak', 'Dibatalkan']))
                    <p class="text-xs text-slate-500">Status final — tidak ada tindakan yang tersedia.</p>
                @elseif (!$canApproveNow && !$canReleaseNow && $req->status === 'Menunggu Persetujuan')
                    <p class="text-xs text-slate-500">Menunggu keputusan pimpinan.</p>
                @endif
            </div>
        </section>
        <p class="text-xs text-slate-500 leading-relaxed px-1">Stok gudang berkurang hanya setelah pengeluaran dikonfirmasi petugas gudang, dan tercatat di riwayat pergerakan barang.</p>
    </aside>
</div>
@endsection
