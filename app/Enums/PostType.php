<?php

namespace App\Enums;

enum PostType: string
{
    case Regular = 'biasa';
    case Express = 'ekspres';
    case Registered = 'berdaftar';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Pos Biasa',
            self::Express => 'Pos Ekspres',
            self::Registered => 'Pos Berdaftar',
        };
    }
}
