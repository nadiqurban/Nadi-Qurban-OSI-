<?php

use App\Models\User;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class, DemoPurchaseOrderSeeder::class]);
    $this->admin = User::where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
});

it('shows the dashboard like the design with the map', function () {
    $this->actingAs($this->admin);

    visit('/dashboard')
        ->resize(...DESKTOP)
        ->assertSee('Assalamualaikum Muhammad')
        ->assertSee('Jualan Bulanan')
        ->wait(1.5)
        ->assertPresent('#nq-country-map svg path')
        ->screenshot(fullPage: true, filename: 'dashboard')
        ->assertNoJavaScriptErrors();
});

it('collapses a card and switches to dark mode', function () {
    $this->actingAs($this->admin);

    visit('/dashboard')
        ->resize(...DESKTOP)
        ->click('#chk-jualan')
        ->wait(0.6)
        ->assertNotPresent('svg[aria-label="Carta jualan bulanan"]')
        ->click('[aria-label="Tukar tema"]')
        ->wait(1.5)
        ->assertScript("document.documentElement.dataset.theme === 'dark'", true)
        ->screenshot(fullPage: true, filename: 'dashboard-dark')
        ->assertNoJavaScriptErrors();

    expect($this->admin->fresh()->dashboard_collapsed)->toBe(['jualan'])
        ->and($this->admin->fresh()->theme)->toBe('dark');
});

it('fits a phone', function () {
    $this->actingAs($this->admin);

    visit('/dashboard')
        ->resize(...PHONE)
        ->wait(1)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(fullPage: true, filename: 'dashboard-phone')
        ->assertNoJavaScriptErrors();
});
