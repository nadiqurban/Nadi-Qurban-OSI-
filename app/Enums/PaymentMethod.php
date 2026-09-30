<?php

namespace App\Enums;

/** Jenis Bayaran chips in the new-order modal (+ ansuran for instalment orders). */
enum PaymentMethod: string
{
    case Fpx = 'fpx';
    case Cheque = 'cek';
    case BankTransfer = 'pindahan_bank';

    public function label(): string
    {
        return match ($this) {
            self::Fpx => 'FPX Payment',
            self::Cheque => 'Cek',
            self::BankTransfer => 'Pindahan Bank',
        };
    }

    public function shortLabel(): string
    {
        return $this === self::Fpx ? 'FPX' : $this->label();
    }

    public function icon(): string
    {
        return match ($this) {
            self::Fpx => 'bank',
            self::Cheque => 'note',
            self::BankTransfer => 'arrows-left-right',
        };
    }
}
