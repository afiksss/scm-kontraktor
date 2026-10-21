@extends('layouts.app')
@section('title', 'Ubah Hak Akses')
@section('crumb', 'Beranda / Hak Akses / Ubah')

@section('content')
<x-page-header title="Ubah Pengaturan Hak Akses" subtitle="Centang role yang boleh melakukan tiap tindakan. Perubahan langsung berlaku setelah disimpan.">
    <a href="{{ route('roles.index') }}" class="btn btn-secondary">Kembali</a>
</x-page-header>

<form method="POST" action="{{ route('roles.update') }}" class="space-y-5">
    @csrf @method('PUT')
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead>
                    <tr>
                        <th class="th">Hak akses</th>
                        @foreach ($roles as $key => $label)<th class="th text-center">{{ $label }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach ($matrix as $group => $rows)
                    <tr><td colspan="{{ count($roles) + 1 }}" class="px-4 py-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 bg-slate-50/60 border-t border-slate-100">{{ $group }}</td></tr>
                    @foreach ($rows as $row)
                        <tr class="hover:bg-slate-50/70">
                            <td class="td">{{ $row['label'] }}</td>
                            @foreach ($roles as $key => $label)
                                @php $isLocked = $key === 'admin' && in_array($row['key'], $locked, true); @endphp
                                <td class="td text-center">
                                    <input type="checkbox" name="perm[{{ $row['key'] }}][{{ $key }}]" value="1"
                                           class="w-4 h-4 accent-blue-700" aria-label="{{ $label }}: {{ $row['label'] }}"
                                           @checked(in_array($key, $row['roles'], true) || $isLocked)
                                           @disabled($isLocked)>
                                    @if ($isLocked)<input type="hidden" name="perm[{{ $row['key'] }}][{{ $key }}]" value="1">@endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-xs text-slate-500">Admin selalu memegang izin kelola pengguna, hak akses, dan data master agar sistem tidak terkunci.</p>
    <div class="flex flex-wrap justify-end gap-2">
        <button type="submit" form="reset-form" class="btn btn-secondary" onclick="return confirm('Kembalikan semua hak akses ke pengaturan bawaan?')">Kembalikan ke bawaan</button>
        <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
    </div>
</form>
<form id="reset-form" method="POST" action="{{ route('roles.reset') }}" class="hidden">@csrf</form>
@endsection
