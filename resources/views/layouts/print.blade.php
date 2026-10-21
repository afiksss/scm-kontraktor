<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>@yield('title') · {{ config('scm.company') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box}
        body{font-family:Inter,Arial,sans-serif;color:#142B4A;margin:0;padding:32px;font-size:12px;line-height:1.5}
        .sheet{max-width:820px;margin:0 auto}
        .head{border-bottom:2px solid #142B4A;padding-bottom:12px;margin-bottom:18px;display:flex;justify-content:space-between;align-items:flex-end}
        .co{font-size:11px;font-weight:800;letter-spacing:.12em;color:#1D4ED8}
        h1{font-size:20px;margin:6px 0 0}
        table{width:100%;border-collapse:collapse;margin-top:14px}
        th{background:#F1F5F9;text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.05em;padding:8px;border:1px solid #CBD5E1}
        td{padding:8px;border:1px solid #CBD5E1;vertical-align:top}
        .r{text-align:right}
        .meta{display:grid;grid-template-columns:1fr 1fr;gap:6px 24px;margin-top:6px}
        .meta span{color:#64748B}
        .sign{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;margin-top:48px;text-align:center}
        .sign div{padding-top:56px;border-top:1px solid #142B4A;font-size:11px}
        .bar{max-width:820px;margin:0 auto 16px;display:flex;gap:8px;justify-content:flex-end}
        .bar button{padding:8px 14px;border:0;border-radius:8px;background:#2563EB;color:#fff;font-weight:700;cursor:pointer}
        @media print{.bar{display:none}body{padding:0}}
    </style>
</head>
<body>
<div class="bar"><button onclick="window.print()">Cetak / Simpan PDF</button></div>
<div class="sheet">
    <div class="head">
        <div><div class="co">{{ strtoupper(config('scm.company')) }}</div><h1>@yield('doc-title')</h1></div>
        <div style="text-align:right;color:#64748B">Dicetak: {{ $fmt->datetime(now()) }}<br>oleh {{ auth()->user()->name }}</div>
    </div>
    @yield('content')
</div>
</body>
</html>
