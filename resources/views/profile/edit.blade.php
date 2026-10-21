@extends('layouts.app')
@section('title', 'Pengaturan')
@section('crumb', 'Beranda / Pengaturan')

@section('content')
<x-page-header title="Pengaturan" subtitle="Kelola profil, keamanan akun, dan lihat riwayat aktivitas Anda." />

<div class="card p-5">
    <div class="flex overflow-x-auto border-b border-slate-200 mb-6">
        @foreach (['profil' => 'Pengaturan Profil', 'password' => 'Ubah Password', 'aktivitas' => 'Riwayat Aktivitas'] as $k => $label)
            <a href="{{ route('profile.edit', ['tab' => $k]) }}"
               class="px-4 py-3 text-xs font-bold whitespace-nowrap border-b-2 {{ $tab === $k ? 'border-brand text-brand' : 'border-transparent text-slate-500 hover:text-navy' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($tab === 'profil')
        <form method="POST" action="{{ route('profile.update') }}" class="max-w-xl space-y-4">
            @csrf @method('PUT')
            <div class="w-16 h-16 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xl">{{ $user->initials() }}</div>
            <div><label for="name" class="label">Nama lengkap <span class="text-red-600">*</span></label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="field">@error('name')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label for="email" class="label">Email <span class="text-red-600">*</span></label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="field">@error('email')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label for="position" class="label">Jabatan</label>
                <input id="position" name="position" value="{{ old('position', $user->position) }}" class="field"></div>
            <div><label class="label">Username</label><input class="field" value="{{ $user->username }}" readonly></div>
            <div><label class="label">Role</label><input class="field" value="{{ $user->roleLabel() }}" readonly></div>
            <button class="btn btn-primary">Simpan Profil</button>
        </form>

    @elseif ($tab === 'password')
        <form method="POST" action="{{ route('profile.password') }}" class="max-w-xl space-y-4">
            @csrf @method('PUT')
            <div><label for="current_password" class="label">Password saat ini <span class="text-red-600">*</span></label>
                <input id="current_password" type="password" name="current_password" required autocomplete="current-password" class="field">@error('current_password')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label for="password" class="label">Password baru <span class="text-red-600">*</span></label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="field" placeholder="Minimal 8 karakter">@error('password')<p class="err">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="label">Konfirmasi password baru <span class="text-red-600">*</span></label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field"></div>
            <button class="btn btn-primary">Ubah Password</button>
        </form>

    @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px]">
                <thead><tr><th class="th">Waktu</th><th class="th">Modul</th><th class="th">Aktivitas</th></tr></thead>
                <tbody>
                @forelse ($logs as $l)
                    <tr><td class="td whitespace-nowrap text-xs">{{ $fmt->datetime($l->created_at) }}</td><td class="td">{{ $l->module }}</td><td class="td">{{ $l->description }}</td></tr>
                @empty<x-empty cols="3" message="Belum ada aktivitas" />@endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
