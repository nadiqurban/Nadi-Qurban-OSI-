<?php

namespace App\Enums;

/** Notifikasi preference rows (Notifikasi.dc.html state.prefs) with their default channels. */
enum NotificationType: string
{
    case NewOrder = 'tempahan_baharu';
    case PaymentVerified = 'pengesahan_bayaran';
    case ExecutionReport = 'laporan_pelaksanaan';
    case CertificateReady = 'sijil_siap';
    case PaymentOverdue = 'bayaran_tertunggak';
    case SystemAlert = 'amaran_sistem';

    public function label(): string
    {
        return match ($this) {
            self::NewOrder => 'Tempahan baharu',
            self::PaymentVerified => 'Pengesahan bayaran',
            self::ExecutionReport => 'Laporan pelaksanaan',
            self::CertificateReady => 'Sijil siap',
            self::PaymentOverdue => 'Bayaran tertunggak',
            self::SystemAlert => 'Amaran sistem',
        };
    }

    /** @return array{app: bool, mail: bool, wa: bool} */
    public function defaults(): array
    {
        return match ($this) {
            self::NewOrder => ['app' => true, 'mail' => true, 'wa' => false],
            self::PaymentVerified => ['app' => true, 'mail' => true, 'wa' => true],
            self::ExecutionReport => ['app' => true, 'mail' => false, 'wa' => false],
            self::CertificateReady => ['app' => true, 'mail' => true, 'wa' => true],
            self::PaymentOverdue => ['app' => true, 'mail' => true, 'wa' => false],
            self::SystemAlert => ['app' => true, 'mail' => false, 'wa' => false],
        };
    }

    /** Feed category (tag + icon) used when a notification does not set its own. */
    public function category(): string
    {
        return match ($this) {
            self::NewOrder => 'Tempahan',
            self::PaymentVerified => 'Kewangan',
            self::ExecutionReport => 'Vendor',
            self::CertificateReady => 'Sijil',
            self::PaymentOverdue, self::SystemAlert => 'Sistem',
        };
    }

    /**
     * Feed categories: [icon, tone] (design iconMap / tags).
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function categories(): array
    {
        return [
            'Tempahan' => ['shopping-cart-simple', 'primary'],
            'Kewangan' => ['money', 'success'],
            'Vendor' => ['truck', 'gold'],
            'Sijil' => ['certificate', 'purple'],
            'Sistem' => ['warning', 'danger'],
        ];
    }
}
