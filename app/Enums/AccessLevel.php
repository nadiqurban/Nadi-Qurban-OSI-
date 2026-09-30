<?php

namespace App\Enums;

/**
 * Permission matrix level per module (Pengguna & Peranan.dc.html):
 * Penuh = view + manage, Lihat = view only, Tiada = no access.
 */
enum AccessLevel: string
{
    case Full = 'F';
    case View = 'V';
    case None = 'N';

    public function label(): string
    {
        return match ($this) {
            self::Full => 'Akses Penuh',
            self::View => 'Lihat Sahaja',
            self::None => 'Tiada Akses',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Full => 'Penuh',
            self::View => 'Lihat',
            self::None => 'Tiada',
        };
    }

    /** Phosphor icon class used in the matrix and pills. */
    public function icon(): string
    {
        return match ($this) {
            self::Full => 'ph-fill ph-check-circle',
            self::View => 'ph-fill ph-eye',
            self::None => 'ph ph-minus-circle',
        };
    }

    /** Matrix icon colour. */
    public function iconClass(): string
    {
        return match ($this) {
            self::Full => 'text-success',
            self::View => 'text-gold',
            self::None => 'text-[#CBD5D0]',
        };
    }

    /** Pill colours (role detail / edit role). */
    public function pillClasses(): string
    {
        return match ($this) {
            self::Full => 'bg-success-soft text-success',
            self::View => 'bg-gold-soft text-gold-ink',
            self::None => 'bg-neutral-soft text-faint',
        };
    }

    /** Click-cycle order in the design: Penuh → Lihat → Tiada → Penuh. */
    public function next(): self
    {
        return match ($this) {
            self::Full => self::View,
            self::View => self::None,
            self::None => self::Full,
        };
    }
}
