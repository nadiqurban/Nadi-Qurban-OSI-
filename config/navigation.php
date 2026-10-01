<?php

use App\Navigation\Badges\ActiveOrders;
use App\Navigation\Badges\PendingPayments;
use App\Navigation\Badges\UnpaidInvoices;

/*
|--------------------------------------------------------------------------
| Sidebar navigation (single source of truth)
|--------------------------------------------------------------------------
| Order, labels and Phosphor icons exactly as design/Dashboard Operasi.dc.html.
| `module` maps to permission "{module}.view" (items hidden without it).
| `always` = shown to every user; `modal` = opens a modal instead of navigating.
| `badge` is an invokable class-string returning ?int (resolved per request;
| kept as strings so `config:cache` works). `title` = page heading used by
| placeholders; `section` = breadcrumb parent.
*/

return [
    [
        'section' => 'UTAMA',
        'items' => [
            ['label' => 'Dashboard', 'icon' => 'squares-four', 'route' => 'dashboard', 'module' => 'dashboard'],
        ],
    ],
    [
        'section' => 'OPERASI',
        'items' => [
            ['label' => 'Bayaran Ansuran', 'icon' => 'calendar-check', 'route' => 'installments.index', 'module' => 'installments'],
            ['label' => 'Tempahan & Pelanggan', 'icon' => 'shopping-cart-simple', 'route' => 'orders.index', 'active' => ['orders.index', 'orders.show'], 'module' => 'orders', 'badge' => ActiveOrders::class],
            ['label' => 'Pengesahan Bayaran', 'icon' => 'seal-check', 'route' => 'payments.verify', 'module' => 'payments', 'badge' => PendingPayments::class],
            ['label' => 'Lafaz Akad', 'icon' => 'hand-heart', 'route' => 'akad.index', 'module' => 'akad'],
            ['label' => 'Agihan Negara', 'icon' => 'globe-hemisphere-west', 'route' => 'allocation.index', 'module' => 'allocation'],
            ['label' => 'Pelaksanaan & Laporan', 'icon' => 'shopping-bag', 'route' => 'execution.index', 'module' => 'execution'],
            ['label' => 'AWB & Postage', 'icon' => 'package', 'route' => 'shipping.index', 'module' => 'shipping'],
            ['label' => 'Tempahan Selesai', 'icon' => 'check-square-offset', 'route' => 'orders.completed', 'module' => 'completed'],
            ['label' => 'Vendor', 'icon' => 'truck', 'route' => 'vendors.index', 'active' => 'vendors.*', 'module' => 'vendors'],
            ['label' => 'Produk', 'icon' => 'cow', 'route' => 'products.index', 'module' => 'products'],
            ['label' => 'Dokumen', 'icon' => 'folders', 'route' => 'documents.index', 'module' => 'documents'],
        ],
    ],
    [
        'section' => 'JUALAN & KEWANGAN',
        'items' => [
            ['label' => 'Sales CRM', 'icon' => 'users-three', 'route' => 'crm.index', 'active' => 'crm.*', 'module' => 'crm'],
            ['label' => 'Kod Promosi', 'icon' => 'ticket', 'route' => 'promo.index', 'module' => 'promo'],
            ['label' => 'Kewangan', 'icon' => 'wallet', 'route' => 'finance.index', 'active' => 'finance.*', 'module' => 'finance', 'badge' => UnpaidInvoices::class],
        ],
    ],
    [
        'section' => 'LAPORAN',
        'items' => [
            ['label' => 'Pusat Laporan', 'icon' => 'chart-bar', 'route' => 'reports.index', 'module' => 'reports'],
            ['label' => 'Audit Log', 'icon' => 'clock-counter-clockwise', 'route' => 'audit.index', 'module' => 'audit'],
        ],
    ],
    [
        'section' => 'SISTEM',
        'items' => [
            ['label' => 'Notifikasi', 'icon' => 'bell', 'route' => 'notifications.index', 'module' => 'notifications', 'badge' => null],
            ['label' => 'Pengguna', 'icon' => 'users', 'route' => 'users.index', 'active' => 'users.*', 'module' => 'users'],
            ['label' => 'Sijil', 'icon' => 'certificate', 'route' => 'certificates.editor', 'module' => 'certificates'],
            ['label' => 'Tetapan', 'icon' => 'gear-six', 'route' => 'settings.profile', 'active' => 'settings.*', 'module' => 'settings', 'always' => true, 'modal' => 'settings'],
        ],
    ],
];
