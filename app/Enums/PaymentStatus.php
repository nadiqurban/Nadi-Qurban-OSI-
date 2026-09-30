<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'menunggu';
    case Verified = 'disahkan';
    case Rejected = 'ditolak';

    /** Pengesahan Bayaran status badge. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Semakan',
            self::Verified => 'Bayaran Disahkan',
            self::Rejected => 'Ditolak',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }
}
