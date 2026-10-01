<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/** Invoice status (Kewangan.dc.html st map), derived automatically from payments & due date. */
enum InvoiceStatus: string
{
    case Deposit = 'deposit';
    case Paid = 'dibayar';
    case Outstanding = 'tertunggak';
    case Late = 'lewat';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Paid => 'Dibayar',
            self::Outstanding => 'Tertunggak',
            self::Late => 'Lewat',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Deposit => 'info',
            self::Paid => 'success',
            self::Outstanding => 'warning',
            self::Late => 'danger',
        };
    }

    /** Lewat when past due and not fully paid; otherwise by amount paid. */
    public static function derive(int $total, int $paid, CarbonInterface $due): self
    {
        return match (true) {
            $paid >= $total => self::Paid,
            $due->lt(today()) => self::Late,
            $paid > 0 => self::Deposit,
            default => self::Outstanding,
        };
    }
}
