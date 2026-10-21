@extends('layouts.app')
@section('title', 'Pembelian ' . $po->number)
@section('crumb', 'Beranda / Pembelian')

@section('content')
@php
    $flow = ['Draft', 'Menunggu Persetujuan', 'Disetujui', 'Diterima'];
    $rank = ['Draft' => 0, 'Menunggu Persetujuan' => 1, 'Disetujui' => 2, 'Diterima Sebagian' => 2, 'Diterima' => 3];
    $current = $rank[$po->status] ?? -1;
@endphp

<x-page-header :title="$po->number" subtitle="Detail pembelian, progres persetujuan, dan riwayat penerimaan barang.">
    <a href="{{ route('purchases.index') }}" class="btn btn-secondary">Kembali</a>
    <a href="{{ route('purchases.print', $po) }}" target="_blank" class="btn btn-secondary"><i data-lucide="printer" class="w-4 h-4"></i>Cetak</a>
    @can('purchase.create')
        @if ($po->isEditable())<a href="{{ route('purchases.edit', $po) }}" class="btn btn-secondary"><i data-lucide="pencil" class="w-4 h-4"></i>Edit Draft</a>@endif
    @endcan
</x-page-header>

{{-- Progres status --}}
<div class="card p-4 mb-5">
    @if ($current >= 0)
        <ol class="flex flex-wrap gap-2 items-center text-xs">
            @foreach ($flow as $i => $step)
                <li class="px-3 py-2 rounded-lg font-semibold {{ $i <= $current ? 'bg-brand text-white' : 'bg-slate-100 text-slate-500' }}">
                    {{ $i + 1 }}. {{ $step === 'Diterima' && $po->status === 'Diterima Sebagian' ? 'Diterima Sebagian' : $step }}
                </li>
            @endforeach
        </ol>
    @else
        <div class="flex items-center gap-3"><x-badge :status="$po->status" />
            @if ($po->rejection_reason)<span class="text-sm text-slate-600">Alasan: {{ $po->rejection_reason }}</span>@endif</div>
    @endif
</div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <section class="card p-6">
            <div class="grid sm:grid-cols-2 gap-5 text-sm">
                <div><div class="text-xs text-slate-500">Supplier</div><div class="font-bold mt-1">{{ $po->supplier->name }}</div>
                    <div class="text-xs text-slate-500">{{ $po->supplier->contact_person }} {{ $po->supplier->phone ? '· ' . $po->supplier->phone : '' }}</div></div>
                <div><div class="text-xs text-slate-500">Tanggal pembelian</div><div class="font-bold mt-1">{{ $fmt->date($po->date, 'd F Y') }}</div></div>
                <div><div class="text-xs text-slate-500">Status</div><div class="mt-1"><x-badge :status="$po->status" /></div></div>
                <div><div class="text-xs text-slate-500">Total harga</div><div class="font-extrabold text-lg text-brand mt-0.5">{{ $fmt->money($po->total) }}</div></div>
                <div><div class="text-xs text-slate-500">Dibuat oleh</div><div class="mt-1">{{ $po->creator?->name ?? '-' }}</div></div>
                <div><div class="text-xs text-slate-500">Disetujui oleh</div><div class="mt-1">{{ $po->approver?->name ?? '-' }} @if ($po->approved_at)<span class="text-xs text-slate-500">· {{ $fmt->datetime($po->approved_at) }}</span>@endif</div></div>
            </div>
            @if ($po->notes)<p class="text-sm text-slate-600 mt-5 pt-4 border-t border-slate-100"><strong>Catatan:</strong> {{ $po->notes }}</p>@endif
        </section>

        <section class="card overflow-hidden">
            <h2 class="font-bold p-5 pb-3">Rincian Barang</h2>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px]">
                    <thead><tr><th class="th">Barang</th><th class="th text-right">Dipesan</th><th class="th text-right">Diterima</th><th class="th text-right">Sisa</th><th class="th text-right">Harga</th><th class="th text-right">Subtotal</th></tr></thead>
                    <tbody>
                    @foreach ($po->items as $l)
                        <tr>
                            <td class="td font-semibold">{{ $l->item->name }}<div class="text-[11px] font-normal text-slate-500">{{ $l->item->code }}</div></td>
                            <td class="td text-right">{{ $fmt->qty($l->quantity) }} {{ $l->item->unit->symbol }}</td>
                            <td class="td text-right">{{ $fmt->qty($l->received_qty) }}</td>
                            <td class="td text-right {{ $l->outstanding() > 0 && in_array($po->status, ['Diterima Sebagian']) ? 'text-amber-700 font-bold' : '' }}">{{ $fmt->qty($l->outstanding()) }}</td>
                            <td class="td text-right">{{ $fmt->money($l->price) }}</td>
                            <td class="td text-right font-semibold">{{ $fmt->money($l->subtotal()) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="5" class="td text-right font-bold">Total</td><td class="td text-right font-extrabold">{{ $fmt->money($po->total) }}</td></tr></tfoot>
                </table>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="font-bold mb-3">Riwayat Penerimaan</h2>
            @forelse ($po->receipts as $r)
                <a href="{{ route('receipts.show', $r) }}" class="flex flex-wrap justify-between gap-2 py-3 border-t border-slate-100 text-sm hover:bg-slate-50 -mx-2 px-2 rounded">
                    <span><strong class="text-brand">{{ $r->number }}</strong> · {{ $r->warehouse->name }}
                        <span class="text-xs text-slate-500">· {{ $r->items->map(fn ($i) => $i->item->name . ' ' . $fmt->qty($i->quantity))->implode(', ') }}</span></span>
                    <span class="text-xs text-slate-500">{{ $fmt->date($r->date) }} · {{ $r->receiver?->name }}</span>
                </a>
            @empty
                <p class="text-sm text-slate-500">Belum ada penerimaan barang.</p>
            @endforelse
        </section>
    </div>

    {{-- Tindakan --}}
    <aside class="space-y-4" x-data="{reject:false}">
        <section class="card p-5">
            <h2 class="font-bold mb-3 text-sm">Tindakan</h2>
            <div class="space-y-2">
                @can('purchase.create')
                    @if ($po->status === 'Draft')
                        <form method="POST" action="{{ route('purchases.submit', $po) }}">@csrf<button class="btn btn-primary w-full">Ajukan Persetujuan</button></form>
                    @endif
                @endcan
                @can('purchase.approve')
                    @if ($po->status === 'Menunggu Persetujuan')
                        <form method="POST" action="{{ route('purchases.approve', $po) }}" onsubmit="return confirm('Setujui pembelian {{ $po->number }}?')">@csrf<button class="btn btn-success w-full">Setujui</button></form>
                        <button type="button" class="btn btn-danger w-full" @click="reject=!reject">Tolak</button>
                        <form x-show="reject" x-cloak method="POST" action="{{ route('purchases.reject', $po) }}" class="space-y-2">@csrf
                            <textarea name="rejection_reason" required rows="3" class="field" placeholder="Alasan penolakan"></textarea>
                            <button class="btn btn-danger w-full">Kirim Penolakan</button>
                        </form>
                    @endif
                @endcan
                @can('purchase.receive')
                    @if ($po->canReceive())
                        <a href="{{ route('receipts.create', $po) }}" class="btn btn-success w-full"><i data-lucide="package-check" class="w-4 h-4"></i>Konfirmasi Penerimaan</a>
                    @endif
                @endcan
                @can('purchase.create')
                    @if ($po->canCancel())
                        <form method="POST" action="{{ route('purchases.cancel', $po) }}" onsubmit="return confirm('Batalkan pembelian ini?')">@csrf<button class="btn btn-danger w-full">Batalkan Pembelian</button></form>
                    @endif
                @endcan
                @if (in_array($po->status, ['Diterima', 'Dibatalkan', 'Ditolak']))
                    <p class="text-xs text-slate-500">Status final — tidak ada tindakan yang tersedia.</p>
                @endif
            </div>
        </section>
        <p class="text-xs text-slate-500 leading-relaxed px-1">Stok hanya bertambah setelah penerimaan diverifikasi gudang. Penerimaan sebagian diperbolehkan; sisa pesanan tetap terbuka.</p>
    </aside>
</div>
@endsection
