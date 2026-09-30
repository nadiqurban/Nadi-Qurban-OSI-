<?php

namespace App\Enums;

/** Vendor report (Laporan tab → HQ Verify). */
enum VendorReportStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Verified = 'verified';
    case Revision = 'revision';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Belum Dihantar',
            self::Submitted => 'Menunggu Semakan HQ',
            self::Verified => 'Verified — Completed',
            self::Revision => 'Perlu Semakan Semula',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Submitted => 'warning',
            self::Verified => 'success',
            self::Revision => 'danger',
        };
    }
}
