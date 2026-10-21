@extends('layouts.app')
@php $title = $user ? 'Edit Pengguna' : 'Tambah Pengguna'; @endphp
@section('title', $title)
@section('crumb', 'Beranda / Pengguna')

@section('content')
<x-page-header :title="$title" subtitle="Role menentukan menu dan tindakan yang dapat diakses pengguna.">
    <a href="{{ route('users.index') }}" class="btn btn-secondary">Kembali</a>
</x-page-header>

<form method="POST" action="{{ $user ? route('users.update', $user) : route('users.store') }}" class="card p-6 max-w-3xl">
    @csrf @if ($user) @method('PUT') @endif
    <div class="grid sm:grid-cols-2 gap-5">
        <div>
            <label for="name" class="label">Nama lengkap <span class="text-red-600">*</span></label>
            <input id="name" name="name" value="{{ old('name', $user?->name) }}" required class="field">
            @error('name')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="username" class="label">Username <span class="text-red-600">*</span></label>
            <input id="username" name="username" value="{{ old('username', $user?->username) }}" required autocomplete="off" class="field">
            @error('username')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="label">Email <span class="text-red-600">*</span></label>
            <input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required class="field">
            @error('email')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="position" class="label">Jabatan <span class="text-red-600">*</span></label>
            <input id="position" name="position" value="{{ old('position', $user?->position) }}" required class="field">
            @error('position')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="role" class="label">Role <span class="text-red-600">*</span></label>
            <select id="role" name="role" required class="field" @disabled($user && $user->id === auth()->id())>
                @foreach (config('scm.roles') as $k => $v)<option value="{{ $k }}" @selected(old('role', $user?->role) === $k)>{{ $v }}</option>@endforeach
            </select>
            @if ($user && $user->id === auth()->id())<p class="text-[11px] text-slate-500 mt-1">Anda tidak dapat mengubah role akun sendiri.</p>@endif
            @error('role')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div class="text-[11px] text-slate-500 leading-relaxed sm:pt-6" x-data="{r: '{{ old('role', $user?->role ?? 'purchasing') }}', info: @js(config('scm.role_info'))}" x-init="$nextTick(() => { const el = document.getElementById('role'); if (el) { r = el.value; el.addEventListener('change', e => r = e.target.value) } })">
            <span x-text="info[r]"></span> <a href="{{ route('roles.index') }}" class="text-brand font-semibold hover:underline">Lihat matriks hak akses</a>
        </div>
        <div>
            <label for="password" class="label">{{ $user ? 'Password baru' : 'Password' }} @unless ($user)<span class="text-red-600">*</span>@endunless</label>
            <input id="password" type="password" name="password" autocomplete="new-password" @required(!$user) class="field" placeholder="{{ $user ? 'Kosongkan bila tidak diubah' : 'Minimal 8 karakter' }}">
            @error('password')<p class="err">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="password_confirmation" class="label">Konfirmasi password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="field">
        </div>
    </div>
    <div class="flex justify-end gap-2 mt-8 pt-5 border-t border-slate-100">
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary">Simpan Pengguna</button>
    </div>
</form>
@endsection
