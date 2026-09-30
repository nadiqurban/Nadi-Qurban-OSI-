<?php

namespace App\Enums;

/** Promo code discount type (Kod Promosi.dc.html: "Peratus (%)" / "Jumlah Tetap (RM)"). */
enum DiscountType: string
{
    case Percent = 'peratus';
    case Fixed = 'tetap';

    public function label(): string
    {
        return match ($this) {
            self::Percent => 'Peratus (%)',
            self::Fixed => 'Jumlah Tetap (RM)',
        };
    }
}
