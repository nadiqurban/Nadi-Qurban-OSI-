<?php

namespace App\Enums;

/** Sales CRM Kanban columns (Sales CRM.dc.html `columns`). */
enum LeadStage: string
{
    case New = 'baru';
    case Contacted = 'dihubungi';
    case Negotiation = 'rundingan';
    case Proposal = 'cadangan';
    case Closed = 'ditutup';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Lead Baru',
            self::Contacted => 'Dihubungi',
            self::Negotiation => 'Rundingan',
            self::Proposal => 'Cadangan',
            self::Closed => 'Ditutup',
        };
    }

    /** Column dot colour (design hex). */
    public function dot(): string
    {
        return match ($this) {
            self::New => '#94A3AC',
            self::Contacted => '#2563EB',
            self::Negotiation => '#C9A227',
            self::Proposal => '#7C3AED',
            self::Closed => '#16A34A',
        };
    }

    /** Detail-page stage badge tone. */
    public function tone(): string
    {
        return match ($this) {
            self::New => 'neutral',
            self::Contacted => 'info',
            self::Negotiation => 'gold-ink',
            self::Proposal => 'purple',
            self::Closed => 'success',
        };
    }

    public function step(): int
    {
        return (int) array_search($this, self::cases(), true) + 1;
    }
}
