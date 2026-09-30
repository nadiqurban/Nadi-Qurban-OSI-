<?php

namespace App\Enums;

/** Ibadah service (PRD §9). Code is used in order/product numbers: NQ-{SVC}-{ANI}-… */
enum Service: string
{
    case Qurban = 'qurban';
    case Aqiqah = 'aqiqah';
    case Dam = 'dam';
    case Nazar = 'nazar';

    public function label(): string
    {
        return match ($this) {
            self::Qurban => 'Qurban',
            self::Aqiqah => 'Aqiqah',
            self::Dam => 'Dam',
            self::Nazar => 'Nazar Haiwan',
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::Qurban => 'QB',
            self::Aqiqah => 'AQ',
            self::Dam => 'DM',
            self::Nazar => 'NZ',
        };
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
