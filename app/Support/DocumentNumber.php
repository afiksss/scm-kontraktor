<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DocumentNumber
{
    /** Nomor dokumen bertahun, mis. PB-2026-00131. */
    public static function next(string $prefix, string $table, string $column = 'number'): string
    {
        $base = $prefix . '-' . now()->format('Y') . '-';
        $last = DB::table($table)->where($column, 'like', $base . '%')->orderByDesc($column)->value($column);
        $n = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        return $base . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    /** Kode master berurutan, mis. BRG-009. */
    public static function code(string $prefix, string $table, int $pad = 3): string
    {
        $base = $prefix . '-';
        $last = DB::table($table)->where('code', 'like', $base . '%')->orderByDesc('code')->value('code');
        $n = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        return $base . str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}
