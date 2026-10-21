@props(['title', 'subtitle' => null])
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div class="min-w-0">
        <h1 class="font-extrabold text-2xl md:text-[28px] tracking-tight">{{ $title }}</h1>
        @if ($subtitle)<p class="text-xs text-slate-500 mt-1.5 max-w-2xl leading-relaxed">{{ $subtitle }}</p>@endif
    </div>
    @if (trim((string) $slot))<div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>@endif
</div>
