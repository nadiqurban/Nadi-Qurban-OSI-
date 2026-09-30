<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Phase 0: app shell + placeholder screens
|--------------------------------------------------------------------------
| Every module route from PRD §4 exists so the sidebar is navigable. Each
| placeholder is replaced by its Livewire component in the listed phase.
| Auth middleware + permissions are added in Phase 1.
*/

Route::redirect('/', '/dashboard');

$placeholder = function (string $uri, string $name, string $title, array $breadcrumb, int $phase, string $icon) {
    Route::view($uri, 'pages.placeholder', compact('title', 'breadcrumb', 'phase', 'icon'))->name($name);
};

$placeholder('/dashboard', 'dashboard', 'Dashboard', ['Utama', 'Dashboard'], 8, 'squares-four');

// Operasi
$placeholder('/ansuran', 'installments.index', 'Bayaran Ansuran', ['Operasi', 'Bayaran Ansuran'], 5, 'calendar-check');
$placeholder('/tempahan', 'orders.index', 'Senarai Tempahan', ['Operasi', 'Tempahan & Pelanggan'], 3, 'shopping-cart-simple');
$placeholder('/tempahan/{order}', 'orders.show', 'Butiran Tempahan', ['Operasi', 'Tempahan & Pelanggan'], 3, 'shopping-cart-simple');
$placeholder('/pengesahan-bayaran', 'payments.verify', 'Pengesahan Bayaran', ['Operasi', 'Pengesahan Bayaran'], 3, 'seal-check');
$placeholder('/lafaz-akad', 'akad.index', 'Lafaz Akad', ['Operasi', 'Lafaz Akad'], 4, 'hand-heart');
$placeholder('/agihan-negara', 'allocation.index', 'Agihan Negara', ['Operasi', 'Agihan Negara'], 4, 'globe-hemisphere-west');
$placeholder('/pelaksanaan', 'execution.index', 'Pelaksanaan & Laporan', ['Operasi', 'Pelaksanaan & Laporan'], 4, 'shopping-bag');
$placeholder('/awb', 'shipping.index', 'AWB & Postage', ['Operasi', 'AWB & Postage'], 4, 'package');
$placeholder('/tempahan-selesai', 'orders.completed', 'Tempahan Selesai', ['Operasi', 'Tempahan Selesai'], 4, 'check-square-offset');
$placeholder('/vendor', 'vendors.index', 'Vendor', ['Operasi', 'Vendor'], 6, 'truck');
$placeholder('/vendor/{vendor}', 'vendors.show', 'Profil Vendor', ['Operasi', 'Vendor'], 6, 'truck');
$placeholder('/produk', 'products.index', 'Produk', ['Operasi', 'Produk'], 2, 'cow');
$placeholder('/dokumen', 'documents.index', 'Dokumen', ['Operasi', 'Dokumen'], 7, 'folders');

// Jualan & Kewangan
$placeholder('/crm', 'crm.index', 'Sales CRM', ['Jualan & Kewangan', 'Sales CRM'], 7, 'users-three');
$placeholder('/crm/{lead}', 'crm.show', 'Butiran Lead', ['Jualan & Kewangan', 'Sales CRM'], 7, 'users-three');
$placeholder('/kod-promosi', 'promo.index', 'Kod Promosi', ['Jualan & Kewangan', 'Kod Promosi'], 2, 'ticket');
$placeholder('/kewangan', 'finance.index', 'Kewangan', ['Jualan & Kewangan', 'Kewangan'], 7, 'wallet');
$placeholder('/kewangan/invois/{invoice}', 'finance.invoice', 'Butiran Invois', ['Jualan & Kewangan', 'Kewangan'], 7, 'wallet');

// Laporan
$placeholder('/laporan', 'reports.index', 'Pusat Laporan', ['Laporan', 'Pusat Laporan'], 7, 'chart-bar');
$placeholder('/audit-log', 'audit.index', 'Audit Log', ['Laporan', 'Audit Log'], 7, 'clock-counter-clockwise');

// Sistem
$placeholder('/notifikasi', 'notifications.index', 'Notifikasi', ['Sistem', 'Notifikasi'], 7, 'bell');
$placeholder('/pengguna', 'users.index', 'Pengguna & Peranan', ['Sistem', 'Pengguna'], 1, 'users');
$placeholder('/pengguna/peranan/{role}', 'users.role', 'Butiran Peranan', ['Sistem', 'Pengguna'], 1, 'users');
$placeholder('/sijil', 'certificates.editor', 'Editor Sijil', ['Sistem', 'Sijil'], 4, 'certificate');
$placeholder('/tetapan/profil', 'settings.profile', 'Profil & Akaun', ['Tetapan', 'Profil & Akaun'], 1, 'user-circle');
$placeholder('/tetapan/syarikat', 'settings.company', 'Maklumat Syarikat', ['Tetapan', 'Maklumat Syarikat'], 1, 'buildings');
$placeholder('/tetapan/keselamatan', 'settings.security', 'Keselamatan', ['Tetapan', 'Keselamatan'], 1, 'shield-check');
$placeholder('/tetapan/notifikasi', 'settings.notifications', 'Notifikasi', ['Tetapan', 'Notifikasi'], 7, 'bell');
$placeholder('/tetapan/integrasi', 'settings.integrations', 'Integrasi API', ['Tetapan', 'Integrasi API'], 9, 'plugs-connected');
$placeholder('/tetapan/webhooks', 'settings.webhooks', 'Webhooks', ['Tetapan', 'Webhooks'], 9, 'webhooks-logo');

// Design system review (local only)
if (app()->environment('local', 'testing')) {
    Route::view('/_design/components', 'design.components')->name('design.components');
    Route::view('/_design/auth', 'design.auth')->name('design.auth');
    Route::view('/_design/public', 'design.public')->name('design.public');
}
