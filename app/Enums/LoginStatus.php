<?php

namespace App\Enums;

enum LoginStatus: string
{
    case Success = 'berjaya';
    case Failed = 'gagal';
    case Locked = 'dikunci';

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Berjaya',
            self::Failed => 'Gagal',
            self::Locked => 'Dikunci',
        };
    }
}
