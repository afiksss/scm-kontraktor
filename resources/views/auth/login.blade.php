<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · SCM {{ config('scm.company') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.577.0/dist/umd/lucide.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        body{font-family:Inter,system-ui,sans-serif}
        .art{background:
            radial-gradient(circle at 20% 15%, rgba(37,99,235,.55), transparent 45%),
            radial-gradient(circle at 85% 85%, rgba(56,189,248,.25), transparent 40%),
            linear-gradient(160deg,#142B4A,#0B2341)}
        .grid-lines{background-image:linear-gradient(rgba(255,255,255,.06) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.06) 1px,transparent 1px);background-size:44px 44px}
        [x-cloak]{display:none!important}
    </style>
</head>
<body class="bg-white text-[#142B4A] antialiased">
<div class="min-h-screen grid lg:grid-cols-2">
    <section class="art grid-lines relative hidden lg:flex flex-col justify-between p-14 text-white">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center"><i data-lucide="blocks"></i></span>
            <span class="font-bold text-sm">{{ config('scm.company') }}</span>
        </div>
        <div class="max-w-lg">
            <span class="text-blue-200 text-xs tracking-[.2em] font-bold">OPERASIONAL PROYEK • {{ now()->format('Y') }}</span>
            <h1 class="font-extrabold text-4xl leading-tight mt-4">Material tepat.<br>Proyek bergerak.</h1>
            <p class="text-blue-100 text-lg mt-4 leading-relaxed">Kelola rantai pasok proyek secara terintegrasi — dari pembelian dan penerimaan hingga pemakaian di lapangan.</p>
            <div class="grid grid-cols-3 gap-3 mt-10 text-xs">
                @foreach ([['shopping-bag', 'Pengadaan'], ['warehouse', 'Gudang'], ['hard-hat', 'Proyek']] as [$i, $t])
                    <div class="rounded-xl bg-white/10 border border-white/10 p-4"><i data-lucide="{{ $i }}" class="w-5 h-5 mb-3 text-blue-200"></i><div class="font-semibold">{{ $t }}</div></div>
                @endforeach
            </div>
        </div>
        <span class="text-blue-200 text-xs">Pengadaan • Gudang • Proyek</span>
    </section>

    <main class="flex items-center justify-center px-6 py-14">
        <div class="w-full max-w-md">
            <div class="w-12 h-12 bg-[#142B4A] text-white rounded-xl flex items-center justify-center mb-8"><i data-lucide="boxes"></i></div>
            <p class="font-bold text-sm text-blue-600">{{ config('scm.company') }}</p>
            <h2 class="font-extrabold text-2xl mt-2 leading-tight">Sistem Supply Chain Management</h2>
            <p class="text-sm text-slate-500 mt-2">Masuk untuk mengelola pembelian, inventaris, dan pemakaian barang.</p>

            <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 space-y-5" x-data="{show:false}">
                @csrf
                @if ($errors->any())
                    <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 text-[13px] px-4 py-3" role="alert">{{ $errors->first() }}</div>
                @endif
                <div>
                    <label for="login" class="block text-xs font-bold text-slate-600 mb-1.5">Username atau email</label>
                    <input id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username"
                           class="w-full h-11 border border-slate-300 rounded-lg px-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-600">
                </div>
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-600 mb-1.5">Password</label>
                    <div class="relative">
                        <input id="password" name="password" :type="show ? 'text' : 'password'" required autocomplete="current-password"
                               class="w-full h-11 border border-slate-300 rounded-lg pl-3.5 pr-16 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-600">
                        <button type="button" @click="show=!show" class="absolute right-3 top-3 text-xs font-semibold text-slate-500 hover:text-blue-600" x-text="show ? 'Sembunyikan' : 'Lihat'"></button>
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="remember" class="accent-blue-600 w-4 h-4"> Ingat saya di perangkat ini</label>
                <button class="w-full h-11 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm transition">Masuk</button>
            </form>

            @if (app()->environment('local'))
                <div class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-4" x-data>
                    <div class="text-xs font-bold text-slate-600 mb-2">Akun contoh (hanya tampil di lingkungan lokal) — password: <code>password</code></div>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['andi.pratama' => 'Admin', 'siti.rahmawati' => 'Purchasing', 'dian.putri' => 'Gudang', 'budi.santoso' => 'Manajer Proyek', 'rudi.hartono' => 'Pimpinan'] as $u => $label)
                            <button type="button" class="text-[11px] font-semibold px-2.5 py-1.5 rounded-lg bg-white border border-slate-200 hover:border-blue-400 hover:text-blue-700"
                                    @click="document.getElementById('login').value='{{ $u }}';document.getElementById('password').value='password'">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            <p class="mt-10 text-center text-xs text-slate-400">{{ config('scm.company') }} • {{ now()->format('Y') }}</p>
        </div>
    </main>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
</body>
</html>
