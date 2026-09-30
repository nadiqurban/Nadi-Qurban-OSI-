<?php

namespace App\Enums;

/**
 * The 23 modules of the permission matrix (Pengguna & Peranan.dc.html, design order).
 * Each module has two permissions: "{value}.view" and "{value}.manage".
 */
enum Module: string
{
    case Dashboard = 'dashboard';
    case Installments = 'installments';
    case Orders = 'orders';
    case Payments = 'payments';
    case Akad = 'akad';
    case Allocation = 'allocation';
    case Execution = 'execution';
    case Shipping = 'shipping';
    case Completed = 'completed';
    case Vendors = 'vendors';
    case Products = 'products';
    case Documents = 'documents';
    case Crm = 'crm';
    case Promo = 'promo';
    case Finance = 'finance';
    case Reports = 'reports';
    case Audit = 'audit';
    case Notifications = 'notifications';
    case Users = 'users';
    case Certificates = 'certificates';
    case Settings = 'settings';
    case Webhooks = 'webhooks';
    case Api = 'api';

    public function label(): string
    {
        return match ($this) {
            self::Dashboard => 'Dashboard',
            self::Installments => 'Bayaran Ansuran',
            self::Orders => 'Tempahan & Pelanggan',
            self::Payments => 'Pengesahan Bayaran',
            self::Akad => 'Lafaz Akad',
            self::Allocation => 'Agihan Negara',
            self::Execution => 'Pelaksanaan & Laporan',
            self::Shipping => 'AWB & Postage',
            self::Completed => 'Tempahan Selesai',
            self::Vendors => 'Vendor',
            self::Products => 'Produk',
            self::Documents => 'Dokumen',
            self::Crm => 'Sales CRM',
            self::Promo => 'Kod Promosi',
            self::Finance => 'Kewangan',
            self::Reports => 'Pusat Laporan',
            self::Audit => 'Audit Log',
            self::Notifications => 'Notifikasi',
            self::Users => 'Pengguna & Peranan',
            self::Certificates => 'Sijil',
            self::Settings => 'Tetapan',
            self::Webhooks => 'Webhooks',
            self::Api => 'Integrasi API',
        };
    }

    /** Phosphor icon name (same icons as the sidebar). */
    public function icon(): string
    {
        return match ($this) {
            self::Dashboard => 'squares-four',
            self::Installments => 'calendar-check',
            self::Orders => 'shopping-cart-simple',
            self::Payments => 'seal-check',
            self::Akad => 'hand-heart',
            self::Allocation => 'globe-hemisphere-west',
            self::Execution => 'shopping-bag',
            self::Shipping => 'package',
            self::Completed => 'check-square-offset',
            self::Vendors => 'truck',
            self::Products => 'cow',
            self::Documents => 'folders',
            self::Crm => 'users-three',
            self::Promo => 'ticket',
            self::Finance => 'wallet',
            self::Reports => 'chart-bar',
            self::Audit => 'clock-counter-clockwise',
            self::Notifications => 'bell',
            self::Users => 'users',
            self::Certificates => 'certificate',
            self::Settings => 'gear-six',
            self::Webhooks => 'webhooks-logo',
            self::Api => 'plugs',
        };
    }

    public function viewPermission(): string
    {
        return $this->value.'.view';
    }

    public function managePermission(): string
    {
        return $this->value.'.manage';
    }

    /** @return list<string> */
    public static function allPermissions(): array
    {
        return collect(self::cases())
            ->flatMap(fn (self $m) => [$m->viewPermission(), $m->managePermission()])
            ->values()
            ->all();
    }
}
