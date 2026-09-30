<?php

namespace App\Enums;

/**
 * Animal (PRD §9). Capacity = shares per animal: Lembu & Unta 7, Kambing 1
 * (used for participant groups).
 */
enum Animal: string
{
    case Cow = 'lembu';
    case Goat = 'kambing';
    case Camel = 'unta';

    public function label(): string
    {
        return match ($this) {
            self::Cow => 'Lembu',
            self::Goat => 'Kambing',
            self::Camel => 'Unta',
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::Cow => 'LE',
            self::Goat => 'KA',
            self::Camel => 'UN',
        };
    }

    public function capacity(): int
    {
        return $this === self::Goat ? 1 : 7;
    }

    /** Product card header tint + icon colour (Produk.dc.html `tint` / `color`). */
    public function cardClasses(): string
    {
        return match ($this) {
            self::Cow => 'bg-primary-soft text-primary',
            self::Goat => 'bg-gold-soft text-[#9a7b12]',
            self::Camel => 'bg-info-soft text-info',
        };
    }

    /** Package ribbon text colour on the white pill. */
    public function inkClass(): string
    {
        return match ($this) {
            self::Cow => 'text-primary',
            self::Goat => 'text-[#9a7b12]',
            self::Camel => 'text-info',
        };
    }

    /** CSS mask for the animal silhouette (cowMask / goatMask / camelMask in Produk.dc.html). */
    public function maskClass(): string
    {
        return 'nq-mask-'.$this->value;
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $a) => [$a->value => $a->label()])->all();
    }
}
