@extends('layouts.app')
@section('title', 'Log Aktivitas')
@section('crumb', 'Beranda / Log Aktivitas')

@section('content')
<x-page-header title="Log Aktivitas" subtitle="Jejak audit seluruh perubahan penting: siapa melakukan apa dan kapan." />

<form method="GET" class="card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[200px]">
        <label for="q" class="label">Cari aktivitas</label>
        <input id="q" name="q" value="{{ request('q') }}" class="field" placeholder="Mis. nomor dokumen...">
    </div>
    <div class="min-w-[160px]">
        <label for="module" class="label">Modul</label>
        <select id="module" name="module" class="field"><option value="">Semua modul</option>
            @foreach ($modules as $m)<option @selected(request('module') === $m)>{{ $m }}</option>@endforeach</select>
    </div>
    <div class="min-w-[180px]">
        <label for="user_id" class="label">Pengguna</label>
        <select id="user_id" name="user_id" class="field"><option value="">Semua pengguna</option>
            @foreach ($users as $u)<option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>@endforeach</select>
    </div>
    <button class="btn btn-primary">Terapkan</button>
    @if (request()->hasAny(['q', 'module', 'user_id']))<a href="{{ route('activity.index') }}" class="btn btn-secondary">Reset</a>@endif
</form>

<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px]">
            <thead><tr><th class="th">Waktu</th><th class="th">Pengguna</th><th class="th">Modul</th><th class="th">Aktivitas</th><th class="th">IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $l)
                <tr class="hover:bg-slate-50/70">
                    <td class="td whitespace-nowrap text-xs">{{ $fmt->datetime($l->created_at) }}</td>
                    <td class="td font-semibold">{{ $l->user?->name ?? 'Sistem' }}</td>
                    <td class="td"><x-badge :status="$l->module" /></td>
                    <td class="td">{{ $l->description }}</td>
                    <td class="td text-xs text-slate-500">{{ $l->ip_address }}</td>
                </tr>
            @empty<x-empty cols="5" message="Belum ada aktivitas" />@endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
