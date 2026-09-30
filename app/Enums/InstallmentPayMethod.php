<?php

namespace App\Enums;

/** "Kaedah Bayaran Ansuran" chips in the Pelan Baharu modal. */
enum InstallmentPayMethod: string
{
    case FpxAutoDebit = 'fpx_auto';
    case Card = 'kad';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::FpxAutoDebit => 'FPX Auto-debit',
            self::Card => 'Kad Kredit',
            self::Manual => 'Manual (Transfer)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FpxAutoDebit => 'bank',
            self::Card => 'credit-card',
            self::Manual => 'receipt',
        };
    }

    /** Payment method of the Order created by "Hantar". */
    public function orderMethod(): PaymentMethod
    {
        return $this === self::Manual ? PaymentMethod::BankTransfer : PaymentMethod::Fpx;
    }
}
