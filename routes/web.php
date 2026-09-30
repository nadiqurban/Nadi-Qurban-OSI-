<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\HomeController;
use App\Livewire\Auth\ForceChangePassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Locked;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\LoginHistory;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Settings\Company;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Users\RoleShow;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication (Login.dc.html — 6 views)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/lupa-kata-laluan', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-kata-laluan/{token}', ResetPassword::class)->name('password.reset');
    Route::livewire('/akaun-dikunci', Locked::class)->name('login.locked');
    Route::livewire('/pengesahan-2fa', TwoFactorChallenge::class)->name('two-factor.challenge');
});

Route::middleware('auth')->group(function () {
    Route::post('/log-keluar', LogoutController::class)->name('logout');
    Route::livewire('/tukar-kata-laluan', ForceChangePassword::class)->name('password.force');
    Route::livewire('/sejarah-log-masuk', LoginHistory::class)->name('login.history');
});

/*
|--------------------------------------------------------------------------
| Application (sidebar modules)
|--------------------------------------------------------------------------
| Every module requires "{module}.view" (matrix Penuh/Lihat). Screens not built
| yet render a placeholder and are replaced in the listed phase.
*/

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    $placeholder = function (string $uri, string $name, string $module, string $title, array $breadcrumb, int $phase, string $icon) {
        Route::view($uri, 'pages.placeholder', compact('title', 'breadcrumb', 'phase', 'icon'))
            ->middleware('can:'.$module.'.view')
            ->name($name);
    };

    $placeholder('/dashboard', 'dashboard', 'dashboard', 'Dashboard', ['Utama', 'Dashboard'], 8, 'squares-four');

    // Operasi
    $placeholder('/ansuran', 'installments.index', 'installments', 'Bayaran Ansuran', ['Operasi', 'Bayaran Ansuran'], 5, 'calendar-check');
    $placeholder('/tempahan', 'orders.index', 'orders', 'Senarai Tempahan', ['Operasi', 'Tempahan & Pelanggan'], 3, 'shopping-cart-simple');
    $placeholder('/tempahan/{order}', 'orders.show', 'orders', 'Butiran Tempahan', ['Operasi', 'Tempahan & Pelanggan'], 3, 'shopping-cart-simple');
    $placeholder('/pengesahan-bayaran', 'payments.verify', 'payments', 'Pengesahan Bayaran', ['Operasi', 'Pengesahan Bayaran'], 3, 'seal-check');
    $placeholder('/lafaz-akad', 'akad.index', 'akad', 'Lafaz Akad', ['Operasi', 'Lafaz Akad'], 4, 'hand-heart');
    $placeholder('/agihan-negara', 'allocation.index', 'allocation', 'Agihan Negara', ['Operasi', 'Agihan Negara'], 4, 'globe-hemisphere-west');
    $placeholder('/pelaksanaan', 'execution.index', 'execution', 'Pelaksanaan & Laporan', ['Operasi', 'Pelaksanaan & Laporan'], 4, 'shopping-bag');
    $placeholder('/awb', 'shipping.index', 'shipping', 'AWB & Postage', ['Operasi', 'AWB & Postage'], 4, 'package');
    $placeholder('/tempahan-selesai', 'orders.completed', 'completed', 'Tempahan Selesai', ['Operasi', 'Tempahan Selesai'], 4, 'check-square-offset');
    $placeholder('/vendor', 'vendors.index', 'vendors', 'Vendor', ['Operasi', 'Vendor'], 6, 'truck');
    $placeholder('/vendor/{vendor}', 'vendors.show', 'vendors', 'Profil Vendor', ['Operasi', 'Vendor'], 6, 'truck');
    $placeholder('/produk', 'products.index', 'products', 'Produk', ['Operasi', 'Produk'], 2, 'cow');
    $placeholder('/dokumen', 'documents.index', 'documents', 'Dokumen', ['Operasi', 'Dokumen'], 7, 'folders');

    // Jualan & Kewangan
    $placeholder('/crm', 'crm.index', 'crm', 'Sales CRM', ['Jualan & Kewangan', 'Sales CRM'], 7, 'users-three');
    $placeholder('/crm/{lead}', 'crm.show', 'crm', 'Butiran Lead', ['Jualan & Kewangan', 'Sales CRM'], 7, 'users-three');
    $placeholder('/kod-promosi', 'promo.index', 'promo', 'Kod Promosi', ['Jualan & Kewangan', 'Kod Promosi'], 2, 'ticket');
    $placeholder('/kewangan', 'finance.index', 'finance', 'Kewangan', ['Jualan & Kewangan', 'Kewangan'], 7, 'wallet');
    $placeholder('/kewangan/invois/{invoice}', 'finance.invoice', 'finance', 'Butiran Invois', ['Jualan & Kewangan', 'Kewangan'], 7, 'wallet');

    // Laporan
    $placeholder('/laporan', 'reports.index', 'reports', 'Pusat Laporan', ['Laporan', 'Pusat Laporan'], 7, 'chart-bar');
    $placeholder('/audit-log', 'audit.index', 'audit', 'Audit Log', ['Laporan', 'Audit Log'], 7, 'clock-counter-clockwise');

    // Sistem
    $placeholder('/notifikasi', 'notifications.index', 'notifications', 'Notifikasi', ['Sistem', 'Notifikasi'], 7, 'bell');
    Route::livewire('/pengguna', UsersIndex::class)->middleware('can:users.view')->name('users.index');
    Route::livewire('/pengguna/peranan/{role}', RoleShow::class)->middleware('can:users.view')->name('users.role');
    $placeholder('/sijil', 'certificates.editor', 'certificates', 'Editor Sijil', ['Sistem', 'Sijil'], 4, 'certificate');

    // Tetapan — profile & security are personal (every user); company needs settings.view.
    Route::livewire('/tetapan/profil', Profile::class)->name('settings.profile');
    Route::livewire('/tetapan/keselamatan', Security::class)->name('settings.security');
    Route::livewire('/tetapan/syarikat', Company::class)->middleware('can:settings.view')->name('settings.company');
    Route::view('/tetapan/notifikasi', 'pages.placeholder', ['title' => 'Notifikasi', 'breadcrumb' => ['Tetapan', 'Notifikasi'], 'phase' => 7, 'icon' => 'bell'])->name('settings.notifications');
    $placeholder('/tetapan/integrasi', 'settings.integrations', 'api', 'Integrasi API', ['Tetapan', 'Integrasi API'], 9, 'plugs-connected');
    $placeholder('/tetapan/webhooks', 'settings.webhooks', 'webhooks', 'Webhooks', ['Tetapan', 'Webhooks'], 9, 'webhooks-logo');
});

// Design system review (local only)
if (app()->environment('local', 'testing')) {
    Route::view('/_design/components', 'design.components')->name('design.components');
    Route::view('/_design/auth', 'design.auth')->name('design.auth');
    Route::view('/_design/public', 'design.public')->name('design.public');
}
