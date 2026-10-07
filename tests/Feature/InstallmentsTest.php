<?php

use App\Actions\Installments\CreatePlan;
use App\Actions\Installments\RefreshPlanStatus;
use App\Actions\Installments\SendPlanToOrder;
use App\Enums\DiscountType;
use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Enums\OrderStatus;
use App\Enums\RoleName;
use App\Livewire\Installments\Index;
use App\Livewire\Public\InstallmentPortal;
use App\Mail\InstallmentReminder;
use App\Models\InstallmentPlan;
use App\Models\PaymentGatewayTransaction;
use App\Models\Product;
use App\Models\PromoCode;
use App\Services\Chip\ChipGateway;
use App\Services\Chip\FakeChipGateway;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoCatalogSeeder::class]);
    $this->chip = new FakeChipGateway;
    app()->instance(ChipGateway::class, $this->chip);
});

/** @param  array<string, mixed>  $plan */
function makePlan(array $plan = [], ?string $start = null): InstallmentPlan
{
    return app(CreatePlan::class)->handle(
        ['name' => 'Ahmad Zaki bin Hassan', 'phone' => '012-3345671', 'email' => 'zaki@example.com', 'state' => 'Selangor'],
        $plan + [
            'product_id' => Product::where('name', 'Qurban Lembu Uganda')->value('id'),   // RM 3,500
            'quantity' => 1, 'year' => 2027, 'months' => 6, 'deposit_sen' => 0, 'payment_method' => 'fpx_auto',
            'start_date' => $start,
        ],
        [],
        null,
        superAdmin(),
    )->load(['installments', 'customer']);
}

it('splits the balance into whole-ringgit months with the remainder last', function () {
    $plan = makePlan();

    expect($plan->order_no)->toBe('NQ-QB-LE-001249')
        ->and($plan->installments->pluck('amount_sen')->all())->toBe([58300, 58300, 58300, 58300, 58300, 58500])
        ->and($plan->monthly_sen)->toBe(58300)
        ->and($plan->installments->first()->due_date->isToday())->toBeTrue()
        ->and($plan->installments->last()->due_date->toDateString())->toBe(today()->addMonthsNoOverflow(5)->toDateString());

    foreach ([3, 9, 12] as $months) {
        expect((int) makePlan(['months' => $months])->installments->sum('amount_sen'))->toBe(350000);
    }
});

it('applies deposit and both promo types before splitting', function () {
    PromoCode::create(['code' => 'UJI50', 'type' => DiscountType::Fixed, 'value' => 5000, 'is_active' => true]);
    PromoCode::create(['code' => 'UJI10', 'type' => DiscountType::Percent, 'value' => 10, 'is_active' => true]);

    $fixed = makePlan(['promo_code' => 'UJI50', 'deposit_sen' => 50000]);
    expect($fixed->total_sen)->toBe(345000)
        ->and($fixed->financedSen())->toBe(295000)
        ->and((int) $fixed->installments->sum('amount_sen'))->toBe(295000)
        ->and($fixed->installments->first()->amount_sen)->toBe(49100);

    $percent = makePlan(['promo_code' => 'UJI10', 'months' => 3]);
    expect($percent->discount_sen)->toBe(35000)
        ->and((int) $percent->installments->sum('amount_sen'))->toBe(315000)
        ->and(PromoCode::where('code', 'UJI10')->value('used_count'))->toBe(1);

    expect(fn () => makePlan(['months' => 4]))->toThrow(ValidationException::class)
        ->and(fn () => makePlan(['deposit_sen' => 400000]))->toThrow(ValidationException::class);
});

it('creates a plan from the Pelan Baharu modal', function () {
    Livewire::actingAs(superAdmin())->test(Index::class)
        ->call('create')
        ->set('form.name', 'Sofea binti Kamal')
        ->set('form.phone', '017-7781220')
        ->set('form.productId', Product::where('name', 'Qurban Lembu Uganda')->value('id'))
        ->set('form.quantity', '2')
        ->set('form.months', '12')
        ->set('form.deposit', '1000')
        ->assertSee('Baki Ansuran')
        ->assertSee('RM 6,000')
        ->set('form.participants.1', 'Kamal bin Ahmad')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $plan = InstallmentPlan::with('installments', 'customer')->sole();
    expect($plan->months)->toBe(12)
        ->and($plan->participantList())->toBe(['Sofea binti Kamal', 'Kamal bin Ahmad'])
        ->and((int) $plan->installments->sum('amount_sen'))->toBe(600000);
});

it('requires a bank receipt for manual transfer plans', function () {
    Livewire::actingAs(superAdmin())->test(Index::class)
        ->call('create')
        ->set('form.name', 'X')->set('form.phone', '012-1234567')
        ->set('form.productId', Product::where('name', 'Qurban Lembu Uganda')->value('id'))
        ->set('form.paymentMethod', 'manual')
        ->call('save')
        ->assertHasErrors('form.proof');
});

it('confirms and undoes months from the progress bar', function () {
    $plan = makePlan();
    $lw = Livewire::actingAs(superAdmin())->test(Index::class);

    $lw->call('segment', $plan->id, 3)->assertSet('showPay', true)->assertSet('payMonth', 3)
        ->set('payMethod', 'Mesin Deposit Tunai')
        ->call('confirmPayment');

    $plan->refresh()->load('installments');
    expect($plan->paidCount())->toBe(3)
        ->and($plan->installments->first()->method)->toBe('Mesin Deposit Tunai');

    $lw->call('segment', $plan->id, 3);   // last paid → undo it
    expect($plan->refresh()->load('installments')->paidCount())->toBe(2);

    $lw->call('segment', $plan->id, 1);   // earlier paid → keep month 1 only
    expect($plan->refresh()->load('installments')->paidCount())->toBe(1);
});

it('cancels with a reason, restores, and sends a completed plan to Pengesahan Bayaran', function () {
    $plan = makePlan(['months' => 3]);

    Livewire::actingAs(superAdmin())->test(Index::class)
        ->set('selected', [$plan->id])
        ->call('openCancel')
        ->call('cancelSelected')
        ->assertHasErrors('cancelReason')
        ->set('cancelReason', 'Pelanggan menarik diri')
        ->call('cancelSelected')
        ->assertSet('tab', 'batal')
        ->assertSee('Sebab: Pelanggan menarik diri')
        ->call('restore', $plan->id);

    expect($plan->refresh()->status)->toBe(InstallmentPlanStatus::Ongoing);

    expect(fn () => app(SendPlanToOrder::class)->handle($plan, superAdmin()))->toThrow(ValidationException::class);

    $plan->installments()->update(['status' => InstallmentStatus::Paid, 'paid_at' => now(), 'method' => 'Perbankan Internet']);
    app(RefreshPlanStatus::class)->handle($plan);

    Livewire::actingAs(superAdmin())->test(Index::class, ['tab' => 'selesai'])
        ->call('send', $plan->id)
        ->assertSee('Dihantar');

    $order = $plan->refresh()->order;
    expect($order->order_no)->toBe($plan->order_no)
        ->and($order->is_instalment)->toBeTrue()
        ->and($order->status)->toBe(OrderStatus::Accepted)
        ->and($order->total_sen)->toBe($plan->total_sen)
        ->and($plan->sent_at)->not->toBeNull();

    $this->actingAs(superAdmin())->get(route('payments.verify'))->assertSee($plan->order_no)->assertSee('ANSURAN');
});

it('pays selected months through CHIP from the public portal', function () {
    $plan = makePlan();

    $this->get(route('installments.portal', $plan->pay_token))->assertOk()->assertSee('Jadual Ansuran')->assertSee('Bayar RM 583 Sekarang');
    $this->get('/bayar/'.str_repeat('x', 48))->assertNotFound();

    Livewire::test(InstallmentPortal::class, ['token' => $plan->pay_token])
        ->call('toggle', 1)->call('toggle', 2)
        ->set('method', 'kad')
        ->assertSee('Bayar RM 1,166 Sekarang')
        ->call('pay')
        ->assertRedirect();

    $tx = PaymentGatewayTransaction::sole();
    expect($tx->amount_sen)->toBe(116600)
        ->and($tx->installment_ids)->toHaveCount(2)
        ->and($tx->status)->toBe('created');

    // Customer returns from CHIP → purchase re-checked → months marked paid.
    Livewire::withQueryParams(['tx' => $tx->reference])->test(InstallmentPortal::class, ['token' => $plan->pay_token])
        ->assertSet('payState', 'done')
        ->assertSee('Bayaran Berjaya!');

    $plan->refresh()->load('installments');
    expect($plan->paidCount())->toBe(2)
        ->and($plan->installments->first()->method)->toBe('CHIP · Kad Kredit/Debit')
        ->and($tx->refresh()->status)->toBe('paid');
});

it('keeps months unpaid when the CHIP payment fails', function () {
    $plan = makePlan();
    $this->chip->failNext = true;

    Livewire::test(InstallmentPortal::class, ['token' => $plan->pay_token])->call('pay')->assertRedirect();
    $tx = PaymentGatewayTransaction::sole();

    Livewire::withQueryParams(['tx' => $tx->reference])->test(InstallmentPortal::class, ['token' => $plan->pay_token])
        ->assertSet('payState', 'failed')
        ->assertSee('Perlu Bayar');

    expect($plan->refresh()->load('installments')->paidCount())->toBe(0)
        ->and($tx->refresh()->status)->toBe('failed');
});

it('verifies the webhook signature and is idempotent', function () {
    $plan = makePlan();
    Livewire::test(InstallmentPortal::class, ['token' => $plan->pay_token])->call('pay');
    $tx = PaymentGatewayTransaction::sole();

    $body = json_encode(['id' => $tx->purchase_id, 'status' => 'paid', 'event_type' => 'purchase.paid', 'reference' => $tx->reference]);

    $this->call('POST', route('webhooks.chip'), [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => base64_encode('forged')], $body)
        ->assertStatus(401);
    expect($plan->refresh()->load('installments')->paidCount())->toBe(0);

    $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $this->chip->sign($body)];
    $this->call('POST', route('webhooks.chip'), [], [], [], $headers, $body)->assertOk()->assertJson(['message' => 'paid']);
    $this->call('POST', route('webhooks.chip'), [], [], [], $headers, $body)->assertOk();

    expect($plan->refresh()->load('installments')->paidCount())->toBe(1)
        ->and(PaymentGatewayTransaction::where('status', 'paid')->count())->toBe(1);
});

it('marks overdue plans Lewat Bayar and sends H-3 / H+1 reminders once', function () {
    Mail::fake();

    $late = makePlan(start: today()->subMonth()->subDay()->toDateString());      // month 1 & 2 overdue
    $soon = makePlan(start: today()->addDays(3)->toDateString());               // month 1 due in 3 days

    $this->artisan('installments:daily')->assertSuccessful();

    expect($late->refresh()->status)->toBe(InstallmentPlanStatus::Late)
        ->and($soon->refresh()->status)->toBe(InstallmentPlanStatus::Ongoing);

    Mail::assertQueued(InstallmentReminder::class, fn ($m) => $m->when === 'before' && $m->installment->installment_plan_id === $soon->id);
    Mail::assertQueued(InstallmentReminder::class, fn ($m) => $m->when === 'after' && $m->installment->installment_plan_id === $late->id);

    $this->artisan('installments:daily');
    Mail::assertQueued(InstallmentReminder::class, 2);
});

it('lets only instalment managers change plans', function () {
    $plan = makePlan();
    $sales = userWithRoles(RoleName::Sales);

    if ($sales->can('installments.view') && ! $sales->can('installments.manage')) {
        Livewire::actingAs($sales)->test(Index::class)->call('segment', $plan->id, 1)->assertForbidden();
    }

    $vendor = userWithRoles(RoleName::VendorPic);
    $this->actingAs($vendor)->get(route('installments.index'))->assertForbidden();
    $this->actingAs($vendor)->get(route('installments.receipt', $plan))->assertForbidden();
});

it('offers only FPX and card in the portal (no DuitNow QR or e-wallet)', function () {
    $plan = makePlan();

    $this->get(route('installments.portal', $plan->pay_token))
        ->assertOk()
        ->assertSee('FPX Online Banking')->assertSee('Kad Kredit / Debit')
        ->assertDontSee('DuitNow QR')->assertDontSee('E-Wallet (TnG, GrabPay)');
});
