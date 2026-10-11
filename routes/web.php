<?php

use App\Enums\Courier;
use App\Http\Controllers\AgentDocumentController;
use App\Http\Controllers\AgentPhotoController;
use App\Http\Controllers\AgentProofController;
use App\Http\Controllers\AuditExportController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\BookingDocumentController;
use App\Http\Controllers\ChipWebhookController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExecutionMediaController;
use App\Http\Controllers\FinanceDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstallmentDocumentController;
use App\Http\Controllers\OrderDocumentController;
use App\Http\Controllers\ReportDownloadController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\VendorDocumentController;
use App\Livewire\Agent\Login as AgentLogin;
use App\Livewire\Agent\Portal as AgentPortal;
use App\Livewire\Agents\Index as AgentsIndex;
use App\Livewire\Akad\Index as AkadIndex;
use App\Livewire\Allocation\Index as AllocationIndex;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\Auth\ForceChangePassword;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Locked;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\LoginHistory;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Certificates\Editor as CertificateEditor;
use App\Livewire\Crm\Pipeline as CrmPipeline;
use App\Livewire\Crm\Show as CrmShow;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Execution\Index as ExecutionIndex;
use App\Livewire\Finance\Index as FinanceIndex;
use App\Livewire\Finance\InvoiceShow as FinanceInvoiceShow;
use App\Livewire\Installments\Index as InstallmentsIndex;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Orders\Completed as OrdersCompleted;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Orders\Show as OrdersShow;
use App\Livewire\Payments\Verify as PaymentsVerify;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Promo\Index as PromoIndex;
use App\Livewire\Public\AgentRegister;
use App\Livewire\Public\Booking;
use App\Livewire\Public\BookingReceipt;
use App\Livewire\Public\InstallmentPortal;
use App\Livewire\Public\Tracking;
use App\Livewire\Reports\Index as ReportsIndex;
use App\Livewire\Settings\Company;
use App\Livewire\Settings\Integrations;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Security;
use App\Livewire\Settings\Webhooks;
use App\Livewire\Shipping\Index as ShippingIndex;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Users\RoleShow;
use App\Livewire\Vendors\Index as VendorsIndex;
use App\Livewire\Vendors\Show as VendorsShow;
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

// Tempahan Awam (public booking) — own page or an agent's link.
Route::middleware('throttle:60,1')->group(function () {
    Route::livewire('/tempah', Booking::class)->name('booking');
    Route::livewire('/tempah/{slug}', Booking::class)->where('slug', '(?!resit$)[A-Za-z0-9-]{1,80}')->name('booking.agent');
    // Old agent links (/e/nama-ejen) keep working.
    Route::get('/e/{slug}', fn (string $slug) => redirect()->route('booking.agent', ['slug' => $slug] + request()->query(), 301))->where('slug', '[a-z0-9-]{1,80}');
    Route::livewire('/tempah/resit/{token}', BookingReceipt::class)->where('token', '[A-Za-z0-9]{40}')->name('booking.receipt');
    Route::get('/tempah/resit/{token}/resit.pdf', [BookingDocumentController::class, 'receipt'])->where('token', '[A-Za-z0-9]{40}')->name('booking.receipt.pdf');
});

// Pendaftaran Ejen (public sign-up, approved by HQ in Pengurusan Ejen).
Route::livewire('/daftar-ejen', AgentRegister::class)->middleware('throttle:30,1')->name('agent.register');

// Public: instalment portal (one permanent token link per plan) + CHIP Collect callback.
Route::livewire('/bayar/{token}', InstallmentPortal::class)->where('token', '[A-Za-z0-9]{32,64}')->name('installments.portal');
Route::get('/bayar/{token}/resit/{reference}.pdf', [InstallmentDocumentController::class, 'portalReceipt'])
    ->where(['token' => '[A-Za-z0-9]{32,64}', 'reference' => 'NQPAY[0-9]+'])->middleware('throttle:30,1')->name('installments.portal.receipt');
Route::post('/webhooks/chip', ChipWebhookController::class)->middleware('throttle:120,1')->name('webhooks.chip');

// Log Masuk Ejen: open even while a staff member is signed in (Pengurusan Ejen › Portal Ejen).
Route::livewire('/ejen', AgentLogin::class)->name('agent.login');

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/lupa-kata-laluan', ForgotPassword::class)->name('password.request');
    Route::livewire('/reset-kata-laluan/{token}', ResetPassword::class)->name('password.reset');
    Route::livewire('/akaun-dikunci', Locked::class)->name('login.locked');
    Route::livewire('/pengesahan-2fa', TwoFactorChallenge::class)->name('two-factor.challenge');
});

Route::middleware('auth')->group(function () {
    Route::post('/log-keluar', LogoutController::class)->name('logout');
    Route::middleware('role:Ejen')->group(function () {
        Route::livewire('/ejen/portal', AgentPortal::class)->name('agent.portal');
        Route::get('/ejen/bukti/{payment}', AgentProofController::class)->name('agent.proof');
    });
    Route::livewire('/tukar-kata-laluan', ForceChangePassword::class)->name('password.force');
    Route::livewire('/sejarah-log-masuk', LoginHistory::class)->name('login.history');
});

/*
|--------------------------------------------------------------------------
| Application (sidebar modules)
|--------------------------------------------------------------------------
| Every module requires "{module}.view" (matrix Penuh/Lihat); every Livewire
| action re-checks "{module}.manage" before changing data.
*/

Route::middleware('auth')->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::livewire('/dashboard', Dashboard::class)->middleware('can:dashboard.view')->name('dashboard');

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
    Route::middleware('can:vendors.view')->group(function () {
        Route::livewire('/vendor', VendorsIndex::class)->name('vendors.index');
        Route::livewire('/vendor/{vendor}', VendorsShow::class)->name('vendors.show');
    });
    // PO receipt + payment files: Vendor (vendors.view) and Kewangan (finance.view); checked in the controller.
    Route::get('/vendor/po/{po}/resit.pdf', [VendorDocumentController::class, 'purchaseOrder'])->name('vendors.po.pdf');
    Route::get('/fail-vendor/{media}', [VendorDocumentController::class, 'media'])->middleware('signed')->name('vendors.media');
    Route::livewire('/produk', ProductsIndex::class)->middleware('can:products.view')->name('products.index');
    Route::middleware('can:documents.view')->group(function () {
        Route::livewire('/dokumen', DocumentsIndex::class)->name('documents.index');
        Route::get('/dokumen/{document}/buka', [DocumentController::class, 'open'])->name('documents.open');
    });
    // Single certificate PDF (Dokumen / Sijil / Tempahan viewers); checked in the controller.
    Route::get('/sijil/{certificate}/pdf', [DocumentController::class, 'certificate'])->name('documents.certificate');

    // Jualan & Kewangan
    Route::middleware('can:crm.view')->group(function () {
        Route::livewire('/crm', CrmPipeline::class)->name('crm.index');
        Route::livewire('/crm/{lead}', CrmShow::class)->name('crm.show');
    });
    Route::livewire('/kod-promosi', PromoIndex::class)->middleware('can:promo.view')->name('promo.index');
    Route::middleware('can:agents.view')->group(function () {
        Route::livewire('/pengurusan-ejen', AgentsIndex::class)->name('agents.index');
        Route::get('/pengurusan-ejen/invois-komisen.pdf', [AgentDocumentController::class, 'commissionInvoice'])->name('agents.invoice');
        Route::get('/pengurusan-ejen/{agent}/gambar', AgentPhotoController::class)->name('agents.photo');
    });
    Route::middleware('can:finance.view')->group(function () {
        Route::livewire('/kewangan', FinanceIndex::class)->name('finance.index');
        Route::livewire('/kewangan/invois/{invoice}', FinanceInvoiceShow::class)->name('finance.invoice');
        Route::get('/kewangan/invois/{invoice}/pdf', [FinanceDocumentController::class, 'invoice'])->name('finance.invoice.pdf');
        Route::get('/kewangan/quotation/{quotation}/pdf', [FinanceDocumentController::class, 'quotation'])->name('finance.quotation');
    });

    // Laporan
    Route::middleware('can:reports.view')->group(function () {
        Route::livewire('/laporan', ReportsIndex::class)->name('reports.index');
        Route::get('/laporan/{report}/muat-turun', ReportDownloadController::class)->name('reports.download');
    });
    // Audit trail is read-only: no update/delete routes exist.
    Route::middleware('can:audit.view')->group(function () {
        Route::livewire('/audit-log', AuditIndex::class)->name('audit.index');
        Route::get('/audit-log/eksport.csv', AuditExportController::class)->name('audit.export');
    });

    // Sistem
    Route::livewire('/notifikasi', NotificationsIndex::class)->middleware('can:notifications.view')->name('notifications.index');
    Route::livewire('/pengguna', UsersIndex::class)->middleware('can:users.view')->name('users.index');
    Route::livewire('/pengguna/peranan/{role}', RoleShow::class)->middleware('can:users.view')->name('users.role');
    Route::livewire('/sijil', CertificateEditor::class)->middleware('can:certificates.view')->name('certificates.editor');

    // Tetapan — profile & security are personal (every user); company needs settings.view.
    Route::livewire('/tetapan/profil', Profile::class)->name('settings.profile');
    Route::post('/tetapan/tema', ThemeController::class)->name('settings.theme');
    Route::livewire('/tetapan/keselamatan', Security::class)->name('settings.security');
    Route::livewire('/tetapan/syarikat', Company::class)->middleware('can:settings.view')->name('settings.company');
    Route::redirect('/tetapan/notifikasi', '/notifikasi?tetapan=1')->name('settings.notifications');
    Route::livewire('/tetapan/integrasi', Integrations::class)->middleware('can:api.view')->name('settings.integrations');
    Route::livewire('/tetapan/webhooks', Webhooks::class)->middleware('can:webhooks.view')->name('settings.webhooks');
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
