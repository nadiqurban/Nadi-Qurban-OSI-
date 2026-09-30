<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'aktif';
    case Suspended = 'digantung';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Suspended => 'Digantung',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'danger',
        };
    }
}
