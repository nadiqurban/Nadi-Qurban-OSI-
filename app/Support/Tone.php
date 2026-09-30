<?php

namespace App\Support;

/**
 * Colour pairs used across the design (tinted background + ink).
 * Full class strings are written out so Tailwind can detect them.
 */
final class Tone
{
    /** @var array<string, array{bg: string, fg: string}> */
    private const MAP = [
        'primary' => ['bg' => 'bg-primary-soft', 'fg' => 'text-primary dark:text-[#c9ce93]'],
        'gold' => ['bg' => 'bg-gold-soft', 'fg' => 'text-gold'],
        'gold-ink' => ['bg' => 'bg-gold-soft', 'fg' => 'text-gold-ink'],
        'success' => ['bg' => 'bg-success-soft', 'fg' => 'text-success'],
        'info' => ['bg' => 'bg-info-soft', 'fg' => 'text-info'],
        'warning' => ['bg' => 'bg-warning-soft', 'fg' => 'text-warning'],
        'danger' => ['bg' => 'bg-danger-soft', 'fg' => 'text-danger'],
        'purple' => ['bg' => 'bg-purple-soft', 'fg' => 'text-purple'],
        'neutral' => ['bg' => 'bg-neutral-soft', 'fg' => 'text-neutral'],
    ];

    /** Avatar pairs in design order (Pengguna & Peranan / Vendor / CRM `avPairs`). */
    public const AVATAR = ['primary', 'gold', 'info', 'success', 'purple', 'danger'];

    public static function bg(string $tone): string
    {
        return self::MAP[$tone]['bg'] ?? self::MAP['neutral']['bg'];
    }

    public static function fg(string $tone): string
    {
        return self::MAP[$tone]['fg'] ?? self::MAP['neutral']['fg'];
    }

    public static function classes(string $tone): string
    {
        return self::bg($tone).' '.self::fg($tone);
    }

    /** Stable avatar tone for a name/id. */
    public static function avatarFor(string|int $key): string
    {
        $index = is_int($key) ? $key : crc32($key);

        return self::AVATAR[$index % count(self::AVATAR)];
    }
}
