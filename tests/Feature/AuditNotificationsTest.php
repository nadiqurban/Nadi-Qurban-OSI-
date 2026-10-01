<?php

use App\Enums\NotificationType;
use App\Enums\RoleName;
use App\Enums\Severity;
use App\Livewire\Audit\Index as AuditIndex;
use App\Livewire\NotificationBell;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Models\Invoice;
use App\Models\NotificationPreference;
use App\Notifications\AppNotification;
use App\Notifications\Channels\WhatsAppChannel;
use App\Support\Audit;
use App\Support\Notifier;
use Database\Seeders\DemoFinanceSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class]);
    $this->admin = superAdmin();
});

it('shows the audit log with filters, detail panel and type summary', function () {
    Audit::log('login', 'Log masuk berjaya', causer: $this->admin);
    Audit::log('role.permissions', 'Kebenaran peranan Kewangan dikemas kini', severity: Severity::Critical, causer: $this->admin);
    Audit::log('login.failed', 'Log masuk gagal untuk aisyah@', severity: Severity::Warning);
    $critical = Activity::query()->where('event', 'role.permissions')->firstOrFail();

    Livewire::actingAs($this->admin)->test(AuditIndex::class)
        ->assertSee('Log Audit Sistem')
        ->assertSee('Tukar kebenaran')
        ->assertSee('Aktiviti Mengikut Jenis')
        ->set('severity', 'kritikal')
        ->assertSee('Kebenaran peranan Kewangan')
        ->assertDontSee('Log masuk gagal untuk')
        ->call('select', $critical->id)
        ->assertSee('Butiran Log')
        ->assertSee('LOG-'.$critical->id);
});

it('exports the audit log as CSV and keeps it immutable', function () {
    Audit::log('invoice.created', 'Invois INV-2027-0900 dicipta', causer: $this->admin);

    $response = $this->actingAs($this->admin)->get(route('audit.export'));
    $response->assertOk();
    expect($response->streamedContent())->toContain('Invois INV-2027-0900 dicipta')->toContain('Cipta invois');

    $log = Activity::query()->firstOrFail();
    expect(fn () => $log->update(['description' => 'diubah']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);

    $this->actingAs(userWithRoles(RoleName::Sales))->get('/audit-log')->assertForbidden();
});

it('sends notifications only through the channels a user enabled', function () {
    Notification::fake();
    $finance = userWithRoles(RoleName::Finance);
    $finance->forceFill(['phone' => '012-3456789'])->save();
    NotificationPreference::query()->create(['user_id' => $finance->id, 'type' => NotificationType::PaymentOverdue, 'app' => true, 'mail' => false, 'wa' => true]);

    Notifier::send(NotificationType::PaymentOverdue, 'finance.view', 'Bayaran tertunggak', '2 invois lewat.');

    Notification::assertSentTo($finance, AppNotification::class, fn (AppNotification $n, array $channels) => $channels === ['database', WhatsAppChannel::class]);
    Notification::assertSentTo($this->admin, AppNotification::class);
    Notification::assertNotSentTo(userWithRoles(RoleName::VendorPic), AppNotification::class);
});

it('lists notifications, marks them read and saves preferences', function () {
    $this->admin->notify(new AppNotification(NotificationType::NewOrder, 'Tempahan baharu diterima', 'NQ-QB-LE-001248 daripada Ahmad Zaki.', '/tempahan'));
    $this->admin->notify(new AppNotification(NotificationType::PaymentOverdue, 'Bayaran tertunggak', '8 invois lewat.'));

    Livewire::actingAs($this->admin)->test(NotificationBell::class)
        ->assertSee('Tempahan baharu diterima')
        ->assertSee('2');

    Livewire::actingAs($this->admin)->test(NotificationsIndex::class)
        ->assertSee('Tempahan baharu diterima')
        ->set('tab', 'sistem')
        ->assertSee('Bayaran tertunggak')
        ->assertDontSee('Tempahan baharu diterima')
        ->call('markAllRead')
        ->set('settings', true)
        ->assertSee('Keutamaan Notifikasi')
        ->call('togglePref', 'tempahan_baharu', 'wa');

    expect($this->admin->unreadNotifications()->count())->toBe(0)
        ->and(NotificationPreference::for($this->admin)['tempahan_baharu']['wa'])->toBeTrue();
});

it('notifies finance when an invoice becomes overdue', function () {
    Notification::fake();
    $finance = userWithRoles(RoleName::Finance);
    $this->seed(DemoFinanceSeeder::class);
    Invoice::query()->where('invoice_no', 'INV-2027-0887')->update(['due_date' => today()->subDay()]);

    $this->artisan('finance:daily')->assertSuccessful();

    Notification::assertSentTo($finance, AppNotification::class, fn (AppNotification $n) => $n->type === NotificationType::PaymentOverdue);
});
