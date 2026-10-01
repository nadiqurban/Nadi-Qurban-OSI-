<?php

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Support\Audit;
use Database\Seeders\DemoDocumentSeeder;
use Database\Seeders\DemoFinanceSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoFinanceSeeder::class, DemoDocumentSeeder::class]);
    $this->admin = User::where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
});

it('shows Dokumen in grid and list view', function () {
    $this->actingAs($this->admin);

    visit('/dokumen')
        ->resize(...DESKTOP)
        ->assertSee('Repositori Dokumen')
        ->assertSee('Invois INV-2027-0891.pdf')
        ->screenshot(filename: 'documents-grid')
        ->click('#view-list')
        ->wait(0.5)
        ->assertPresent('[data-table-mode]')
        ->assertSee('Invois INV-2027-0891.pdf')
        ->screenshot(filename: 'documents-list')
        ->assertNoJavaScriptErrors();
});

it('shows Pusat Laporan and generates a report', function () {
    $this->actingAs($this->admin);

    visit('/laporan')
        ->resize(...DESKTOP)
        ->assertSee('Jana Laporan Pantas')
        ->click('#quick-peserta')
        ->wait(1)
        ->assertSee('Peserta & Ibadah')
        ->screenshot(filename: 'reports-index')
        ->assertNoJavaScriptErrors();
});

it('shows the audit log and its detail panel', function () {
    Audit::log('role.permissions', 'Kebenaran peranan Kewangan dikemas kini', causer: $this->admin);
    Audit::log('login', 'Log masuk berjaya', causer: $this->admin);
    $log = Activity::query()->where('event', 'role.permissions')->firstOrFail();
    $this->actingAs($this->admin);

    visit('/audit-log')
        ->resize(...DESKTOP)
        ->assertSee('Log Audit Sistem')
        ->assertSee('Aktiviti Mengikut Jenis')
        ->screenshot(filename: 'audit-index')
        ->click('#log-'.$log->id)
        ->wait(0.5)
        ->assertSee('Butiran Log')
        ->screenshot(filename: 'audit-detail')
        ->assertNoJavaScriptErrors();
});

it('shows notifications, preferences and the header bell', function () {
    $this->admin->notify(new AppNotification(NotificationType::NewOrder, 'Tempahan baharu diterima', 'NQ-QB-LE-001248 daripada Ahmad Zaki bin Hassan — Qurban Lembu (Uganda).', '/tempahan'));
    $this->admin->notify(new AppNotification(NotificationType::PaymentOverdue, 'Bayaran tertunggak', '8 invois melebihi tempoh 30 hari — jumlah RM 42,000.'));
    $this->actingAs($this->admin);

    visit('/notifikasi')
        ->resize(...DESKTOP)
        ->assertSee('Tempahan baharu diterima')
        ->assertSee('Ringkasan')
        ->screenshot(filename: 'notifications-index')
        ->click('#btn-settings')
        ->wait(0.5)
        ->assertSee('Keutamaan Notifikasi')
        ->click('#bell')
        ->wait(0.4)
        ->assertSee('Lihat semua notifikasi')
        ->screenshot(filename: 'notifications-settings-bell')
        ->assertNoJavaScriptErrors();
});

it('fits phones without horizontal overflow', function (string $path) {
    $this->actingAs($this->admin);

    visit($path)
        ->resize(...PHONE)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors();
})->with(['/kewangan', '/dokumen', '/laporan', '/audit-log', '/notifikasi']);
