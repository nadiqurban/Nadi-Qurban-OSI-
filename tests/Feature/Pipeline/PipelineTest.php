<?php

use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\VerifyPayment;
use App\Actions\Pipeline\AssignAllocation;
use App\Actions\Pipeline\CancelAllocation;
use App\Actions\Pipeline\GenerateAwb;
use App\Actions\Pipeline\RecordAkad;
use App\Actions\Pipeline\SubmitExecutionReport;
use App\Actions\Pipeline\VerifyExecutionReport;
use App\Enums\AkadMethod;
use App\Enums\Courier;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PostType;
use App\Enums\RoleName;
use App\Livewire\Allocation\Index as AllocationIndex;
use App\Livewire\Certificates\Editor;
use App\Livewire\Execution\Index as ExecutionIndex;
use App\Livewire\Orders\Completed;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Public\Tracking;
use App\Livewire\Shipping\Index as ShippingIndex;
use App\Models\ExecutionReport;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Support\CertificateTemplate;
use App\Support\Settings;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class]);
});

/** A paid & verified "Qurban Lembu Uganda" order (stage payment_verified). */
function verifiedOrder(int $quantity = 1): Order
{
    $admin = superAdmin();
    $order = app(CreateOrder::class)->handle(
        ['name' => 'Iskandar bin Yusof', 'phone' => '014-8890213', 'address' => 'No. 20, Persiaran Kayangan', 'postcode' => '40000', 'city' => 'Shah Alam', 'state' => 'Selangor'],
        ['product_id' => Product::where('name', 'Qurban Lembu Uganda')->value('id'), 'quantity' => $quantity, 'year' => 2027, 'payment_method' => 'fpx'],
        [],
        null,
        $admin,
    );
    $order->update(['status' => OrderStatus::Accepted]);
    app(VerifyPayment::class)->handle($order, $admin);

    return $order->fresh();
}

function ugandaVendor(): Vendor
{
    return Vendor::where('code', 'SP 001')->firstOrFail();
}

it('runs an order through the whole pipeline to Selesai', function () {
    $admin = superAdmin();
    $order = verifiedOrder(2);

    app(RecordAkad::class)->handle($order, AkadMethod::WhatsApp, true, $admin);
    app(AssignAllocation::class)->handle($order, $order->country_id, ugandaVendor()->id, $admin);
    expect($order->fresh()->stage)->toBe(OrderStage::Executing);

    app(SubmitExecutionReport::class)->handle($order, [UploadedFile::fake()->image('sembelihan.png')], [], 'Selesai', $admin);
    app(VerifyExecutionReport::class)->handle($order, $admin);
    expect($order->fresh()->stage)->toBe(OrderStage::FinalReport);

    $shipment = app(GenerateAwb::class)->handle($order, Courier::PosLaju, PostType::Registered, [], $admin);

    $order->refresh();
    expect($order->stage)->toBe(OrderStage::Completed)
        ->and($order->status)->toBe(OrderStatus::Completed)
        ->and($shipment->consignment_no)->toStartWith('EP')
        ->and($order->certificates)->toHaveCount(2)
        ->and($order->certificates->first()->certificate_no)->toBe('NQ-SIJIL-2027-0001')
        ->and($order->stageHistories()->pluck('stage')->map->value->all())
        ->toBe(array_map(fn (OrderStage $s) => $s->value, OrderStage::cases()));
});

it('requires consent for the akad and refuses to skip stages', function () {
    $order = verifiedOrder();

    expect(fn () => app(RecordAkad::class)->handle($order, AkadMethod::Phone, false, superAdmin()))->toThrow(ValidationException::class)
        ->and(fn () => app(AssignAllocation::class)->handle($order, $order->country_id, ugandaVendor()->id, superAdmin()))->toThrow(ValidationException::class)
        ->and(fn () => app(GenerateAwb::class)->handle($order, Courier::PosLaju, PostType::Regular, [], superAdmin()))->toThrow(ValidationException::class);
});

it('only allows active vendors of the chosen country', function () {
    $order = verifiedOrder();
    app(RecordAkad::class)->handle($order, AkadMethod::Phone, true, superAdmin());

    $kano = Vendor::where('name', 'Kano Farms')->firstOrFail();           // pending, Nigeria
    $sahara = Vendor::where('name', 'Sahara Cattle Co.')->firstOrFail();  // active, Chad

    expect(fn () => app(AssignAllocation::class)->handle($order, $kano->country_id, $kano->id, superAdmin()))->toThrow(ValidationException::class)
        ->and(fn () => app(AssignAllocation::class)->handle($order, $order->country_id, $sahara->id, superAdmin()))->toThrow(ValidationException::class);
});

it('sends from Agihan Negara and cancels back to Belum Diagih', function () {
    $order = verifiedOrder();
    app(RecordAkad::class)->handle($order, AkadMethod::Phone, true, superAdmin());

    Livewire::actingAs(superAdmin())->test(AllocationIndex::class)
        ->assertSee($order->order_no)
        ->set("draft.{$order->id}.vendor", (string) ugandaVendor()->id)
        ->call('send', $order->id)
        ->assertSet('tab', 'telah')
        ->call('cancel', $order->id)
        ->assertSet('tab', 'belum');

    expect($order->fresh()->stage)->toBe(OrderStage::AkadDone)
        ->and($order->fresh()->allocation)->toBeNull();
});

it('cannot cancel an allocation once the report is uploaded', function () {
    $order = verifiedOrder();
    app(RecordAkad::class)->handle($order, AkadMethod::Phone, true, superAdmin());
    app(AssignAllocation::class)->handle($order, $order->country_id, ugandaVendor()->id, superAdmin());
    app(SubmitExecutionReport::class)->handle($order, [UploadedFile::fake()->image('a.png')], [], null, superAdmin());

    expect(fn () => app(CancelAllocation::class)->handle($order, superAdmin()))->toThrow(ValidationException::class);
});

it('uploads report evidence from the Pelaksanaan screen', function () {
    $order = verifiedOrder();
    app(RecordAkad::class)->handle($order, AkadMethod::Phone, true, superAdmin());
    app(AssignAllocation::class)->handle($order, $order->country_id, ugandaVendor()->id, superAdmin());

    Livewire::actingAs(superAdmin())->test(ExecutionIndex::class)
        ->call('openReport', $order->id)
        ->assertSee('Upload Laporan Pelaksanaan')
        ->call('submitReport')
        ->assertHasErrors('images')
        ->set('images', [UploadedFile::fake()->image('sembelihan.png'), UploadedFile::fake()->image('agihan.jpg')])
        ->set('notes', 'Daging diagihkan kepada 40 keluarga.')
        ->call('submitReport')
        ->assertHasNoErrors()
        ->assertSet('tab', 'semakan');

    $report = $order->fresh()->executionReport;
    expect($report->getMedia('images'))->toHaveCount(2)
        ->and($order->fresh()->stage)->toBe(OrderStage::ReportUploaded);

    // Evidence is served only through a signed, authenticated route.
    $media = $report->getFirstMedia('images');
    $this->actingAs(superAdmin())->get(ExecutionReport::mediaUrl($media))->assertOk();
    $this->get(route('execution.media', $media))->assertForbidden();
});

it('scopes vendor PICs to their own vendor and stops them verifying', function () {
    $pic = userWithRoles(RoleName::VendorPic);
    $pic->forceFill(['vendor_id' => ugandaVendor()->id])->save();
    $mine = verifiedOrder();
    $other = verifiedOrder();

    foreach ([$mine, $other] as $o) {
        app(RecordAkad::class)->handle($o, AkadMethod::Phone, true, superAdmin());
    }
    app(AssignAllocation::class)->handle($mine, $mine->country_id, ugandaVendor()->id, superAdmin());

    Livewire::actingAs($pic)->test(ExecutionIndex::class)
        ->assertSee($mine->order_no)
        ->assertDontSee($other->order_no);

    app(SubmitExecutionReport::class)->handle($mine, [UploadedFile::fake()->image('a.png')], [], null, $pic);

    expect(fn () => app(VerifyExecutionReport::class)->handle($mine, $pic))
        ->toThrow(HttpException::class);

    $media = $mine->fresh()->executionReport->getFirstMedia('images');
    $stranger = userWithRoles(RoleName::VendorPic);
    $stranger->forceFill(['vendor_id' => Vendor::where('code', 'SP 002')->value('id')])->save();
    $this->actingAs($stranger)->get(ExecutionReport::mediaUrl($media))->assertForbidden();
});

it('enforces manage permissions on the pipeline screens', function () {
    $sales = userWithRoles(RoleName::Sales);
    $order = verifiedOrder();

    $this->actingAs($sales);
    $canView = fn (string $perm) => $sales->can($perm);

    if ($canView('shipping.view')) {
        Livewire::test(ShippingIndex::class)->call('openGenerate', $order->id)->assertForbidden();
    }

    if ($canView('certificates.view')) {
        Livewire::test(Editor::class)->call('save')->assertForbidden();
    }

    expect($sales->can('allocation.manage'))->toBeFalse();
    $this->get(route('execution.index'))->assertStatus($canView('execution.view') ? 200 : 403);
});

it('generates the AWB from the screen and lists it in Tempahan Selesai', function () {
    $order = verifiedOrder();
    app(RecordAkad::class)->handle($order, AkadMethod::Phone, true, superAdmin());
    app(AssignAllocation::class)->handle($order, $order->country_id, ugandaVendor()->id, superAdmin());
    app(SubmitExecutionReport::class)->handle($order, [UploadedFile::fake()->image('a.png')], [], null, superAdmin());
    app(VerifyExecutionReport::class)->handle($order, superAdmin());

    Livewire::actingAs(superAdmin())->test(ShippingIndex::class)
        ->call('openGenerate', $order->id)
        ->set('courier', Courier::JntExpress->value)
        ->assertSee(Courier::JntExpress->consignmentFor($order->order_no))
        ->set('address', 'No. 1, Jalan Baru')
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSet('tab', 'dijana');

    expect($order->fresh()->shipment->address)->toBe('No. 1, Jalan Baru');

    Livewire::actingAs(superAdmin())->test(Completed::class)
        ->assertSee($order->order_no)
        ->call('openDetail', $order->id)
        ->assertSee('Garis Masa Proses');

    Pdf::fake();
    $this->actingAs(superAdmin())->get(route('shipping.awb.pdf', ['ids' => [$order->id]]))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.airway-bill');
});

it('saves the certificate template and downloads sample and bulk PDFs', function () {
    Livewire::actingAs(superAdmin())->test(Editor::class)
        ->set('form.title', 'SIJIL IBADAH')
        ->set('form.name', 'AHMAD')
        ->assertSee('SIJIL IBADAH')
        ->call('save')
        ->assertHasNoErrors()
        ->call('download')
        ->assertFileDownloaded('Sijil-NQ-SIJIL-2027-0248.pdf');

    expect(app(Settings::class)->get('certificate.title'))->toBe('SIJIL IBADAH')
        ->and(app(CertificateTemplate::class)->template()['title'])->toBe('SIJIL IBADAH');

    $order = verifiedOrder(3);
    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)
        ->set('selected', [$order->id])
        ->call('generateCertificates')
        ->assertFileDownloaded('Sijil-'.$order->order_no.'.pdf');

    expect($order->certificates()->count())->toBe(3);
})->skip(fn () => ! getenv('LARAVEL_PDF_CHROME_PATH') && ! env('LARAVEL_PDF_CHROME_PATH'), 'Chrome not configured');

it('shows the main participant in full on /jejak and masks the others unless the token matches', function () {
    $order = verifiedOrder(2);
    $order->participants()->where('position', 2)->update(['name' => 'Aminah Salleh']);

    expect(Tracking::mask('Iskandar bin Yusof'))->toBe('Iskandar b*** Yusof')
        ->and(Tracking::mask('Aminah Salleh'))->toBe('Aminah S***');

    Livewire::test(Tracking::class, ['query' => $order->tracking_no])
        ->assertSee($order->customer->name)
        ->assertSee('Aminah S***')
        ->assertDontSee('Aminah Salleh');

    Livewire::withQueryParams(['track' => $order->order_no, 't' => $order->tracking_token])
        ->test(Tracking::class)
        ->assertSee('Iskandar bin Yusof')
        ->assertSee('Bayaran Disahkan');

    $this->get('/jejak?track=TIADA')->assertOk()->assertSee('Tiada rekod dijumpai');
});

it('rate-limits public tracking lookups', function () {
    $order = verifiedOrder();

    foreach (range(1, 20) as $_) {
        Livewire::test(Tracking::class, ['query' => $order->tracking_no]);
    }

    Livewire::test(Tracking::class, ['query' => $order->tracking_no])->assertSee('Terlalu banyak carian');
});

it('seeds consistent pipeline demo data', function () {
    $this->seed(DemoOrderSeeder::class);

    $completed = Order::where('stage', OrderStage::Completed)->with(['akad', 'allocation', 'executionReport', 'shipment', 'certificates'])->get();

    expect($completed)->not->toBeEmpty();
    $completed->each(fn (Order $o) => expect($o->akad)->not->toBeNull()
        ->and($o->allocation)->not->toBeNull()
        ->and($o->executionReport)->not->toBeNull()
        ->and($o->shipment)->not->toBeNull()
        ->and($o->certificates)->toHaveCount($o->quantity));

    expect(User::where('email', 'vendor@albarakah.ug')->exists() ? User::where('email', 'vendor@albarakah.ug')->value('vendor_id') : ugandaVendor()->id)
        ->toBe(ugandaVendor()->id);
});
