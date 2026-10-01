<?php

use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DemoFinanceSeeder;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoPurchaseOrderSeeder::class, DemoFinanceSeeder::class]);
    $this->admin = User::where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
});

it('shows Kewangan like the design and opens the invoice modal', function () {
    $this->actingAs($this->admin);

    visit('/kewangan')
        ->resize(...DESKTOP)
        ->assertSee('Pengurusan Kewangan')
        ->assertSee('INV-2027-0891')
        ->screenshot(filename: 'finance-index')
        ->click('#btn-invoice')
        ->wait(0.6)
        ->assertSee('MAKLUMAT SYARIKAT (boleh edit)')
        ->screenshot(filename: 'finance-invoice-modal')
        ->assertNoJavaScriptErrors();
});

it('shows the invoice detail', function () {
    $this->actingAs($this->admin);
    $invoice = Invoice::where('invoice_no', 'INV-2027-0891')->firstOrFail();

    visit('/kewangan/invois/'.$invoice->id)
        ->resize(...DESKTOP)
        ->assertSee('Bil Kepada')
        ->assertSee('Rekod Transaksi')
        ->screenshot(filename: 'finance-invoice-detail')
        ->assertNoJavaScriptErrors();
});

it('fits a phone without horizontal overflow', function () {
    $this->actingAs($this->admin);

    visit('/kewangan')
        ->resize(...PHONE)
        ->assertSee('Pengurusan Kewangan')
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->screenshot(filename: 'finance-index-phone')
        ->assertNoJavaScriptErrors();
});
