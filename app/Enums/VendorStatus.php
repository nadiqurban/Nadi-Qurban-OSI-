<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Active = 'aktif';
    case Pending = 'pending';
    case Suspended = 'digantung';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Pending => 'Pending',
            self::Suspended => 'Digantung',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Suspended => 'danger',
        };
    }
}
