<?php

namespace App\Enums;

/** Couriers for waybill / AWB with consignment prefixes (design `pre` map). */
enum Courier: string
{
    case PosLaju = 'pos_laju';
    case JntExpress = 'jnt';
    case Dhl = 'dhl';
    case Gdex = 'gdex';
    case Aramex = 'aramex';
    case NinjaVan = 'ninja_van';

    public function label(): string
    {
        return match ($this) {
            self::PosLaju => 'Pos Laju',
            self::JntExpress => 'J&T Express',
            self::Dhl => 'DHL Express',
            self::Gdex => 'GDEX',
            self::Aramex => 'Aramex',
            self::NinjaVan => 'Ninja Van',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::PosLaju => 'EP',
            self::JntExpress => 'JT',
            self::Dhl => 'DHL',
            self::Gdex => 'GDEX',
            self::Aramex => 'ARX',
            self::NinjaVan => 'NV',
        };
    }

    /** Placeholder consignment number until real courier integration (Phase 2: EasyParcel). */
    public function consignmentFor(string $orderNo): string
    {
        $digits = preg_replace('/\D/', '', $orderNo) ?? '';

        return $this->prefix().str_pad(substr($digits, -6), 6, '0', STR_PAD_LEFT).'MY';
    }
}
