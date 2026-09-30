<?php

namespace App\Enums;

/** Order list status badge (Tempahan & Pelanggan.dc.html `st` map). Distinct from the stage. */
enum OrderStatus: string
{
    case Draft = 'draf';
    case AwaitingPayment = 'menunggu_bayaran';
    case Accepted = 'diterima';
    case InProgress = 'dalam_proses';
    case Completed = 'selesai';
    case Cancelled = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::AwaitingPayment => 'Menunggu Bayaran',
            self::Accepted => 'Diterima',
            self::InProgress => 'Dalam Proses',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::AwaitingPayment => 'warning',
            self::Accepted, self::Completed => 'success',
            self::InProgress => 'info',
            self::Cancelled => 'danger',
        };
    }

    /** Dot colour in the "Kemaskini Status" menu. */
    public function dotClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-neutral',
            self::AwaitingPayment => 'bg-warning',
            self::Accepted, self::Completed => 'bg-success',
            self::InProgress => 'bg-info',
            self::Cancelled => 'bg-danger',
        };
    }

    /**
     * Statuses staff may set from "Kemaskini Status" (design order). Diterima has its own
     * button; Selesai is reached through the pipeline, not set by hand.
     *
     * @return list<self>
     */
    public static function manual(): array
    {
        return [self::Draft, self::InProgress, self::AwaitingPayment, self::Cancelled];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
