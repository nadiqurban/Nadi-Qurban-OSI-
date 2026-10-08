<?php

use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\VerifyPayment;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Events\OrderStageChanged;
use App\Livewire\Orders\Index;
use App\Livewire\Orders\Index as OrdersIndex;
use App\Livewire\Orders\Show as OrdersShow;
use App\Livewire\Payments\Verify;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Navigation\Badges\PendingPayments;
use App\Support\ParticipantGroups;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

beforeEach(function () {
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoCatalogSeeder::class]);
    Storage::fake('local');
});

function product(string $name = 'Qurban Lembu Uganda'): Product
{
    return Product::where('name', $name)->firstOrFail();
}

function makeOrder(array $order = [], array $customer = [], ?UploadedFile $proof = null): Order
{
    return app(CreateOrder::class)->handle(
        $customer + ['name' => 'Ahmad Zaki bin Hassan', 'phone' => '012-3456789', 'state' => 'Selangor'],
        $order + ['product_id' => product()->id, 'quantity' => 1, 'year' => 2027, 'payment_method' => 'fpx'],
        [],
        $proof,
        superAdmin(),
    );
}

it('creates an order from the modal with auto number, price from product and proof', function () {
    $admin = superAdmin();

    Livewire::actingAs($admin)->test(OrdersIndex::class)
        ->call('create')
        ->set('form.name', 'Farid bin Kassim')
        ->set('form.phone', '012-7788990')
        ->set('form.address', 'No. 30, Jalan Tulip 7')
        ->set('form.postcode', '47301')
        ->set('form.city', 'Petaling Jaya')
        ->set('form.productId', product()->id)
        ->set('form.quantity', '7')
        ->assertSee('RM 24,500')
        ->set('form.paymentMethod', 'pindahan_bank')
        ->set('form.proof', UploadedFile::fake()->image('resit.png'))
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false)
        ->assertDispatched('toast');

    $order = Order::with(['customer', 'participants', 'payment', 'stageHistories'])->sole();

    expect($order->order_no)->toBe('NQ-QB-LE-001249')
        ->and($order->tracking_no)->toBe('NQT-2027-001249')
        ->and($order->total_sen)->toBe(2450000)
        ->and($order->status)->toBe(OrderStatus::Draft)
        ->and($order->stage)->toBe(OrderStage::Received)
        ->and($order->customer->code)->toBe('CUST-10240')
        ->and($order->participants)->toHaveCount(7)
        ->and($order->participants->first()->name)->toBe('Farid bin Kassim')
        ->and($order->payment->status)->toBe(PaymentStatus::Pending)
        ->and($order->payment->proof())->not->toBeNull()
        ->and($order->stageHistories->pluck('stage')->all())->toBe([OrderStage::Received]);
});

it('numbers orders uniquely and sequentially', function () {
    $numbers = collect(range(1, 5))->map(fn () => makeOrder()->order_no);

    expect($numbers->unique())->toHaveCount(5)
        ->and($numbers->last())->toBe('NQ-QB-LE-001253');

    $goat = makeOrder(['product_id' => product('Aqiqah Kambing Malaysia')->id]);
    expect($goat->order_no)->toBe('NQ-AQ-KA-001254');
});

it('applies percent and fixed promo codes and rejects bad ones', function () {
    $percent = makeOrder(['promo_code' => 'awalqurban']);       // 10%
    $fixed = makeOrder(['promo_code' => 'RAYA50']);             // RM 50

    expect($percent->discount_sen)->toBe(35000)->and($percent->total_sen)->toBe(315000)
        ->and($fixed->discount_sen)->toBe(5000)->and($fixed->total_sen)->toBe(345000);

    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)
        ->call('create')
        ->set('form.name', 'X')
        ->set('form.phone', '0123456789')
        ->set('form.productId', product()->id)
        ->set('form.promo', 'EARLYBIRD26') // ended
        ->call('save')
        ->assertHasErrors('form.promo');
});

it('reuses an existing customer by phone number', function () {
    makeOrder();
    makeOrder([], ['phone' => '0123456789', 'email' => 'zaki@email.com']);

    expect(Customer::count())->toBe(1)
        ->and(Customer::first()->email)->toBe('zaki@email.com');
});

it('refuses orders above the available stock', function () {
    $camel = product('Nazar Unta Sudan'); // stock 5

    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)
        ->call('create')
        ->set('form.name', 'Hafiz')
        ->set('form.phone', '0178899001')
        ->set('form.productId', $camel->id)
        ->set('form.quantity', '6')
        ->call('save')
        ->assertHasErrors(['form.quantity']);
});

it('lists, filters and searches orders', function () {
    makeOrder();
    makeOrder(['product_id' => product('Aqiqah Kambing Malaysia')->id], ['name' => 'Nurul Aina binti Rahim', 'phone' => '013-9988776']);

    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)
        ->assertSee('NQ-QB-LE-001249')
        ->assertSee('NQ-AQ-KA-001250')
        ->set('service', 'aqiqah')
        ->assertDontSee('NQ-QB-LE-001249')
        ->set('service', '')
        ->set('search', '9988776')
        ->assertSee('Nurul Aina')
        ->assertDontSee('Ahmad Zaki');
});

it('moves an order through Diterima → Sahkan with stock, promo and history', function () {
    Event::fake([OrderStageChanged::class]);
    $order = makeOrder(['quantity' => 2, 'promo_code' => 'AWALQURBAN']);
    $stock = product()->stock;

    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)
        ->set('selected', [$order->id])
        ->call('markAccepted')
        ->assertSet('selected', []);

    expect($order->fresh()->status)->toBe(OrderStatus::Accepted)
        ->and((new PendingPayments)())->toBe(1);

    Livewire::actingAs(userWithRoles(RoleName::Finance))->test(Verify::class)
        ->assertSee($order->order_no)
        ->call('confirm', $order->id)
        ->assertDispatched('toast');

    $order->refresh();
    $promo = PromoCode::where('code', 'AWALQURBAN')->first();

    expect($order->status)->toBe(OrderStatus::InProgress)
        ->and($order->stage)->toBe(OrderStage::PaymentVerified)
        ->and($order->payment->status)->toBe(PaymentStatus::Verified)
        ->and($order->payment->verified_by)->not->toBeNull()
        ->and(product()->stock)->toBe($stock - 2)
        ->and($promo->used_count)->toBe(313)
        ->and($promo->total_discount_sen)->toBe(1560000 + 70000)
        ->and($order->stageHistories()->pluck('stage')->map->value->all())->toBe(['received', 'payment_verified']);

    Event::assertDispatched(OrderStageChanged::class, fn ($e) => $e->to === OrderStage::PaymentVerified);

    Livewire::actingAs(superAdmin())->test(Verify::class, ['tab' => 'telah'])->assertSee($order->order_no);
});

it('rejects a payment with a reason and sends it back to Menunggu Bayaran', function () {
    $order = makeOrder();
    $order->update(['status' => OrderStatus::Accepted]);

    Livewire::actingAs(superAdmin())->test(Verify::class)
        ->call('openReject', $order->id)
        ->set('reason', '')
        ->call('reject')
        ->assertHasErrors('reason')
        ->set('reason', 'Jumlah resit tidak sepadan')
        ->call('reject')
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($order->payment->status)->toBe(PaymentStatus::Rejected)
        ->and($order->payment->rejection_reason)->toBe('Jumlah resit tidak sepadan');

    // Marking it Diterima again re-opens the payment for verification.
    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)->set('selected', [$order->id])->call('markAccepted');
    expect($order->fresh()->payment->status)->toBe(PaymentStatus::Pending);
});

it('only verifies orders that are waiting', function () {
    $order = makeOrder(); // still Draf

    expect(fn () => app(VerifyPayment::class)->handle($order, superAdmin()))
        ->toThrow(ValidationException::class);
});

it('updates status in bulk but never un-verifies an order', function () {
    $a = makeOrder();
    $b = makeOrder();
    $b->update(['status' => OrderStatus::Accepted]);
    app(VerifyPayment::class)->handle($b, superAdmin());

    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)
        ->set('selected', [$a->id, $b->id])
        ->call('setStatus', 'draf');

    expect($b->fresh()->status)->toBe(OrderStatus::InProgress);

    Livewire::actingAs(superAdmin())->test(OrdersIndex::class)
        ->set('selected', [$a->id])
        ->call('setStatus', 'dibatalkan');

    expect($a->fresh()->status)->toBe(OrderStatus::Cancelled);
});

it('enforces order and payment permissions', function () {
    $order = makeOrder();

    // Kewangan: Tempahan = Lihat, Pengesahan = Penuh
    $finance = userWithRoles(RoleName::Finance);
    $this->actingAs($finance)->get('/tempahan')->assertOk()->assertDontSeeHtml('wire:click="create"');
    Livewire::actingAs($finance)->test(OrdersIndex::class)->call('create')->assertForbidden();
    Livewire::actingAs($finance)->test(OrdersIndex::class)->set('selected', [$order->id])->call('markAccepted')->assertForbidden();
    $this->actingAs($finance)->get('/pengesahan-bayaran')->assertOk();

    // Sales: Tempahan = Penuh, Pengesahan = Tiada
    $sales = userWithRoles(RoleName::Sales);
    $this->actingAs($sales)->get('/pengesahan-bayaran')->assertForbidden();
    Livewire::actingAs($sales)->test(Verify::class)->call('confirm', $order->id)->assertForbidden();

    // Vendor PIC: Tempahan = Tiada
    $this->actingAs(userWithRoles(RoleName::VendorPic))->get(route('orders.show', $order))->assertForbidden();
});

it('shows the order detail with workflow and edits participants, details and proof', function () {
    $order = makeOrder(['quantity' => 3]);
    $admin = superAdmin();

    $this->actingAs($admin)->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee($order->order_no)
        ->assertSee('Link Tracking Pelanggan')
        ->assertSee('Aliran Kerja')
        ->assertSee('Tempahan Diterima')
        ->assertSee('Senarai Peserta');

    Livewire::actingAs($admin)->test(OrdersShow::class, ['order' => $order])
        ->set('participants.1', 'Siti Fatimah binti Ali')
        ->set('participants.2', 'Mohd Hafiz bin Razak')
        ->call('saveParticipants')
        ->call('openEdit')
        ->set('edit.city', 'Klang')
        ->set('edit.implementation_date', '2027-06-18')
        ->call('saveEdit')
        ->assertHasNoErrors()
        ->set('proof', UploadedFile::fake()->createWithContent('resit.pdf', '%PDF-1.4
1 0 obj<</Type/Catalog>>endobj
trailer<</Root 1 0 R>>
%%EOF'))
        ->assertHasNoErrors();

    $order->refresh();
    expect($order->participantNames()->all())->toBe(['Ahmad Zaki bin Hassan', 'Siti Fatimah binti Ali', 'Mohd Hafiz bin Razak'])
        ->and($order->customer->city)->toBe('Klang')
        ->and($order->implementation_date->toDateString())->toBe('2027-06-18')
        ->and($order->payment->proofIsPdf())->toBeTrue();
});

it('rejects invalid proof files', function () {
    $order = makeOrder();

    Livewire::actingAs(superAdmin())->test(OrdersShow::class, ['order' => $order])
        ->set('proof', UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'))
        ->assertHasErrors('proof');
});

it('streams payment proofs only through signed URLs', function () {
    $order = makeOrder([], [], UploadedFile::fake()->image('resit.png'));
    $admin = superAdmin();

    $this->actingAs($admin)->get(route('payments.proof', $order->payment))->assertForbidden();
    $this->actingAs($admin)->get($order->payment->proofUrl())->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->actingAs(userWithRoles(RoleName::VendorPic))->get($order->payment->proofUrl())->assertForbidden();
});

it('renders receipt, waybill and participant PDFs', function () {
    Pdf::fake();
    $order = makeOrder(['quantity' => 7]);
    $admin = superAdmin();

    $this->actingAs($admin)->get(route('orders.receipt', $order))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.order-receipt' && $pdf->downloadName === 'Resit-'.$order->order_no.'.pdf');

    $this->actingAs($admin)->get(route('orders.waybill.pdf', ['ids' => [$order->id], 'kurier' => 'jnt']))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.waybill' && $pdf->viewData['courier']->label() === 'J&T Express');

    $this->actingAs($admin)->get(route('orders.participants.pdf', ['ids' => [$order->id], 'tarikh' => '2027-06-18']))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.participant-groups' && $pdf->viewData['date'] === '18-06-2027' && $pdf->viewData['tag'] === '#KORBANLEMBU #001249');

    $this->actingAs($admin)->get(route('orders.waybill.pdf'))->assertStatus(422);
});

it('renders the receipt template with company details, QR and participants', function () {
    $order = makeOrder(['quantity' => 2]);

    $html = view('pdf.order-receipt', ['order' => $order->load(['customer', 'country', 'participants'])])->render();

    expect($html)->toContain('RESIT TEMPAHAN')
        ->toContain($order->order_no)
        ->toContain('NADI QURBAN SDN. BHD.')
        ->toContain('Menara Ilham')
        ->toContain('<svg')
        ->toContain('Senarai Peserta (1)');
});

it('groups participants by animal capacity', function () {
    $cow = makeOrder(['quantity' => 9]);
    $goat = makeOrder(['product_id' => product('Aqiqah Kambing Malaysia')->id, 'quantity' => 2]);

    $groups = ParticipantGroups::for(collect([$cow->load(['customer', 'country', 'participants']), $goat->load(['customer', 'country', 'participants'])]));

    expect(collect($groups)->pluck('count')->all())->toBe([7, 2, 1, 1])
        ->and($groups[0]['full'])->toBeTrue()
        ->and($groups[1]['full'])->toBeFalse()
        ->and($groups[0]['names'][0])->toBe('AHMAD ZAKI BIN HASSAN');
});

it('exports orders, customers and payments to Excel', function () {
    Excel::fake();
    $order = makeOrder();
    $admin = superAdmin();

    Livewire::actingAs($admin)->test(OrdersIndex::class)->call('export');
    Excel::assertDownloaded('Tempahan-Nadi-Qurban-'.now()->format('Ymd').'.xlsx');

    Livewire::actingAs($admin)->test(OrdersIndex::class)->set('selected', [$order->id])->call('exportCustomers');
    Excel::assertDownloaded('Maklumat-Pelanggan-Nadi-Qurban.xlsx', fn ($export) => $export->collection()->first()[0] === 'Ahmad Zaki bin Hassan');

    Livewire::actingAs($admin)->test(Verify::class)->call('export');
    Excel::assertDownloaded('Pengesahan-Bayaran-Nadi-Qurban.xlsx');
});

it('cancels the selected orders with the Batal button (completed ones are kept)', function () {
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class]);
    $open = Order::where('status', OrderStatus::AwaitingPayment)->firstOrFail();
    $done = Order::where('status', OrderStatus::Completed)->firstOrFail();

    Livewire::actingAs(superAdmin())->test(Index::class)
        ->assertSee('Batal')
        ->set('selected', [$open->id, $done->id])
        ->call('cancelSelected')
        ->assertHasNoErrors();

    expect($open->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($done->fresh()->status)->toBe(OrderStatus::Completed);

    Livewire::actingAs(userWithRoles(RoleName::Finance))->test(Index::class)
        ->set('selected', [$done->id])->call('cancelSelected')->assertForbidden();
});
