@extends('layouts.app')
@section('title', 'Pengguna')
@section('crumb', 'Beranda / Pengguna')

@section('content')
<x-page-header title="Pengguna" subtitle="Akses operasional sesuai peran dan tanggung jawab.">
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i data-lucide="plus" class="w-4 h-4"></i>Tambah Pengguna</a>
</x-page-header>

<div class="card p-4 mb-5 text-xs leading-6 text-slate-600">
    <strong class="text-navy">Ringkasan izin:</strong>
    <strong>Admin</strong> mengelola seluruh sistem; <strong>Purchasing</strong> membuat pembelian; <strong>Petugas Gudang</strong> menerima, mengeluarkan, dan memutasi barang;
    <strong>Manajer Proyek</strong> membuat permintaan barang; <strong>Pimpinan</strong> menyetujui pembelian/permintaan dan meninjau laporan.
</div>

<form method="GET" class="card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="label">Cari pengguna</label>
        <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="Nama, username, atau email...">
    </div>
    <div class="min-w-[160px]">
        <label for="role" class="label">Role</label>
        <select id="role" name="role" class="field">
            <option value="">Semua role</option>
            @foreach (config('scm.roles') as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $v }}</option>@endforeach
        </select>
    </div>
    <div class="min-w-[140px]">
        <label for="status" class="label">Status akun</label>
        <select id="status" name="status" class="field">
            <option value="">Semua</option>
            @foreach (['Aktif', 'Nonaktif'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach
        </select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    @if (request()->hasAny(['q', 'role', 'status']))<a href="{{ route('users.index') }}" class="btn btn-secondary">Reset</a>@endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px]">
            <thead><tr><th class="th">Nama</th><th class="th">Username</th><th class="th">Jabatan</th><th class="th">Role</th><th class="th">Status Akun</th><th class="th">Aktivitas Terakhir</th><th class="th">Aksi</th></tr></thead>
            <tbody>
            @forelse ($users as $u)
                <tr class="hover:bg-slate-50/70">
                    <td class="td font-semibold">{{ $u->name }}<div class="text-[11px] font-normal text-slate-500">{{ $u->email }}</div></td>
                    <td class="td">{{ $u->username }}</td>
                    <td class="td">{{ $u->position }}</td>
                    <td class="td">{{ $u->roleLabel() }}</td>
                    <td class="td"><x-badge :status="$u->is_active ? 'Aktif' : 'Nonaktif'" /></td>
                    <td class="td text-xs">{{ $u->last_login_at ? $fmt->datetime($u->last_login_at) : 'Belum pernah masuk' }}</td>
                    <td class="td">
                        <div class="flex gap-1.5">
                            <a href="{{ route('users.edit', $u) }}" class="icon-btn" title="Edit" aria-label="Edit {{ $u->name }}"><i data-lucide="pencil" class="w-4 h-4"></i></a>
                            @if ($u->id !== auth()->id())
                                <form method="POST" action="{{ route('users.toggle', $u) }}" onsubmit="return confirm('{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ addslashes($u->name) }}?')">
                                    @csrf @method('PATCH')
                                    <button class="icon-btn" title="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $u->name }}"><i data-lucide="{{ $u->is_active ? 'user-x' : 'user-check' }}" class="w-4 h-4"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty<x-empty cols="7" />@endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
