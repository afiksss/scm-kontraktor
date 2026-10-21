@extends('layouts.app')
@php $title = ($row ? 'Edit ' : 'Tambah ') . $cfg['singular']; @endphp
@section('title', $title)
@section('crumb', 'Beranda / Data Master / ' . $cfg['title'])

@section('content')
<x-page-header :title="$title" subtitle="Kode dibuat otomatis oleh sistem. Kolom bertanda * wajib diisi.">
    <a href="{{ route('master.index', $type) }}" class="btn btn-secondary">Kembali</a>
</x-page-header>

<form method="POST" action="{{ $row ? route('master.update', [$type, $row->id]) : route('master.store', $type) }}" class="card p-6 max-w-3xl">
    @csrf @if ($row) @method('PUT') @endif
    <div class="grid sm:grid-cols-2 gap-5">
        @if ($row)
            <div>
                <label class="label">Kode</label>
                <input class="field" value="{{ $row->code }}" readonly>
            </div>
        @endif

        @foreach ($cfg['fields'] as $f)
            @php $val = old($f['name'], $row?->{$f['name']}); @endphp
            <div class="{{ ($f['span'] ?? 1) === 2 ? 'sm:col-span-2' : '' }}">
                <label for="f-{{ $f['name'] }}" class="label">{{ $f['label'] }} @if (str_contains((string) $f['rules'], 'required'))<span class="text-red-600">*</span>@endif</label>
                @if ($f['type'] === 'select')
                    <select id="f-{{ $f['name'] }}" name="{{ $f['name'] }}" class="field" @required(str_contains((string) $f['rules'], 'required'))>
                        <option value="">Pilih {{ strtolower($f['label']) }}</option>
                        @foreach (($f['options'])() as $id => $label)
                            <option value="{{ $id }}" @selected((string) $val === (string) $id)>{{ $label }}</option>
                        @endforeach
                    </select>
                @elseif ($f['type'] === 'textarea')
                    <textarea id="f-{{ $f['name'] }}" name="{{ $f['name'] }}" rows="3" class="field">{{ $val }}</textarea>
                @else
                    <input id="f-{{ $f['name'] }}" name="{{ $f['name'] }}" type="{{ $f['type'] }}" value="{{ $val }}"
                           @if ($f['type'] === 'number') step="any" min="0" @endif
                           @required(str_contains((string) $f['rules'], 'required')) class="field">
                @endif
                @if (!empty($f['hint']))<p class="text-[11px] text-slate-500 mt-1">{{ $f['hint'] }}</p>@endif
                @error($f['name'])<p class="err">{{ $message }}</p>@enderror
            </div>
        @endforeach
    </div>

    <div class="flex justify-end gap-2 mt-8 pt-5 border-t border-slate-100">
        <a href="{{ route('master.index', $type) }}" class="btn btn-secondary">Batal</a>
        <button class="btn btn-primary">Simpan {{ $cfg['singular'] }}</button>
    </div>
</form>
@endsection
