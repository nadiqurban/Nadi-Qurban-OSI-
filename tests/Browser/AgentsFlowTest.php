<?php

use App\Actions\Agents\SaveAgent;
use App\Actions\Booking\CreatePublicBooking;
use App\Enums\PaymentMethod;
use App\Models\Agent;
use App\Models\Product;
use App\Services\Chip\ChipGateway;
use App\Services\Chip\FakeChipGateway;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
| Phase 12 screens at 1440×900 and 390×844: no JS errors, no horizontal page
| overflow, screenshots for the side-by-side check with the design files.
*/

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoCatalogSeeder::class]);
    app()->instance(ChipGateway::class, new FakeChipGateway);

    $this->agent = app(SaveAgent::class)->handle(null, [
        'code' => 'AZ01', 'name' => 'Aiman Zulkifli', 'email' => 'aiman@nadiqurban.com', 'phone' => '012-334 5671',
        'password' => 'EjenNq2027x', 'gender' => 'Lelaki', 'birth_date' => '1992-04-12', 'district' => 'Petaling', 'state' => 'Selangor',
        'bank_name' => 'Maybank', 'bank_account_name' => 'Aiman Zulkifli', 'bank_account_no' => '5623 5782 2681',
    ], superAdmin());

    $this->order = app(CreatePublicBooking::class)->handle(
        ['name' => 'Rosmawati binti Idris', 'phone' => '012-3456789', 'email' => 'ros@example.com', 'address' => 'No. 12, Jalan Melati 3', 'postcode' => '40150', 'city' => 'Shah Alam', 'state' => 'Selangor'],
        Product::where('name', 'Qurban Lembu Uganda')->firstOrFail(), 2, ['Rosmawati binti Idris', 'Faizal bin Kassim'],
        null, PaymentMethod::BankTransfer, UploadedFile::fake()->image('resit.jpg'), $this->agent,
    );
});

it('walks through Tempahan Awam on desktop', function () {
    $page = visit('/e/aiman-zulkifli')
        ->resize(...DESKTOP)
        ->assertSee('Mulakan Tempahan')
        ->screenshot(filename: 'booking-welcome')
        ->press('Mulakan Tempahan')
        ->wait(0.5)
        ->assertSee('Pilih Servis')
        ->click('#bk-product-'.Product::where('name', 'Qurban Lembu Uganda')->value('id'))
        ->wait(0.5)
        ->screenshot(filename: 'booking-step1')
        ->press('Teruskan')
        ->wait(0.5)
        ->type('#bk-name', 'Siti Hajar binti Ali')
        ->type('#bk-phone', '013-9988776')
        ->type('#bk-email', 'hajar@example.com')
        ->type('#bk-address', 'No. 5, Jalan Bunga Raya')
        ->wait(0.6)
        ->screenshot(filename: 'booking-step2')
        ->press('Teruskan')
        ->wait(0.6)
        ->assertSee('Semak & Bayar')
        ->screenshot(filename: 'booking-step3')
        ->assertNoJavaScriptErrors();

    expect($page)->not->toBeNull();
});

it('fits Tempahan Awam on a phone', function () {
    visit('/tempah')
        ->resize(...PHONE)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->press('Mulakan Tempahan')
        ->wait(0.5)
        ->screenshot(filename: 'booking-step1-phone')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors();
});

it('renders the receipt on desktop and phone', function () {
    visit(route('booking.receipt', $this->order->tracking_token))
        ->resize(...DESKTOP)
        ->assertSee('Bukti Bayaran Diterima')
        ->assertSee('RESIT BAYARAN')
        ->screenshot(filename: 'booking-receipt')
        ->resize(...PHONE)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: 'booking-receipt-phone')
        ->assertNoJavaScriptErrors();
});

it('renders Log Masuk Ejen and the Portal Ejen', function () {
    visit('/ejen')
        ->resize(...DESKTOP)
        ->assertSee('Log Masuk Ejen')
        ->screenshot(filename: 'agent-login')
        ->type('#ag-email', 'aiman@nadiqurban.com')
        ->type('#ag-password', 'EjenNq2027x')
        ->press('Log Masuk')
        ->wait(1.2)
        ->assertPathIs('/ejen/portal')
        ->assertSee('Assalamualaikum, Aiman')
        ->assertSee($this->order->order_no)
        ->screenshot(filename: 'agent-portal')
        ->resize(...PHONE)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: 'agent-portal-phone')
        ->assertNoJavaScriptErrors();
});

it('renders Pengurusan Ejen on desktop and phone', function () {
    $this->actingAs(superAdmin());

    visit('/pengurusan-ejen')
        ->resize(...DESKTOP)
        ->assertSee('Pengurusan Ejen')
        ->assertSee('AZ01')
        ->screenshot(filename: 'agents-index')
        ->press('Tambah Ejen')
        ->wait(0.6)
        ->screenshot(filename: 'agents-form')
        ->resize(...PHONE)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors();

    visit('/pengurusan-ejen')->resize(...PHONE)->wait(0.5)->screenshot(filename: 'agents-index-phone')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);

    expect(Agent::count())->toBe(1);
});
