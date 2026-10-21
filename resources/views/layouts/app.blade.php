<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SCM {{ config('scm.company') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = {theme: {extend: {fontFamily: {sans: ['Inter', 'system-ui', 'sans-serif']},
            colors: {navy: {DEFAULT: '#142B4A', 600: '#1E3A5F', 700: '#294769'}, brand: {DEFAULT: '#2563EB', dark: '#1D4ED8'}, canvas: '#F3F6FA'}}}}
    </script>
    <style type="text/tailwindcss">
        @layer components {
            .btn {@apply inline-flex items-center justify-center gap-2 min-h-[38px] px-4 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition disabled:opacity-50 disabled:cursor-not-allowed;}
            .btn-primary {@apply bg-brand text-white hover:bg-brand-dark shadow-sm;}
            .btn-secondary {@apply bg-white border border-slate-300 text-navy hover:bg-slate-50;}
            .btn-danger {@apply bg-red-50 text-red-700 border border-red-100 hover:bg-red-100;}
            .btn-success {@apply bg-emerald-600 text-white hover:bg-emerald-700;}
            .field {@apply w-full min-h-[40px] bg-white border border-slate-300 rounded-lg px-3 py-2 text-[13px] text-navy placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-brand disabled:bg-slate-50 read-only:bg-slate-50;}
            .label {@apply block text-xs font-bold text-slate-600 mb-1.5;}
            .card {@apply bg-white border border-slate-200 rounded-2xl shadow-[0_4px_18px_rgba(20,43,74,.04)];}
            .th {@apply px-4 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 whitespace-nowrap;}
            .td {@apply px-4 py-3 text-[13px] border-t border-slate-100 align-middle;}
            .nav-item {@apply flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-[13px] font-semibold text-blue-200/80 hover:bg-navy-700 hover:text-white transition;}
            .nav-item.active {@apply bg-navy-700 text-white;}
            .err {@apply text-xs text-red-600 mt-1;}
            .icon-btn {@apply inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-brand;}
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        body{font-family:Inter,system-ui,sans-serif}
        [x-cloak]{display:none!important}
        @media print{
            aside,header,.no-print,#flash{display:none!important}
            body{background:#fff!important}
            main{padding:0!important}
            .card{box-shadow:none!important;border:0!important}
        }
    </style>
    @stack('head')
</head>
<body class="bg-canvas text-navy antialiased" x-data="{menu:false}">
@php
    $main = [
        ['dashboard', 'Dashboard', 'layout-dashboard', 'dashboard', null],
        ['purchases.index', 'Pembelian', 'shopping-bag', 'purchases.*', 'purchase.view'],
        ['receipts.index', 'Penerimaan Barang', 'package-check', 'receipts.*', 'purchase.view'],
        ['inventory.index', 'Inventaris', 'warehouse', 'inventory.*', 'inventory.view'],
        ['transfers.index', 'Mutasi Antargudang', 'arrow-left-right', 'transfers.*', 'inventory.view'],
        ['usage.index', 'Pemakaian Barang', 'clipboard-list', 'usage.*', 'usage.view'],
    ];
    $master = ['barang' => 'Data Barang', 'kategori' => 'Kategori Barang', 'satuan' => 'Satuan Barang', 'supplier' => 'Data Supplier', 'gudang' => 'Data Gudang', 'proyek' => 'Data Proyek'];
    $me = auth()->user();
@endphp

<div class="flex min-h-screen">
    <div x-show="menu" x-cloak @click="menu=false" class="fixed inset-0 bg-slate-900/50 z-30 lg:hidden"></div>

    <aside class="fixed lg:sticky top-0 z-40 h-screen w-[260px] shrink-0 bg-navy text-white overflow-y-auto p-4 transition-transform -translate-x-full lg:translate-x-0"
           :class="{'translate-x-0': menu, '-translate-x-full': !menu}">
        <div class="flex items-center gap-3 px-2 pt-2 pb-7">
            <div class="bg-brand rounded-xl p-2"><i data-lucide="boxes" class="w-6 h-6"></i></div>
            <div class="leading-tight">
                <strong class="block text-sm">Karya Bangun</strong>
                <small class="text-[10px] tracking-wide text-blue-200">SUPPLY CHAIN MANAGEMENT</small>
            </div>
        </div>

        <nav class="space-y-1" aria-label="Navigasi utama">
            @foreach ($main as [$route, $label, $icon, $match, $perm])
                @if (!$perm || $me->can($perm))
                    <a href="{{ route($route) }}" class="nav-item {{ request()->routeIs($match) ? 'active' : '' }}">
                        <i data-lucide="{{ $icon }}" class="w-[17px] h-[17px]"></i>{{ $label }}
                    </a>
                @endif
            @endforeach

            <div class="text-[10px] uppercase tracking-widest text-blue-300 font-bold px-3.5 pt-6 pb-2">Data Master</div>
            @foreach ($master as $key => $label)
                <a href="{{ route('master.index', $key) }}" class="nav-item !py-2 pl-10 text-xs {{ request()->route('type') === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach

            <div class="border-t border-white/10 my-4"></div>
            @can('report.view')
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}"><i data-lucide="file-bar-chart-2" class="w-[17px] h-[17px]"></i>Laporan</a>
            @endcan
            @can('users.manage')
                <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}"><i data-lucide="users" class="w-[17px] h-[17px]"></i>Pengguna</a>
            @endcan
            @can('activity.view')
                <a href="{{ route('activity.index') }}" class="nav-item {{ request()->routeIs('activity.*') ? 'active' : '' }}"><i data-lucide="history" class="w-[17px] h-[17px]"></i>Log Aktivitas</a>
            @endcan
            <a href="{{ route('roles.index') }}" class="nav-item {{ request()->routeIs('roles.*') ? 'active' : '' }}"><i data-lucide="shield-check" class="w-[17px] h-[17px]"></i>Hak Akses</a>
            <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}"><i data-lucide="settings" class="w-[17px] h-[17px]"></i>Pengaturan</a>
        </nav>

        <div class="mt-8 p-3 rounded-xl bg-white/10 text-xs text-blue-100 leading-relaxed">
            {{ config('scm.company') }}<br><span class="text-blue-300">{{ $me->roleLabel() }}</span>
        </div>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="sticky top-0 z-20 bg-white/95 backdrop-blur border-b border-slate-200 px-4 md:px-8 py-3 flex items-center gap-3">
            <button type="button" class="icon-btn lg:hidden" @click="menu=true" aria-label="Buka menu"><i data-lucide="menu" class="w-4 h-4"></i></button>
            <div class="min-w-0 flex-1">
                <div class="text-[11px] text-slate-500 truncate">@yield('crumb', 'Beranda')</div>
                <div class="font-bold text-sm truncate">@yield('title', 'Dashboard')</div>
            </div>

            <div class="relative" x-data="{open:false}" @keydown.escape.window="open=false">
                <button type="button" class="icon-btn relative !w-9 !h-9" @click="open=!open" aria-label="Notifikasi">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    @if (count($notifications))
                        <span class="absolute -right-1 -top-1 bg-red-500 text-white text-[9px] font-bold rounded-full px-1.5 py-px">{{ count($notifications) }}</span>
                    @endif
                </button>
                <div x-show="open" x-cloak @click.outside="open=false" x-transition
                     class="absolute right-0 mt-2 w-80 max-w-[85vw] card p-2 z-50">
                    <div class="px-3 py-2 text-xs font-bold text-slate-500">Notifikasi</div>
                    @forelse ($notifications as $n)
                        <a href="{{ $n['url'] }}" class="flex gap-3 items-start px-3 py-2.5 rounded-lg hover:bg-slate-50 text-[13px]">
                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ ['red' => 'bg-red-500', 'amber' => 'bg-amber-500', 'blue' => 'bg-blue-500'][$n['tone']] ?? 'bg-slate-400' }}"></span>
                            <span>{{ $n['text'] }}</span>
                        </a>
                    @empty
                        <div class="px-3 py-6 text-center text-xs text-slate-500">Tidak ada notifikasi baru.</div>
                    @endforelse
                </div>
            </div>

            <div class="relative border-l pl-4" x-data="{open:false}">
                <button type="button" class="flex items-center gap-2 text-left" @click="open=!open">
                    <span class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">{{ $me->initials() }}</span>
                    <span class="hidden sm:block text-xs leading-tight">
                        <strong class="block">{{ $me->name }}</strong><span class="text-slate-500">{{ $me->roleLabel() }}</span>
                    </span>
                </button>
                <div x-show="open" x-cloak @click.outside="open=false" x-transition class="absolute right-0 mt-2 w-48 card p-1.5 z-50">
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-lg text-[13px] hover:bg-slate-50">Profil &amp; pengaturan</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="w-full text-left px-3 py-2 rounded-lg text-[13px] text-red-600 hover:bg-red-50">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="p-4 md:p-8 max-w-[1500px] mx-auto">
            @if (session('success') || session('error'))
                <div id="flash" x-data="{show:true}" x-init="setTimeout(()=>show=false, 6000)" x-show="show" x-transition
                     class="mb-5 flex items-start gap-3 rounded-xl px-4 py-3 text-[13px] font-medium border {{ session('error') ? 'bg-red-50 border-red-200 text-red-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800' }}" role="status">
                    <i data-lucide="{{ session('error') ? 'circle-alert' : 'circle-check' }}" class="w-4 h-4 mt-0.5 shrink-0"></i>
                    <span class="flex-1">{{ session('error') ?? session('success') }}</span>
                    <button type="button" @click="show=false" aria-label="Tutup" class="opacity-60 hover:opacity-100">✕</button>
                </div>
            @endif

            @if ($errors->any() && !$errors->hasBag('none'))
                <div class="mb-5 rounded-xl px-4 py-3 text-[13px] bg-red-50 border border-red-200 text-red-800" role="alert">
                    <strong class="block mb-1">Periksa kembali isian Anda:</strong>
                    <ul class="list-disc pl-5 space-y-0.5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    window.refreshIcons = () => window.lucide && lucide.createIcons();
    document.addEventListener('DOMContentLoaded', refreshIcons);
</script>
@stack('scripts')
</body>
</html>
