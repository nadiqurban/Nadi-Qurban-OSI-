<?php

use App\Enums\OrderStage;
use App\Models\Order;
use App\Models\Vendor;
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

it('records a lafaz akad from the Lafaz Akad screen', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'NQ-QB-LE-001252')->firstOrFail();

    visit('/lafaz-akad')
        ->resize(...DESKTOP)
        ->assertSee('Lafaz Akad Wakalah')
        ->screenshot(filename: 'akad-index')
        ->click('#akad-open-'.$order->id)
        ->wait(1)
        ->assertSee('Mohon Peserta Mengikuti Bacaan Lafaz Akad')
        ->click('#akad-consent')
        ->wait(0.6)
        ->press('Sahkan Akad')
        ->wait(1)
        ->assertSee('direkodkan')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->akad)->not->toBeNull();
});

it('assigns a country and vendor then sends from Agihan Negara', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'NQ-QB-LE-001256')->firstOrFail();
    $vendor = Vendor::where('code', 'SP 001')->firstOrFail();

    visit('/agihan-negara')
        ->resize(...DESKTOP)
        ->assertSee('Agihan Negara Pelaksanaan')
        ->assertSee('NQ-QB-LE-001256')
        ->screenshot(filename: 'agihan-index')
        ->select('[aria-label="Vendor NQ-QB-LE-001256"]', (string) $vendor->id)
        ->wait(0.8)
        ->click('#agih-send-'.$order->id)
        ->wait(1)
        ->assertSee('dihantar kepada vendor')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->allocation?->vendor_id)->toBe($vendor->id);
});

it('reviews and verifies an execution report', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'like', '%-001258')->firstOrFail();

    visit('/pelaksanaan?tab=semakan')
        ->resize(...DESKTOP)
        ->assertSee('Pantau pelaksanaan ibadah')
        ->screenshot(filename: 'pelaksanaan-index')
        ->click('#exec-open-'.$order->id)
        ->wait(1)
        ->assertSee('Semak Laporan Pelaksanaan')
        ->assertSee('Bukti Dimuat Naik')
        ->screenshot(filename: 'pelaksanaan-semak')
        ->press('Sahkan Selesai')
        ->wait(1)
        ->assertSee('sedia untuk AWB')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->stage)->toBe(OrderStage::FinalReport);
});

it('generates an airway bill and completes the order', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'like', '%-001259')->firstOrFail();

    visit('/awb')
        ->resize(...DESKTOP)
        ->assertSee('Jana Airway Bill & jejak penghantaran')
        ->screenshot(filename: 'awb-index')
        ->click('#awb-open-'.$order->id)
        ->wait(1)
        ->assertSee('No. Konsainan (auto-jana)')
        ->press('Jana & Simpan')
        ->wait(1.5)
        ->assertSee('selesai')
        ->click('#awb-view-'.$order->id)
        ->wait(1)
        ->assertSee('AIRWAY BILL')
        ->screenshot(filename: 'awb-preview')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->stage)->toBe(OrderStage::Completed)
        ->and($order->fresh()->certificates)->toHaveCount(1);
});

it('lays out every pipeline screen without horizontal overflow on phones', function (string $url) {
    $this->actingAs(superAdmin());

    visit($url)
        ->resize(...PHONE)
        ->assertSee('NQ-')
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with(['/lafaz-akad', '/agihan-negara', '/pelaksanaan', '/awb', '/tempahan-selesai', '/sijil']);

it('shows Tempahan Selesai and the certificate editor at desktop size', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'like', '%-001241')->firstOrFail();

    visit('/tempahan-selesai')
        ->resize(...DESKTOP)
        ->assertSee('Tempahan Selesai')
        ->screenshot(filename: 'selesai-index')
        ->click('#done-view-'.$order->id)
        ->wait(1)
        ->assertSee('Garis Masa Proses')
        ->assertNoJavaScriptErrors();

    visit('/sijil')
        ->resize(...DESKTOP)
        ->assertSee('Editor Sijil')
        ->assertSee('SIJIL PENYERTAAN')
        ->type('#f-form-name', 'AHMAD ZAKI BIN HASSAN')
        ->wait(1)
        ->assertSee('AHMAD ZAKI BIN HASSAN')
        ->screenshot(filename: 'sijil-editor')
        ->assertNoJavaScriptErrors();
});
