<?php

use App\Models\Order;
use App\Models\Product;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class]);
});

it('selects orders, shows the bulk bar and opens participant groups', function () {
    $this->actingAs(superAdmin());

    visit('/tempahan')
        ->resize(...DESKTOP)
        ->assertSee('Senarai Tempahan')
        ->assertSee('NQ-QB-LE-001249')
        ->click('[aria-label="Pilih NQ-QB-LE-001249"]')
        ->wait(0.6)
        ->assertSee('1 tempahan dipilih')
        ->press('Jana Senarai Peserta')
        ->wait(0.6)
        ->assertSee('Senarai Peserta Mengikut Kumpulan')
        ->assertSee('FARID BIN KASSIM')
        ->assertSee('Lengkap')
        ->assertNoJavaScriptErrors();
});

it('keeps the bulk bar at the bottom of the screen on phones', function () {
    $this->actingAs(superAdmin());

    visit('/tempahan')
        ->resize(...PHONE)
        ->click('[aria-label="Pilih NQ-QB-LE-001249"]')
        ->wait(0.6)
        ->assertSee('1 tempahan dipilih')
        ->assertScript('getComputedStyle([...document.querySelectorAll("div")].find(d => d.textContent.trim().startsWith("1 tempahan dipilih")).closest(".bg-primary")).position', 'fixed')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
});

it('opens the new order modal and prices the chosen product', function () {
    $this->actingAs(superAdmin());

    visit('/tempahan')
        ->resize(...DESKTOP)
        ->press('Tempahan Baharu')
        ->wait(0.6)
        ->assertSee('No. auto-generate mengikut servis & haiwan')
        ->select('#f-form-productId', (string) Product::where('name', 'Qurban Lembu Uganda')->value('id'))
        ->wait(1)
        ->assertSee('Jumlah Keseluruhan')
        ->assertSee('RM 3,500')
        ->assertNoJavaScriptErrors();
});

it('shows the payment proof with the pdf/image viewer', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'NQ-QB-LE-001249')->firstOrFail();

    visit('/pengesahan-bayaran')
        ->resize(...DESKTOP)
        ->assertSee('NQ-QB-LE-001249')
        ->click('[wire\:click="viewProof('.$order->id.')"]')
        ->wait(1)
        ->assertSee('Bukti Bayaran')
        ->assertSee('Buka dalam tab baharu')
        ->assertNoJavaScriptErrors();
});

it('verifies a payment from the Pengesahan Bayaran screen', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'NQ-DM-KA-001251')->firstOrFail();

    visit('/pengesahan-bayaran')
        ->resize(...DESKTOP)
        ->assertSee('3 menunggu')
        ->click('[wire\\:click="confirm('.$order->id.')"]')
        ->wait(1)
        ->assertSee('2 menunggu')
        ->assertNoJavaScriptErrors();

    expect($order->fresh()->stage->value)->toBe('payment_verified');
});
