<?php

namespace App\Enums;

/** Vendor tier (levelColors); derived from the 1–10 rank on the Prestasi tab. */
enum VendorLevel: string
{
    case Platinum = 'platinum';
    case Gold = 'gold';
    case Silver = 'silver';
    case Bronze = 'bronze';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Platinum => 'bg-[#EEF0F4] text-ink-3',
            self::Gold => 'bg-gold-soft text-gold-ink',
            self::Silver => 'bg-[#EDEEF0] text-muted',
            self::Bronze => 'bg-[#F5E6D8] text-[#9a5b16]',
        };
    }

    /** 9–10 Platinum · 7–8 Gold · 5–6 Silver · 1–4 Bronze. */
    public static function fromRank(int $rank): self
    {
        return match (true) {
            $rank >= 9 => self::Platinum,
            $rank >= 7 => self::Gold,
            $rank >= 5 => self::Silver,
            default => self::Bronze,
        };
    }
}
