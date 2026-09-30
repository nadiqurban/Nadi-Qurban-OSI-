<?php

namespace App\Enums;

/** Purchase Order flow (Vendor.dc.html poFlow + Cancelled). Labels kept in English as in the design. */
enum PoStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /** Badge classes (poStatusBadge). */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-neutral-soft text-neutral',
            self::Sent => 'bg-info-soft text-info',
            self::Accepted => 'bg-[#EAF1EC] text-primary',
            self::InProgress => 'bg-warning-soft text-warning',
            self::Completed => 'bg-success-soft text-success',
            self::Cancelled => 'bg-danger-soft text-danger',
        };
    }

    /** "PO Semasa" pill on the vendor card (apColors — differs from the PO table badge). */
    public function cardBadgeClasses(): string
    {
        return match ($this) {
            self::InProgress => 'bg-info-soft text-info',
            self::Sent => 'bg-warning-soft text-warning',
            self::Accepted, self::Completed => 'bg-success-soft text-success',
            self::Draft => 'bg-neutral-soft text-neutral',
            self::Cancelled => 'bg-danger-soft text-danger',
        };
    }

    /** Stepper order (Cancelled is off the main flow). */
    public function step(): int
    {
        return match ($this) {
            self::Draft, self::Cancelled => 0,
            self::Sent => 1,
            self::Accepted => 2,
            self::InProgress => 3,
            self::Completed => 4,
        };
    }

    /** @return list<self> */
    public static function flow(): array
    {
        return [self::Draft, self::Sent, self::Accepted, self::InProgress, self::Completed];
    }

    /** Counted as "PO Aktif". */
    public function isActive(): bool
    {
        return in_array($this, [self::Sent, self::Accepted, self::InProgress], true);
    }
}
