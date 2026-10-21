@extends('layouts.app')
@section('title', $cfg['title'])
@section('crumb', 'Beranda / Data Master')

@section('content')
<x-page-header :title="$cfg['title']" subtitle="Data referensi operasional. Tidak wajib diisi lebih dulu: supplier, barang, gudang, dan proyek baru otomatis masuk dari transaksi. Data yang sudah dipakai dinonaktifkan, bukan dihapus.">
    @can('master.manage')
        <a href="{{ route('master.create', $type) }}" class="btn btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Tambah {{ $cfg['singular'] }}</a>
    @endcan
</x-page-header>

<form method="GET" class="card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="label">Cari data</label>
        <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="Cari kode atau nama...">
    </div>
    <div class="min-w-[150px]">
        <label for="status" class="label">Status</label>
        <select id="status" name="status" class="field" onchange="this.form.submit()">
            <option value="">Semua status</option>
            @foreach (['Aktif', 'Nonaktif'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
        </select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    @if (request()->hasAny(['q', 'status']))<a href="{{ route('master.index', $type) }}" class="btn btn-secondary">Reset</a>@endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px]">
            <thead>
                <tr>
                    @foreach ($cfg['columns'] as $col)<th class="th">{{ $col[0] }}</th>@endforeach
                    <th class="th">Status</th>
                    @can('master.manage')<th class="th">Aksi</th>@endcan
                </tr>
            </thead>
            <tbody>
            @forelse ($rows as $row)
                <tr class="hover:bg-slate-50/70">
                    @foreach ($cfg['columns'] as $i => $col)
                        <td class="td {{ $i === 0 ? 'font-bold text-brand' : '' }}">{{ $col[1] instanceof \Closure ? $col[1]($row) : data_get($row, $col[1]) }}</td>
                    @endforeach
                    <td class="td"><x-badge :status="$row->is_active ? 'Aktif' : 'Nonaktif'" /></td>
                    @can('master.manage')
                        <td class="td whitespace-nowrap">
                            <div class="flex gap-1.5">
                                <a href="{{ route('master.edit', [$type, $row->id]) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $row->name }}"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                                <form method="POST" action="{{ route('master.toggle', [$type, $row->id]) }}"
                                      onsubmit="return confirm('{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ addslashes($row->name) }}?')">
                                    @csrf @method('PATCH')
                                    <button class="icon-btn" title="{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $row->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $row->name }}">
                                        <i data-lucide="{{ $row->is_active ? 'toggle-right' : 'toggle-left' }}" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    @endcan
                </tr>
            @empty
                <x-empty :cols="count($cfg['columns']) + 2" />
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
