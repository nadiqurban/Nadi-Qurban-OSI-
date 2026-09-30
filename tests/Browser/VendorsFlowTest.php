<?php

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DemoPurchaseOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoPurchaseOrderSeeder::class]);
    $this->admin = User::where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
    $this->vendor = Vendor::where('code', 'SP 001')->firstOrFail();
});

it('shows the vendor list like the design', function () {
    $this->actingAs($this->admin);

    visit('/vendor')
        ->resize(...DESKTOP)
        ->assertSee('Pengurusan Vendor')
        ->assertSee('Uganda Charity')
        ->screenshot(filename: 'vendor-index')
        ->click('[aria-label="Menu Uganda Charity"]')
        ->wait(0.4)
        ->assertSee('Nyahaktif')
        ->assertNoJavaScriptErrors();
});

it('walks through the profile tabs', function () {
    $this->actingAs($this->admin);
    $po = PurchaseOrder::where('po_no', 'NQ-PO-2027-0001')->firstOrFail();

    visit('/vendor/'.$this->vendor->id)
        ->resize(...DESKTOP)
        ->assertSee('Maklumat Vendor')
        ->assertSee('VND-2010')
        ->screenshot(filename: 'vendor-profil')
        ->navigate('/vendor/'.$this->vendor->id.'?tab=po&po='.$po->id)
        ->assertSee('Kadar Harga')
        ->assertSee('Vendor Acceptance')
        ->screenshot(filename: 'vendor-po-detail')
        ->navigate('/vendor/'.$this->vendor->id.'?tab=bayaran')
        ->assertSee('Payment Information')
        ->screenshot(filename: 'vendor-bayaran')
        ->navigate('/vendor/'.$this->vendor->id.'?tab=laporan')
        ->assertSee('HQ Verify')
        ->screenshot(filename: 'vendor-laporan')
        ->navigate('/vendor/'.$this->vendor->id.'?tab=prestasi')
        ->assertSee('Vendor Ranking')
        ->screenshot(filename: 'vendor-prestasi')
        ->navigate('/vendor/'.$this->vendor->id.'?tab=audit')
        ->assertSee('Audit Log Vendor')
        ->assertNoJavaScriptErrors();
});

it('fits vendor screens on a phone', function (string $path) {
    $this->actingAs($this->admin);

    visit(str_replace('{id}', (string) $this->vendor->id, $path))
        ->resize(...PHONE)
        ->assertNoJavaScriptErrors()
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true);
})->with(['/vendor', '/vendor/{id}', '/vendor/{id}?tab=po', '/vendor/{id}?tab=bayaran', '/vendor/{id}?tab=laporan', '/vendor/{id}?tab=prestasi']);
