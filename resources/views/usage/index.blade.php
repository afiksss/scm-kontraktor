@extends('layouts.app')
@section('title', 'Pemakaian Barang')
@section('crumb', 'Beranda / Pemakaian Barang')

@section('content')
<x-page-header title="Pemakaian Barang" subtitle="Alur: permintaan proyek → persetujuan pimpinan → proses gudang → konfirmasi pengeluaran. Stok hanya berkurang saat pengeluaran dikonfirmasi gudang.">
    @can('usage.create')
        <a href="{{ route('usage.create') }}" class="btn btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Buat Permintaan Barang</a>
    @endcan
</x-page-header>

<div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
    <x-kpi label="Total Permintaan" :value="$counts->sum()" hint="Semua periode" icon="clipboard-list" />
    <x-kpi label="Menunggu Persetujuan" :value="$counts['Menunggu Persetujuan'] ?? 0" hint="Perlu ditinjau" icon="clock" tone="orange" />
    <x-kpi label="Siap Dikeluarkan" :value="($counts['Disetujui'] ?? 0) + ($counts['Diproses Gudang'] ?? 0)" hint="Disetujui / diproses gudang" icon="badge-check" tone="green" />
    <x-kpi label="Selesai" :value="$counts['Selesai'] ?? 0" hint="Pengeluaran terkonfirmasi" icon="circle-check" tone="green" />
</div>

<form method="GET" class="card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="label">Cari data</label>
        <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="Nomor, pemohon, atau keperluan...">
    </div>
    <div class="min-w-[170px]">
        <label for="status" class="label">Status</label>
        <select id="status" name="status" class="field">
            <option value="">Semua status</option>
            @foreach (\App\Models\UsageRequest::STATUSES as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
        </select>
    </div>
    <div class="min-w-[210px]">
        <label for="project_id" class="label">Proyek</label>
        <select id="project_id" name="project_id" class="field">
            <option value="">Semua proyek</option>
            @foreach ($projects as $p)<option value="{{ $p->id }}" @selected(request('project_id') == $p->id)>{{ $p->name }}</option>@endforeach
        </select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    @if (request()->hasAny(['q', 'status', 'project_id']))<a href="{{ route('usage.index') }}" class="btn btn-secondary">Reset</a>@endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px]">
            <thead><tr><th class="th">Nomor</th><th class="th">Tanggal</th><th class="th">Proyek</th><th class="th">Pemohon</th><th class="th">Jenis</th><th class="th">Status</th><th class="th">Aksi</th></tr></thead>
            <tbody>
            @forelse ($requests as $r)
                <tr class="hover:bg-slate-50/70">
                    <td class="td font-bold text-brand"><a href="{{ route('usage.show', $r) }}">{{ $r->number }}</a></td>
                    <td class="td">{{ $fmt->date($r->date) }}</td>
                    <td class="td">{{ $r->project->name }}<div class="text-[11px] text-slate-500">{{ $r->purpose }}</div></td>
                    <td class="td">{{ $r->requester?->name ?? '-' }}</td>
                    <td class="td">{{ $r->items_count }} jenis</td>
                    <td class="td"><x-badge :status="$r->status" /></td>
                    <td class="td">
                        <div class="flex gap-1.5">
                            <a href="{{ route('usage.show', $r) }}" class="icon-btn" title="Lihat detail" aria-label="Detail {{ $r->number }}"><i data-lucide="eye" class="w-4 h-4"></i></a>
                            <a href="{{ route('usage.print', $r) }}" target="_blank" class="icon-btn" title="Cetak" aria-label="Cetak {{ $r->number }}"><i data-lucide="printer" class="w-4 h-4"></i></a>
                        </div>
                    </td>
                </tr>
            @empty<x-empty cols="7" />@endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $requests->links() }}</div>
@endsection
