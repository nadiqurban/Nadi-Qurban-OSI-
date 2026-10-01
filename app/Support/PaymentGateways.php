<?php

namespace App\Support;

/**
 * Tetapan › Integrasi → "Gerbang Pembayaran — Kutipan". Credentials live in
 * settings (secrets encrypted). Status: Tidak Aktif (switched off) /
 * Aktif (credentials saved) / Belum Sambung.
 */
final class PaymentGateways
{
    public const CHIP_METHODS = ['FPX' => 'bank', 'Kad Kredit/Debit' => 'credit-card', 'e-Wallet' => 'wallet', 'DuitNow QR' => 'qr-code'];

    /** gateway => [label, icon, tone, description, endpoint, required keys] */
    public const GATEWAYS = [
        'chip' => ['CHIP IN', 'credit-card', 'success', 'FPX, kad kredit/debit, e-Wallet & DuitNow QR untuk kutipan bayaran pelanggan.', ['chip.brand_id', 'chip.secret_key']],
        'billplz' => ['Billplz', 'money', 'info', 'FPX & kad kredit melalui Billplz Collections untuk kutipan bayaran.', ['billplz.secret_key', 'billplz.collection_id']],
        'toyyibpay' => ['toyyibPay', 'wallet', 'warning', 'FPX & kad kredit melalui toyyibPay Category/Bill untuk kutipan bayaran.', ['toyyibpay.secret_key', 'toyyibpay.category_code']],
    ];

    public function __construct(private readonly Settings $settings) {}

    public function enabled(string $gateway): bool
    {
        return (string) $this->settings->get($gateway.'.enabled', '1') !== '0';
    }

    public function configured(string $gateway): bool
    {
        foreach (self::GATEWAYS[$gateway][4] as $key) {
            if ((string) $this->settings->get($key) === '') {
                return false;
            }
        }

        return true;
    }

    /** @return array{0: string, 1: string} label, tone */
    public function status(string $gateway): array
    {
        return match (true) {
            ! $this->enabled($gateway) => ['Tidak Aktif', 'neutral'],
            $this->configured($gateway) => ['Aktif', 'success'],
            default => ['Belum Sambung', 'neutral'],
        };
    }

    public function endpoint(string $gateway): string
    {
        return match ($gateway) {
            'chip' => 'https://gate.chip-in.asia/api/v1',
            'billplz' => 'https://www.billplz.com/api/v3',
            default => $this->settings->get('toyyibpay.env', 'production') === 'sandbox' ? 'https://dev.toyyibpay.com' : 'https://toyyibpay.com',
        };
    }

    /** @return list<string> CHIP methods currently switched on */
    public function chipMethods(): array
    {
        $stored = $this->settings->get('chip.methods');

        return $stored === null ? array_keys(self::CHIP_METHODS) : array_values(array_intersect(array_keys(self::CHIP_METHODS), (array) json_decode((string) $stored, true)));
    }
}
