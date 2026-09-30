<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('rm')) {
    /**
     * Format an integer amount in sen as "RM 2,450" (or "RM 2,450.50" when sen present).
     */
    function rm(?int $sen, bool $forceDecimals = false): string
    {
        $sen ??= 0;
        $negative = $sen < 0;
        $abs = abs($sen);
        $decimals = ($forceDecimals || $abs % 100 !== 0) ? 2 : 0;

        return ($negative ? '-' : '').'RM '.number_format($abs / 100, $decimals);
    }
}

if (! function_exists('parse_rm')) {
    /**
     * Parse a ringgit input ("3500", "3,500.50", "RM 850") into sen. Null when not a valid amount.
     */
    function parse_rm(string|int|float|null $input): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        $clean = str_replace([',', ' '], '', preg_replace('/^\s*RM/i', '', (string) $input) ?? '');

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            return null;
        }

        [$ringgit, $cents] = array_pad(explode('.', $clean), 2, '0');

        return (int) $ringgit * 100 + (int) str_pad($cents, 2, '0');
    }
}

if (! function_exists('rm_short')) {
    /**
     * Compact money: "RM 3.82j" (juta), "RM 168k" (ribu), "RM 850" otherwise.
     */
    function rm_short(?int $sen): string
    {
        $ringgit = abs($sen ?? 0) / 100;
        $sign = ($sen ?? 0) < 0 ? '-' : '';

        if ($ringgit >= 1_000_000) {
            return $sign.'RM '.rtrim(rtrim(number_format($ringgit / 1_000_000, 2), '0'), '.').'j';
        }

        if ($ringgit >= 1_000) {
            return $sign.'RM '.rtrim(rtrim(number_format($ringgit / 1_000, 1), '0'), '.').'k';
        }

        return $sign.'RM '.number_format($ringgit);
    }
}

if (! function_exists('tarikh')) {
    /**
     * Malay short date "12 Jun 2027" (months: Jan Feb Mac Apr Mei Jun Jul Ogos Sep Okt Nov Dis).
     */
    function tarikh(CarbonInterface|string|null $date, bool $withTime = false): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
        $out = $date->day.' '.$months[$date->month - 1].' '.$date->year;

        return $withTime ? $out.', '.$date->format('H:i') : $out;
    }
}

if (! function_exists('masa_lalu')) {
    /**
     * Relative time as in the design: "2 minit lalu", "3 jam lalu", "Semalam", "4 hari lalu",
     * falling back to tarikh() after a week.
     */
    function masa_lalu(CarbonInterface|string|null $date): string
    {
        if ($date === null || $date === '') {
            return 'Belum pernah';
        }

        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);
        $minutes = (int) $date->diffInMinutes(now());

        return match (true) {
            $minutes < 1 => 'Baru sahaja',
            $minutes < 60 => $minutes.' minit lalu',
            $minutes < 60 * 24 && $date->isToday() => intdiv($minutes, 60).' jam lalu',
            $date->isYesterday() => 'Semalam',
            $minutes < 60 * 24 * 7 => max(2, (int) $date->copy()->startOfDay()->diffInDays(now()->startOfDay())).' hari lalu',
            default => tarikh($date),
        };
    }
}

if (! function_exists('initials')) {
    /**
     * Two-letter initials for avatars: "Muhammad Nurfitkri" → "MN".
     */
    function initials(?string $name): string
    {
        $words = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
            ->reject(fn (string $w) => in_array(mb_strtolower($w), ['bin', 'binti', 'bt', 'b.', 'bte', 'a/l', 'a/p'], true))
            ->filter()
            ->values();

        return mb_strtoupper(mb_substr($words->get(0, ''), 0, 1).mb_substr($words->get(1, ''), 0, 1));
    }
}
