@props(['label', 'value', 'hint' => null, 'icon' => 'box', 'tone' => 'blue'])
@php
    $tones = [
        'blue' => 'text-blue-600 bg-blue-50 border-blue-500',
        'red' => 'text-red-600 bg-red-50 border-red-500',
        'green' => 'text-emerald-600 bg-emerald-50 border-emerald-500',
        'orange' => 'text-amber-600 bg-amber-50 border-amber-500',
    ];
    [$iconTone, $border] = [Str::before($tones[$tone] ?? $tones['blue'], ' border'), 'border-' . Str::afterLast($tones[$tone] ?? $tones['blue'], 'border-')];
@endphp
<article class="card border-l-[3px] {{ $border }} p-4 md:p-5">
    <div class="flex justify-between gap-2">
        <span class="text-xs font-semibold text-slate-500">{{ $label }}</span>
        <span class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $iconTone }}"><i data-lucide="{{ $icon }}" class="w-[18px] h-[18px]"></i></span>
    </div>
    <div class="text-xl font-extrabold mt-3 leading-tight">{{ $value }}</div>
    @if ($hint)<div class="text-[11px] text-slate-500 mt-1.5">{{ $hint }}</div>@endif
</article>
