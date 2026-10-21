<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Helper format tampilan (dibagikan ke semua view sebagai $fmt).
 */
class Fmt
{
    public function money($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }

    public function qty($n): string
    {
        $text = number_format((float) $n, 2, ',', '.');

        return rtrim(rtrim($text, '0'), ',');
    }

    public function date($d, string $format = 'd M Y'): string
    {
        return $d ? Carbon::parse($d)->translatedFormat($format) : '-';
    }

    public function datetime($d): string
    {
        return $d ? Carbon::parse($d)->translatedFormat('d M Y, H.i') : '-';
    }
}
