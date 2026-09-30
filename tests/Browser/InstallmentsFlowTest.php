<?php

use App\Models\InstallmentPlan;
use App\Services\Chip\ChipGateway;
use App\Services\Chip\FakeChipGateway;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoInstallmentSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    app()->instance(ChipGateway::class, new FakeChipGateway);
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoCatalogSeeder::class, DemoInstallmentSeeder::class]);
});

it('shows the instalment list and confirms a month from the progress bar', function () {
    $this->actingAs(superAdmin());
    $plan = InstallmentPlan::whereHas('customer', fn ($q) => $q->where('name', 'Ahmad Zaki bin Hassan'))->firstOrFail();

    visit('/ansuran')
        ->resize(...DESKTOP)
        ->assertSee('Urus pelan ansuran pelanggan')
        ->assertSee($plan->order_no)
        ->screenshot(filename: 'ansuran-index')
        ->click('[aria-label="Bulan 5 '.$plan->order_no.'"]')
        ->wait(1)
        ->assertSee('Sahkan Bayaran Diterima')
        ->screenshot(filename: 'ansuran-pay-confirm')
        ->press('Sahkan & Tanda Dibayar')
        ->wait(1)
        ->assertSee('5/6 bulan')
        ->assertNoJavaScriptErrors();
});

it('opens the new plan modal with a working tenure', function () {
    $this->actingAs(superAdmin());

    visit('/ansuran')
        ->resize(...DESKTOP)
        ->press('Pelan Baharu')
        ->wait(1)
        ->assertSee('Tempahan Baharu (Ansuran)')
        ->assertSee('Baki Ansuran')
        ->screenshot(filename: 'ansuran-new')
        ->assertNoJavaScriptErrors();
});

it('renders the public portal on a phone without overflow', function () {
    $plan = InstallmentPlan::whereHas('customer', fn ($q) => $q->where('name', 'Ahmad Zaki bin Hassan'))->firstOrFail();

    visit('/bayar/'.$plan->pay_token)
        ->resize(...PHONE)
        ->assertSee('PORTAL BAYARAN ANSURAN')
        ->assertSee('Jadual Ansuran')
        ->assertSee('Bayar RM 583 Sekarang')
        ->screenshot(filename: 'portal-phone', fullPage: true)
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
});

it('fits the instalment list on a phone', function () {
    $this->actingAs(superAdmin());

    visit('/ansuran')
        ->resize(...PHONE)
        ->assertSee('Bayaran Ansuran')
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
});
