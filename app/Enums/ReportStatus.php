<?php

namespace App\Enums;

/** Execution report state (Pelaksanaan & Laporan.dc.html statusMap). */
enum ReportStatus: string
{
    case Pending = 'menunggu_pelaksanaan';
    case Review = 'menunggu_semakan';
    case Verified = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pelaksanaan',
            self::Review => 'Menunggu Semakan HQ',
            self::Verified => 'Selesai',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'neutral',
            self::Review => 'warning',
            self::Verified => 'success',
        };
    }
}
