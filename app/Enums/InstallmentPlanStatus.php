<?php

namespace App\Enums;

/** Instalment plan state (Bayaran Ansuran.dc.html stMap). */
enum InstallmentPlanStatus: string
{
    case Ongoing = 'berjalan';
    case Late = 'lewat';
    case Completed = 'selesai';
    case Cancelled = 'batal';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Berjalan',
            self::Late => 'Lewat Bayar',
            self::Completed => 'Selesai',
            self::Cancelled => 'Batal',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Ongoing => 'info',
            self::Late, self::Cancelled => 'danger',
            self::Completed => 'success',
        };
    }

    /** Progress-bar segment colour for paid months. */
    public function barClass(): string
    {
        return match ($this) {
            self::Ongoing => 'bg-info',
            self::Late, self::Cancelled => 'bg-danger',
            self::Completed => 'bg-success',
        };
    }
}
