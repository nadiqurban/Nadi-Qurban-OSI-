<?php

use App\Models\Order;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class]);
});

it('tracks an order publicly with masked participant names', function () {
    visit('/jejak')
        ->resize(...DESKTOP)
        ->assertSee('Jejak Status Tempahan Anda')
        ->type('[aria-label="No. tracking atau no. tempahan"]', 'NQT-2027-001252')
        ->press('Semak')
        ->wait(1)
        ->assertSee('NQ-QB-LE-001252')
        ->assertSee('Perkembangan Ibadah')
        ->assertSee('Syed F*** Aljunied')
        ->assertDontSee('Syed Farid Aljunied')
        ->screenshot(filename: 'jejak-desktop')
        ->assertNoJavaScriptErrors();
});

it('shows the not-found state', function () {
    visit('/jejak?track=NQ-XX-XX-999999')
        ->resize(...DESKTOP)
        ->assertSee('Tiada rekod dijumpai');
});

it('fits a phone screen with the full names when the link carries the token', function () {
    $order = Order::where('order_no', 'NQ-QB-LE-001252')->firstOrFail();

    visit('/jejak?track='.$order->tracking_no.'&t='.$order->tracking_token)
        ->resize(...PHONE)
        ->assertSee('Syed Farid Aljunied')
        ->screenshot(filename: 'jejak-phone')
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
});
