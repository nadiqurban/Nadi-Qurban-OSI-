<?php

use App\Actions\Booking\CreatePublicBooking;
use App\Actions\Booking\StartBookingPayment;
use App\Actions\Installments\ProcessChipPurchase;
use App\Actions\Installments\StartPortalPayment;
use App\Enums\InstallmentStatus;
use App\Enums\OrderStage;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PortalPayMethod;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Document;
use App\Models\InstallmentPlan;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\PaymentGatewayTransaction;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Chip\ChipGateway;
use App\Services\Chip\FakeChipGateway;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

it('replaces the demo data with one example per segment and keeps users', function () {
    $this->seed(DatabaseSeeder::class);
    $users = User::query()->count();
    expect(Order::query()->count())->toBeGreaterThan(10);

    $this->artisan('nq:sample-data', ['--force' => true])->assertSuccessful()->run();

    expect(User::query()->count())->toBe($users)
        ->and(Vendor::query()->count())->toBe(1)
        ->and(Product::query()->count())->toBe(1)
        ->and(PromoCode::query()->count())->toBe(1)
        ->and(PurchaseOrder::query()->count())->toBe(1)
        ->and(Invoice::query()->count())->toBe(1)
        ->and(Lead::query()->count())->toBe(1)
        ->and(InstallmentPlan::query()->count())->toBe(1)
        ->and(Document::query()->where('source', 'upload')->count())->toBe(1);

    // One order waiting at each pipeline screen.
    $stages = Order::query()->pluck('stage')->map(fn (OrderStage $s) => $s->value)->sort()->values()->all();
    expect($stages)->toBe(collect([
        OrderStage::Received, OrderStage::PaymentVerified, OrderStage::AkadDone,
        OrderStage::Executing, OrderStage::FinalReport, OrderStage::Completed,
    ])->map->value->sort()->values()->all());

    expect(Customer::query()->count())->toBeLessThanOrEqual(7)
        ->and(User::query()->where('email', 'vendor@albarakah.ug')->value('vendor_id'))->toBe(Vendor::query()->value('id'));

    // Every screen still opens for the Super Admin.
    $admin = User::query()->where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
    $this->actingAs($admin);
    foreach (app(Navigation::class)->for($admin)->flatMap(fn (array $g) => $g['items'])->reject(fn (array $i) => isset($i['modal'])) as $item) {
        $this->get($item['href'])->assertOk();
    }
    $this->get('/tempahan/'.Order::query()->value('id'))->assertOk();
    $this->get('/vendor/'.Vendor::query()->value('id'))->assertOk();
});

it('runs on an empty database too (fresh install)', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(SettingsSeeder::class);
    superAdmin();

    $this->artisan('nq:sample-data', ['--force' => true])->assertSuccessful()->run();

    expect(Order::query()->count())->toBeGreaterThanOrEqual(6)
        ->and(Vendor::query()->count())->toBe(1);
});

it('empties the operational data without examples when --kosong is given', function () {
    $this->seed(DatabaseSeeder::class);
    $users = User::query()->count();

    $this->artisan('nq:sample-data', ['--force' => true, '--kosong' => true])->assertSuccessful()->run();

    expect(Order::query()->count())->toBe(0)
        ->and(Product::query()->count())->toBe(0)
        ->and(Vendor::query()->count())->toBe(0)
        ->and(User::query()->count())->toBe($users)
        ->and(Country::query()->count())->toBeGreaterThan(0);

    $this->actingAs(User::query()->where('email', 'nurfitri@nadiqurban.com')->firstOrFail())
        ->get('/dashboard')->assertOk();
});

it('keeps orders and instalment plans paid through CHIP when emptying', function () {
    $this->seed(DatabaseSeeder::class);
    app()->instance(ChipGateway::class, new FakeChipGateway);

    // A public booking paid by FPX (fake CHIP = paid) …
    $product = Product::query()->where('name', 'Qurban Lembu Uganda')->firstOrFail();
    $order = app(CreatePublicBooking::class)->handle(
        ['name' => 'Pelanggan Sebenar', 'phone' => '011-11112222', 'email' => 'sebenar@example.com', 'address' => 'Jalan 1', 'postcode' => null, 'city' => null, 'state' => 'Selangor'],
        $product, 1, ['Pelanggan Sebenar'], null, PaymentMethod::Fpx, null, null,
    );
    $tx = app(StartBookingPayment::class)->handle($order);
    app(ProcessChipPurchase::class)->handle(app(ChipGateway::class)->getPurchase((string) $tx->purchase_id));

    // … and an instalment month paid through the portal.
    $plan = InstallmentPlan::query()->firstOrFail();
    $ptx = app(StartPortalPayment::class)->handle($plan, [], PortalPayMethod::Card);
    app(ProcessChipPurchase::class)->handle(app(ChipGateway::class)->getPurchase((string) $ptx->purchase_id));

    $orderSeq = DB::table('sequences')->where('name', 'order')->value('next_value');

    $this->artisan('nq:sample-data', ['--force' => true, '--kosong' => true])
        ->expectsOutputToContain('Dikekalkan (bayaran CHIP berjaya): 1 tempahan, 1 pelan ansuran')
        ->assertSuccessful()->run();

    expect(Order::query()->pluck('id')->all())->toBe([$order->id])
        ->and($order->fresh()->payment->status)->toBe(PaymentStatus::Verified)
        ->and($order->fresh()->participants()->count())->toBe(1)
        ->and(Customer::query()->whereKey($order->customer_id)->exists())->toBeTrue()
        ->and(Product::query()->pluck('id')->all())->toContain($product->id)
        ->and(InstallmentPlan::query()->pluck('id')->all())->toBe([$plan->id])
        ->and($plan->fresh()->installments()->where('status', InstallmentStatus::Paid)->count())->toBeGreaterThan(0)
        ->and(PaymentGatewayTransaction::query()->count())->toBe(2)
        ->and(Vendor::query()->count())->toBe(0)
        ->and(DB::table('sequences')->where('name', 'order')->value('next_value'))->toBe($orderSeq);

    $this->actingAs(User::query()->where('email', 'nurfitri@nadiqurban.com')->firstOrFail());
    $this->get('/tempahan/'.$order->id)->assertOk();
    $this->get('/ansuran')->assertOk();
});

it('re-creates a CHIP-paid public booking missing from the database', function () {
    $this->seed(DatabaseSeeder::class);
    app()->instance(ChipGateway::class, new FakeChipGateway);

    $product = Product::query()->where('name', 'Qurban Lembu Uganda')->firstOrFail();
    $order = app(CreatePublicBooking::class)->handle(
        ['name' => 'Roslan Bin Arifin', 'phone' => '012-3456789', 'email' => 'roslan@example.com', 'address' => 'Jalan 1', 'postcode' => null, 'city' => null, 'state' => 'Selangor'],
        $product, 2, ['Roslan Bin Arifin', 'Aminah'], null, PaymentMethod::Fpx, null, null,
    );
    $tx = app(StartBookingPayment::class)->handle($order);
    $purchaseId = (string) $tx->purchase_id;
    [$orderNo, $total, $reference] = [$order->order_no, $order->total_sen, $tx->reference];

    // Lost: the order and its CHIP transaction are gone from the database.
    $tx->delete();
    $order->forceDelete();
    DB::table('sequences')->delete();

    $this->artisan('nq:restore-chip', ['purchase' => [$purchaseId]])
        ->expectsOutputToContain("{$orderNo} ·")
        ->expectsOutputToContain('Siap: 1 tempahan dimasukkan semula.')
        ->assertSuccessful()->run();

    $restored = Order::query()->where('order_no', $orderNo)->firstOrFail();

    expect($restored->total_sen)->toBe($total)
        ->and($restored->quantity)->toBe(2)
        ->and($restored->stage)->toBe(OrderStage::PaymentVerified)
        ->and($restored->country->name)->toBe('Uganda')
        ->and($restored->customer->name)->toBe('Roslan Bin Arifin')
        ->and($restored->payment->status)->toBe(PaymentStatus::Verified)
        ->and($restored->participants()->count())->toBe(2)
        ->and(PaymentGatewayTransaction::query()->where('purchase_id', $purchaseId)->value('status'))->toBe(PaymentGatewayTransaction::PAID)
        ->and((int) DB::table('sequences')->where('name', 'order')->value('next_value'))->toBe((int) substr($orderNo, -6) + 1)
        ->and((int) DB::table('sequences')->where('name', 'gateway-payment')->value('next_value'))->toBe((int) substr($reference, 5) + 1);

    // Running it again changes nothing.
    $this->artisan('nq:restore-chip', ['purchase' => [$purchaseId]])
        ->expectsOutputToContain('sudah ada dalam sistem')->assertSuccessful()->run();

    $this->actingAs(User::query()->where('email', 'nurfitri@nadiqurban.com')->firstOrFail());
    $this->get('/tempahan/'.$restored->id)->assertOk();
});

it('gives an order its own customer when two people shared a phone number', function () {
    $this->seed(DatabaseSeeder::class);
    $order = Order::query()->with('customer')->firstOrFail();
    $old = $order->customer;

    $this->artisan('nq:split-customer', ['order' => $order->order_no, 'name' => 'Fauziah Binti Mohd Ashak'])
        ->expectsOutputToContain('kini di bawah pelanggan Fauziah Binti Mohd Ashak')->assertSuccessful()->run();

    $order->refresh();
    expect($order->customer->name)->toBe('Fauziah Binti Mohd Ashak')
        ->and($order->customer->phone)->toBe($old->phone)
        ->and($old->fresh()->name)->toBe($old->name);
});
