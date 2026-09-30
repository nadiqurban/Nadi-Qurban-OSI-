<?php

use App\Actions\Vendors\PurchaseOrders;
use App\Actions\Vendors\VendorReports;
use App\Enums\PoStatus;
use App\Enums\RoleName;
use App\Enums\VendorLevel;
use App\Enums\VendorPaymentStatus;
use App\Enums\VendorReportStatus;
use App\Enums\VendorStatus;
use App\Livewire\Vendors\Index;
use App\Livewire\Vendors\Show;
use App\Livewire\Vendors\Tabs\AuditLog;
use App\Livewire\Vendors\Tabs\Payments;
use App\Livewire\Vendors\Tabs\Performance;
use App\Livewire\Vendors\Tabs\Profile;
use App\Livewire\Vendors\Tabs\PurchaseOrders as PoTab;
use App\Livewire\Vendors\Tabs\Reports;
use App\Models\Country;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoVendorSeeder::class]);
    $this->vendor = Vendor::where('code', 'SP 001')->firstOrFail();
});

function vendorPic(Vendor $vendor): User
{
    $pic = userWithRoles(RoleName::VendorPic);
    $pic->forceFill(['vendor_id' => $vendor->id])->save();

    return $pic;
}

function draftPo(Vendor $vendor, array $data = []): PurchaseOrder
{
    return app(PurchaseOrders::class)->create($vendor, $data + [
        'service' => 'qurban', 'animal_label' => 'Lembu (1 bhg)', 'quantity' => 25, 'unit_price' => 1800, 'currency' => 'RM',
        'implementation_date' => '2027-06-18', 'notes' => 'Ikut syariat.',
    ], superAdmin());
}

it('lists vendor cards with filters and registers a vendor', function () {
    Livewire::actingAs(superAdmin())->test(Index::class)
        ->assertSee('Pengurusan Vendor')
        ->assertSee('Uganda Charity')
        ->assertSee('SP 001')
        ->set('status', 'digantung')
        ->assertSee('Riyadh Camel Trading')
        ->assertDontSee('Uganda Charity')
        ->call('clearFilters')
        ->call('openRegister')
        ->assertSet('vendorForm.code', 'SP 007')
        ->set('vendorForm.name', 'Somali Livestock Co')
        ->set('vendorForm.countryId', Country::where('name', 'Somalia')->value('id'))
        ->call('toggleVendorAnimal', 'Unta')
        ->call('saveVendor')
        ->assertHasNoErrors()
        ->assertSet('showVendorForm', false);

    $new = Vendor::where('code', 'SP 007')->firstOrFail();
    expect($new->vendor_no)->toBe('VND-2016')
        ->and($new->animals)->toBe(['Unta'])
        ->and($new->status)->toBe(VendorStatus::Active);
});

it('suspends a vendor and hides it from Agihan Negara', function () {
    Livewire::actingAs(superAdmin())->test(Index::class)->call('suspend', $this->vendor->id);

    expect($this->vendor->refresh()->status)->toBe(VendorStatus::Suspended)
        ->and(Vendor::active()->pluck('id'))->not->toContain($this->vendor->id);
});

it('creates a PO from the profile and moves it through the flow', function () {
    Livewire::actingAs(superAdmin())->test(PoTab::class, ['vendorId' => $this->vendor->id])
        ->call('startCreate')
        ->set('create.animal', 'Kambing')
        ->set('create.quantity', '40')
        ->set('create.unit_price', '800')
        ->call('saveCreate')
        ->assertHasNoErrors();

    $po = PurchaseOrder::sole();
    expect($po->po_no)->toBe('NQ-PO-2027-0001')
        ->and($po->status)->toBe(PoStatus::Draft)
        ->and($po->total_rm_sen)->toBe(3200000)
        ->and($po->payment->status)->toBe(VendorPaymentStatus::Pending)
        ->and($po->billing_address)->toContain('Nadi Qurban');

    Livewire::actingAs(superAdmin())->test(PoTab::class, ['vendorId' => $this->vendor->id])
        ->call('open', $po->id)
        ->set('edit.unit_price', '850')
        ->call('saveDetail')
        ->call('send');

    expect($po->refresh()->status)->toBe(PoStatus::Sent)
        ->and($po->total_rm_sen)->toBe(3400000)
        ->and($po->payment->amount_sen)->toBe(3400000);

    Livewire::actingAs(vendorPic($this->vendor))->test(PoTab::class, ['vendorId' => $this->vendor->id])
        ->call('open', $po->id)
        ->assertSee('Accept PO')
        ->call('accept');

    expect($po->refresh()->status)->toBe(PoStatus::Accepted)->and($po->accepted_at)->not->toBeNull();
});

it('stores USD POs with the exchange-rate snapshot', function () {
    $po = draftPo($this->vendor, ['currency' => 'USD', 'unit_price' => 400, 'quantity' => 10]);

    expect($po->total_minor)->toBe(400000)
        ->and((float) $po->exchange_rate)->toBe(4.7)
        ->and($po->total_rm_sen)->toBe(1880000)
        ->and($po->money($po->total_minor, 'RM', 4.7))->toBe('RM 18,800')
        ->and($po->money($po->total_minor, 'USD', 4.7))->toBe('USD 4,000');
});

it('runs report submission and HQ verification to Completed', function () {
    $po = draftPo($this->vendor);
    app(PurchaseOrders::class)->send($po, superAdmin());

    expect(fn () => app(VendorReports::class)->submit($po, [UploadedFile::fake()->image('a.png')], 'Lembu', null, superAdmin()))
        ->toThrow(ValidationException::class);

    $pic = vendorPic($this->vendor);
    app(PurchaseOrders::class)->accept($po->refresh(), $pic);

    Livewire::actingAs($pic)->test(Reports::class, ['vendorId' => $this->vendor->id])
        ->call('setAnimal', 'Lembu')
        ->set('files', [UploadedFile::fake()->image('sembelihan.jpg'), UploadedFile::fake()->create('laporan.pdf', 50, 'application/pdf')])
        ->set('notes', '25 ekor lembu disembelih.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSee('Menunggu Semakan HQ')
        ->assertDontSee('Sahkan &amp; Tandakan Completed', false);

    expect($po->refresh()->status)->toBe(PoStatus::InProgress)
        ->and($po->report->getMedia('files'))->toHaveCount(2)
        ->and($po->report->getFirstMedia('files')->getCustomProperty('animal'))->toBe('Lembu');

    Livewire::actingAs(superAdmin())->test(Reports::class, ['vendorId' => $this->vendor->id])
        ->assertSee('Sahkan')
        ->call('verify');

    expect($po->refresh()->status)->toBe(PoStatus::Completed)
        ->and($po->report->status)->toBe(VendorReportStatus::Verified);
});

it('records vendor payments and lets only Super Admin / Admin HQ confirm', function () {
    $po = draftPo($this->vendor);
    $lw = Livewire::actingAs(superAdmin())->test(Payments::class, ['vendorId' => $this->vendor->id]);

    $lw->call('confirm');   // incomplete info
    expect($po->payment->refresh()->status)->toBe(VendorPaymentStatus::Pending);

    $lw->set('paymentDate', '2027-06-11')->call('setBank', 'Maybank')->set('reference', 'MBB27061100482')
        ->set('receipt', UploadedFile::fake()->image('resit.jpg'))
        ->call('save')->assertHasNoErrors();

    $payment = $po->payment->refresh();
    expect($payment->status)->toBe(VendorPaymentStatus::Processing)
        ->and($payment->getFirstMedia('receipt'))->not->toBeNull();

    $finance = userWithRoles(RoleName::Finance);
    if ($finance->can('vendors.manage')) {
        Livewire::actingAs($finance)->test(Payments::class, ['vendorId' => $this->vendor->id])->call('confirm')->assertForbidden();
    }

    $lw->call('confirm');
    expect($payment->refresh()->status)->toBe(VendorPaymentStatus::Completed)->and($payment->confirmed_by)->not->toBeNull();

    // Receipt is private: signed URL only.
    $media = $payment->getFirstMedia('receipt');
    $this->actingAs(superAdmin())->get(VendorPayment::mediaUrl($media))->assertOk();
    $this->get(route('vendors.media', $media))->assertForbidden();
});

it('lets only Super Admin change the rank and derives the tier', function () {
    $hq = userWithRoles(RoleName::AdminHq);
    Livewire::actingAs($hq)->test(Performance::class, ['vendorId' => $this->vendor->id])->call('setRank', 3)->assertForbidden();

    Livewire::actingAs(superAdmin())->test(Performance::class, ['vendorId' => $this->vendor->id])
        ->assertSee('Vendor Ranking')
        ->call('setRank', 6)
        ->assertSee('Ranking diturunkan 9 → 6');

    expect($this->vendor->refresh()->rank)->toBe(6)->and($this->vendor->level)->toBe(VendorLevel::Silver);

    Livewire::actingAs(superAdmin())->test(AuditLog::class, ['vendorId' => $this->vendor->id])->assertSee('Ranking Uganda Charity 9 → 6');
});

it('scopes the vendor PIC to their own vendor and tabs', function () {
    $pic = vendorPic($this->vendor);
    $other = Vendor::where('code', 'SP 002')->firstOrFail();

    $this->actingAs($pic)->get(route('vendors.index'))->assertRedirect(route('vendors.show', ['vendor' => $this->vendor->id, 'tab' => 'po']));
    $this->actingAs($pic)->get(route('vendors.show', $other))->assertForbidden();

    Livewire::actingAs($pic)->test(Show::class, ['vendor' => $this->vendor])
        ->assertSet('tab', 'po')
        ->assertSee('Purchase Order')
        ->assertDontSee('Prestasi');

    $draft = draftPo($this->vendor);
    Livewire::actingAs($pic)->test(PoTab::class, ['vendorId' => $this->vendor->id])->assertDontSee($draft->po_no);
    Livewire::actingAs($pic)->test(Profile::class, ['vendorId' => $other->id])->assertForbidden();
    Livewire::actingAs($pic)->test(Payments::class, ['vendorId' => $this->vendor->id])->call('save')->assertForbidden();
});

it('renders the PO receipt PDF and blocks drafts for vendors', function () {
    $po = draftPo($this->vendor);

    $this->actingAs(superAdmin())->get(route('vendors.po.pdf', $po))->assertOk();
    $this->actingAs(vendorPic($this->vendor))->get(route('vendors.po.pdf', $po))->assertForbidden();
})->skip(fn () => ! env('LARAVEL_PDF_CHROME_PATH'), 'Chrome not configured');
