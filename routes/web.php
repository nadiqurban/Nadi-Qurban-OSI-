<?php

use App\Enums\Courier;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ChipWebhookController;
use App\Http\Controllers\ExecutionMediaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallmentDocumentController;
use App\Http\Controllers\OrderDocumentController;
use App\Livewire\Akad\Index as AkadIndex;
use App\Livewire\Allocation\Index as AllocationIndex;
use App\Livewire\Auth\ForceChangePassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Locked;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\LoginHistory;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Certificates\Editor as CertificateEditor;
use App\Livewire\Execution\Index as ExecutionIndex;
use App\Livewire\Installments\Index as InstallmentsIndex;
use App\Livewire\Orders\Completed as OrdersCompleted;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Orders\Show as OrdersShow;
use App\Livewire\Payments\Verify as PaymentsVerify;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Promo\Index as PromoIndex;
use App\Livewire\Public\InstallmentPortal;
use App\Livewire\Public\Tracking;
use App\Livewire\Settings\Company;
use App\Livewire\Settings\Integrations;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Livewire\Shipping\Index as ShippingIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Users\RoleShow;
use App\Models\Order;
use App\Support\ParticipantGroups;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication (Login.dc.html — 6 views)
|--------------------------------------------------------------------------
*/

// Public: customer tracking (masked names unless ?t=token), rate-limited in the component.
Route::livewire('/jejak', Tracking::class)->name('tracking');

// Public: instalment portal (one permanent token link per plan) + CHIP Collect callback.
Route::livewire('/bayar/{token}', InstallmentPortal::class)->where('token', '[A-Za-z0-9]{32,64}')->name('installments.portal');
Route::get('/bayar/{token}/resit/{reference}.pdf', [InstallmentDocumentController::class, 'portalReceipt'])
    ->where(['token' => '[A-Za-z0-9]{32,64}', 'reference' => 'NQPAY[0-9]+'])->middleware('throttle:30,1')->name('installments.portal.receipt');
Route::post('/webhooks/chip', ChipWebhookController::class)->middleware('throttle:120,1')->name('webhooks.chip');

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
    Route::middleware('can:installments.view')->group(function () {
        Route::livewire('/ansuran', InstallmentsIndex::class)->name('installments.index');
        Route::get('/ansuran/{plan}/resit.pdf', [InstallmentDocumentController::class, 'receipt'])->name('installments.receipt');
    });
    // Participant groups PDF: Tempahan (orders.view) and Agihan Negara (allocation.view); checked in the controller.
    Route::get('/tempahan/senarai-peserta.pdf', [OrderDocumentController::class, 'participants'])->name('orders.participants.pdf');
    Route::middleware('can:orders.view')->group(function () {
        Route::livewire('/tempahan', OrdersIndex::class)->name('orders.index');
        Route::get('/tempahan/waybill.pdf', [OrderDocumentController::class, 'waybill'])->name('orders.waybill.pdf');
        Route::livewire('/tempahan/{order}', OrdersShow::class)->name('orders.show');
        Route::get('/tempahan/{order}/resit.pdf', [OrderDocumentController::class, 'receipt'])->name('orders.receipt');
    });
    Route::get('/bukti-bayaran/{payment}', [OrderDocumentController::class, 'proof'])->middleware('signed')->name('payments.proof');
    Route::livewire('/pengesahan-bayaran', PaymentsVerify::class)->middleware('can:payments.view')->name('payments.verify');
    Route::livewire('/lafaz-akad', AkadIndex::class)->middleware('can:akad.view')->name('akad.index');
    Route::livewire('/agihan-negara', AllocationIndex::class)->middleware('can:allocation.view')->name('allocation.index');
    Route::livewire('/pelaksanaan', ExecutionIndex::class)->middleware('can:execution.view')->name('execution.index');
    Route::get('/bukti-pelaksanaan/{media}', ExecutionMediaController::class)->middleware('signed')->name('execution.media');
    Route::middleware('can:shipping.view')->group(function () {
        Route::livewire('/awb', ShippingIndex::class)->name('shipping.index');
        Route::get('/awb/airway-bill.pdf', [OrderDocumentController::class, 'airwayBill'])->name('shipping.awb.pdf');
    });
    Route::livewire('/tempahan-selesai', OrdersCompleted::class)->middleware('can:completed.view')->name('orders.completed');
    $placeholder('/vendor', 'vendors.index', 'vendors', 'Vendor', ['Operasi', 'Vendor'], 6, 'truck');
    $placeholder('/vendor/{vendor}', 'vendors.show', 'vendors', 'Profil Vendor', ['Operasi', 'Vendor'], 6, 'truck');
    Route::livewire('/produk', ProductsIndex::class)->middleware('can:products.view')->name('products.index');
    $placeholder('/dokumen', 'documents.index', 'documents', 'Dokumen', ['Operasi', 'Dokumen'], 7, 'folders');

    // Jualan & Kewangan
    $placeholder('/crm', 'crm.index', 'crm', 'Sales CRM', ['Jualan & Kewangan', 'Sales CRM'], 7, 'users-three');
    $placeholder('/crm/{lead}', 'crm.show', 'crm', 'Butiran Lead', ['Jualan & Kewangan', 'Sales CRM'], 7, 'users-three');
    Route::livewire('/kod-promosi', PromoIndex::class)->middleware('can:promo.view')->name('promo.index');
    $placeholder('/kewangan', 'finance.index', 'finance', 'Kewangan', ['Jualan & Kewangan', 'Kewangan'], 7, 'wallet');
    $placeholder('/kewangan/invois/{invoice}', 'finance.invoice', 'finance', 'Butiran Invois', ['Jualan & Kewangan', 'Kewangan'], 7, 'wallet');

    // Laporan
    $placeholder('/laporan', 'reports.index', 'reports', 'Pusat Laporan', ['Laporan', 'Pusat Laporan'], 7, 'chart-bar');
    $placeholder('/audit-log', 'audit.index', 'audit', 'Audit Log', ['Laporan', 'Audit Log'], 7, 'clock-counter-clockwise');

    // Sistem
    $placeholder('/notifikasi', 'notifications.index', 'notifications', 'Notifikasi', ['Sistem', 'Notifikasi'], 7, 'bell');
    Route::livewire('/pengguna', UsersIndex::class)->middleware('can:users.view')->name('users.index');
    Route::livewire('/pengguna/peranan/{role}', RoleShow::class)->middleware('can:users.view')->name('users.role');
    Route::livewire('/sijil', CertificateEditor::class)->middleware('can:certificates.view')->name('certificates.editor');

    // Tetapan — profile & security are personal (every user); company needs settings.view.
    Route::livewire('/tetapan/profil', Profile::class)->name('settings.profile');
    Route::livewire('/tetapan/keselamatan', Security::class)->name('settings.security');
    Route::livewire('/tetapan/syarikat', Company::class)->middleware('can:settings.view')->name('settings.company');
    Route::view('/tetapan/notifikasi', 'pages.placeholder', ['title' => 'Notifikasi', 'breadcrumb' => ['Tetapan', 'Notifikasi'], 'phase' => 7, 'icon' => 'bell'])->name('settings.notifications');
    Route::livewire('/tetapan/integrasi', Integrations::class)->middleware('can:api.view')->name('settings.integrations');
    $placeholder('/tetapan/webhooks', 'settings.webhooks', 'webhooks', 'Webhooks', ['Tetapan', 'Webhooks'], 9, 'webhooks-logo');
});

// Design system review (local only)
if (app()->environment('local', 'testing')) {
    Route::view('/_design/components', 'design.components')->name('design.components');
    Route::view('/_design/auth', 'design.auth')->name('design.auth');
    Route::view('/_design/public', 'design.public')->name('design.public');

    // HTML previews of the PDF templates (same Blade as the PDF), for visual review.
    Route::middleware('auth')->group(function () {
        Route::get('/_design/pdf/resit/{order}', fn (Order $order) => view('pdf.order-receipt', ['order' => $order->load(['customer', 'country', 'participants'])]));
        Route::get('/_design/pdf/waybill/{order}', fn (Order $order) => view('pdf.waybill', ['orders' => collect([$order->load(['customer', 'country'])]), 'courier' => Courier::PosLaju]));
        Route::get('/_design/pdf/peserta/{order}', fn (Order $order) => view('pdf.participant-groups', [
            'groups' => ParticipantGroups::for(collect([$order->load(['customer', 'country', 'participants'])])),
            'date' => '18-06-2027',
            'tag' => ParticipantGroups::tag(collect([$order])),
        ]));
    });
}
