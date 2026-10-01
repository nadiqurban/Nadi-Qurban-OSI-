<?php

use App\Enums\RoleName;
use App\Livewire\Dashboard;
use App\Livewire\Settings\Company;
use App\Models\Order;
use App\Support\DashboardStats;
use App\Support\Settings;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class]);
    $this->admin = superAdmin();
    $this->admin->forceFill(['name' => 'Aisyah Rahman'])->save();
});

it('renders every dashboard widget with real figures', function () {
    $orders = Order::query()->where('year', 2027)->where('status', '!=', 'dibatalkan')->count();

    Livewire::actingAs($this->admin)->test(Dashboard::class)
        ->assertSee('Assalamualaikum Aisyah')
        ->assertSee('Jumlah Tempahan')
        ->assertSee(number_format($orders))
        ->assertSee('Pelaksanaan Akan Datang')
        ->assertSee('Jualan Bulanan')
        ->assertSee('Pencapaian Jualan')
        ->assertSee('Sasaran musim RM 5.00 juta')
        ->assertSee('Ranking Vendor')
        ->assertSee('Prestasi Negara')
        ->assertSee('Aktiviti Terkini')
        ->assertSee('Jumlah Mengikut Pakej')
        ->assertSee('Tempahan Terkini')
        ->assertSee(Order::query()->latest()->latest('id')->value('order_no'));
});

it('computes the sales gauge from verified orders and the season target', function () {
    $stats = app(DashboardStats::class)->all(today());
    $verified = (int) Order::query()->where('year', 2027)->where('status', '!=', 'dibatalkan')
        ->whereNotIn('stage', ['received'])->sum('total_sen');

    expect($stats['gauge']['achieved'])->toBe($verified)
        ->and($stats['gauge']['target'])->toBe(500_000_000)
        ->and($stats['kpis'])->toHaveCount(9)
        ->and($stats['monthly']['rows'])->toHaveCount(12)
        ->and($stats['animals'])->toHaveCount(3);
});

it('caches figures and invalidates them when an order changes', function () {
    $before = app(DashboardStats::class)->all(today())['kpis'][0]['value'];

    Order::query()->where('status', '!=', 'dibatalkan')->where('year', 2027)->firstOrFail()->update(['status' => 'dibatalkan']);

    $after = app(DashboardStats::class)->all(today())['kpis'][0]['value'];
    expect((int) str_replace(',', '', $after))->toBe((int) str_replace(',', '', $before) - 1);
});

it('remembers collapsed cards per user and exports to Excel', function () {
    Livewire::actingAs($this->admin)->test(Dashboard::class)
        ->call('toggleCard', 'pakej')
        ->assertDontSee('Bilangan tempahan setiap jenis pakej')
        ->call('export')
        ->assertFileDownloaded();

    expect($this->admin->fresh()->dashboard_collapsed)->toBe(['pakej']);

    Livewire::actingAs($this->admin->fresh())->test(Dashboard::class)
        ->assertSet('collapsed', ['pakej'])
        ->call('toggleCard', 'pakej')
        ->assertSee('Bilangan tempahan setiap jenis pakej');
});

it('stores the theme per user and renders it server-side', function () {
    $this->actingAs($this->admin)->postJson(route('settings.theme'), ['theme' => 'dark'])->assertOk();
    expect($this->admin->fresh()->theme)->toBe('dark');

    $this->actingAs($this->admin->fresh())->get('/dashboard')->assertOk()->assertSee('data-theme="dark"', false);
    $this->actingAs($this->admin)->postJson(route('settings.theme'), ['theme' => 'neon'])->assertUnprocessable();
});

it('lets settings change the season target', function () {
    Livewire::actingAs($this->admin)->test(Company::class)
        ->set('seasonTarget', '6000000')
        ->call('save')
        ->assertHasNoErrors();

    expect(app(Settings::class)->get('season.target_rm'))->toBe('6000000');
    Livewire::actingAs($this->admin)->test(Dashboard::class)->assertSee('Sasaran musim RM 6.00 juta');
});

it('hides the dashboard from roles without access', function () {
    $this->actingAs(userWithRoles(RoleName::VendorPic))->get('/dashboard')->assertForbidden();
});
