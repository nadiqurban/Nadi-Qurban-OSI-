<?php

namespace App\Enums;

/** Vendor payment (Vendor.dc.html payFlow). */
enum VendorPaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Payment',
            self::Processing => 'Payment Processing',
            self::Completed => 'Payment Completed',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
        };
    }

    public function step(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Processing => 1,
            self::Completed => 2,
        };
    }
}
