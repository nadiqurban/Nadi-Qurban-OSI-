<?php

namespace App\Enums;

/** Portal Ansuran "Pilih Kaedah Bayaran" → CHIP Collect method codes. */
enum PortalPayMethod: string
{
    case Fpx = 'fpx';
    case DuitNow = 'duitnow';
    case Card = 'kad';
    case EWallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Fpx => 'FPX Online Banking',
            self::DuitNow => 'DuitNow QR',
            self::Card => 'Kad Kredit / Debit',
            self::EWallet => 'E-Wallet (TnG, GrabPay)',
        };
    }

    /** Short label used on receipts ("Kaedah"). */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Fpx => 'FPX Online Banking',
            self::DuitNow => 'DuitNow QR',
            self::Card => 'Kad Kredit/Debit',
            self::EWallet => 'E-Wallet',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Fpx => 'bank',
            self::DuitNow => 'qr-code',
            self::Card => 'credit-card',
            self::EWallet => 'wallet',
        };
    }

    /** Icon tile colours [tint, colour] from the design. */
    public function tileClasses(): string
    {
        return match ($this) {
            self::Fpx => 'bg-info-soft text-info',
            self::DuitNow => 'bg-[#FEF6F6] text-danger',
            self::Card => 'bg-primary-soft text-primary',
            self::EWallet => 'bg-success-soft text-success',
        };
    }

    /**
     * CHIP payment_method_whitelist codes (first one is also ?preferred=).
     *
     * @return list<string>
     */
    public function chipCodes(): array
    {
        return match ($this) {
            self::Fpx => ['fpx'],
            self::DuitNow => ['duitnow_qr'],
            self::Card => ['visa', 'mastercard', 'maestro'],
            self::EWallet => ['razer_tng', 'razer_grabpay', 'razer_shopeepay'],
        };
    }
}
