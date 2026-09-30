<?php

namespace App\Enums;

/** One monthly instalment (Portal Ansuran: Dibayar / Perlu Bayar). */
enum InstallmentStatus: string
{
    case Unpaid = 'belum';
    case Paid = 'dibayar';

    public function label(): string
    {
        return $this === self::Paid ? 'Dibayar' : 'Perlu Bayar';
    }

    public function tone(): string
    {
        return $this === self::Paid ? 'success' : 'warning';
    }
}
