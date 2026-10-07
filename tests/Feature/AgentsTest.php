<?php

use App\Actions\Orders\VerifyPayment;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RoleName;
use App\Livewire\Agent\Login as AgentLogin;
use App\Livewire\Agent\Portal as AgentPortal;
use App\Livewire\Agents\Index as AgentsIndex;
use App\Livewire\Public\Booking;
use App\Models\Agent;
use App\Models\AgentClick;
use App\Models\Order;
use App\Models\PaymentGatewayTransaction;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\Chip\ChipGateway;
use App\Services\Chip\FakeChipGateway;
use App\Support\AgentStats;
use App\Support\Period;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoCatalogSeeder::class]);
    app()->instance(ChipGateway::class, new FakeChipGateway);
});

function makeAgent(string $code = 'AZ01', string $name = 'Aiman bin Zulkifli', string $email = 'aiman@nadiqurban.com'): Agent
{
    Livewire::actingAs(superAdmin())->test(AgentsIndex::class)
        ->call('create')
        ->set('code', $code)->set('name', $name)->set('email', $email)->set('phone', '012-334 5671')
        ->set('password', 'EjenNq2027x')
        ->set('bankName', 'Maybank')->set('bankAccountName', $name)->set('bankAccountNo', '5623 5782 2681')
        ->call('save')
        ->assertHasNoErrors();

    app('auth')->forgetGuards();

    return Agent::query()->where('code', $code)->with('user')->firstOrFail();
}

/** @return array<string, mixed> */
function bookingForm(): array
{
    return ['name' => 'Rosmawati binti Idris', 'phone' => '012-3456789', 'email' => 'ros@example.com',
        'address' => 'No. 12, Jalan Melati 3', 'postcode' => '40150', 'city' => 'Shah Alam', 'state' => 'Selangor'];
}

function book(?string $slug, string $payType = 'fpx', ?UploadedFile $proof = null): mixed
{
    $product = Product::where('name', 'Qurban Lembu Uganda')->firstOrFail();   // RM 3,500 · komisen RM 50
    $t = Livewire::withQueryParams([])->test(Booking::class, $slug ? ['slug' => $slug] : [])
        ->call('start')
        ->call('pick', $product->id)
        ->call('toStep', 2);

    foreach (bookingForm() as $k => $v) {
        $t->set($k, $v);
    }

    $t->call('addParticipant')->set('participants.1', 'Faizal bin Kassim')
        ->call('toStep', 3)
        ->assertHasNoErrors()
        ->set('payType', $payType)
        ->set('akad', true);

    if ($proof) {
        $t->set('proof', $proof);
    }

    return $t->call('pay');
}

it('adds the Ejen role (locked to Tiada) and grants Pengurusan Ejen to existing roles', function () {
    $role = Role::findByName(RoleName::Agent->value);

    expect($role->isLockedMatrix())->toBeTrue()
        ->and($role->permissions)->toBeEmpty()
        ->and(Role::findByName(RoleName::AdminHq->value)->hasPermissionTo('agents.manage'))->toBeTrue()
        ->and(Role::findByName(RoleName::Finance->value)->hasPermissionTo('agents.view'))->toBeTrue()
        ->and(Role::findByName(RoleName::Operations->value)->hasPermissionTo('agents.view'))->toBeFalse();

    // An install from before Phase 12: existing roles without the module get its default on re-seed.
    Role::findByName(RoleName::Sales->value)->revokePermissionTo('agents.view');
    Permission::whereIn('name', ['agents.view', 'agents.manage'])->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Role::findByName(RoleName::Sales->value)->fresh()->hasPermissionTo('agents.view'))->toBeTrue();
});

it('registers an agent as a login user with role Ejen, a link slug and bank details', function () {
    $agent = makeAgent();

    expect($agent->user->isAgent())->toBeTrue()
        ->and($agent->slug)->toBe('aiman-zulkifli')
        ->and($agent->bank_account_no)->toBe('5623 5782 2681')
        ->and($agent->shareUrl())->toEndWith('/tempah/aiman-zulkifli')
        ->and(Activity::where('event', 'agent.created')->exists())->toBeTrue();

    // Agents are not listed or creatable in Pengguna & Peranan.
    $this->actingAs(superAdmin())->get('/pengguna')->assertOk()->assertDontSee('aiman@nadiqurban.com');
    $this->get('/pengurusan-ejen')->assertOk()->assertSee('AZ01')->assertSee('Aiman bin Zulkifli');
});

it('validates the agent form and keeps IDs and emails unique', function () {
    makeAgent();

    Livewire::actingAs(superAdmin())->test(AgentsIndex::class)
        ->call('create')
        ->set('code', 'az01')->set('name', '')->set('email', 'aiman@nadiqurban.com')->set('password', 'abc')
        ->set('bankAccountNo', 'abc')
        ->call('save')
        ->assertHasErrors(['code', 'name', 'email', 'password', 'bankAccountNo']);
});

it('logs agents in on Log Masuk Ejen and keeps them inside the portal', function () {
    $agent = makeAgent();

    Livewire::test(AgentLogin::class)
        ->set('email', 'aiman@nadiqurban.com')->set('password', 'EjenNq2027x')
        ->call('login')
        ->assertRedirect(route('agent.portal'));

    $this->actingAs($agent->user);
    $this->get('/ejen/portal')->assertOk()->assertSee('Assalamualaikum, Aiman')->assertSee('/tempah/aiman-zulkifli')
        ->assertDontSee('Pratonton');
    $this->get('/tempahan')->assertRedirect(route('agent.portal'));

    // Their own public sales link opens normally (not bounced to the portal) and is not counted.
    $this->get('/tempah/aiman-zulkifli')->assertOk()->assertSee('Mulakan Tempahan');
    $this->get('/tempah')->assertOk();
    $this->get('/jejak')->assertOk();
    expect(AgentClick::where('agent_id', $agent->id)->sum('clicks'))->toBe(0);
    $this->get('/')->assertRedirect(route('agent.portal'));
});

it('refuses staff and inactive agents on Log Masuk Ejen', function () {
    $staff = userWithRoles(RoleName::Sales);
    $staff->forceFill(['password' => 'StaffNq2027x'])->save();

    Livewire::test(AgentLogin::class)
        ->set('email', $staff->email)->set('password', 'StaffNq2027x')
        ->call('login')
        ->assertHasErrors('email');
    expect(auth()->check())->toBeFalse();

    $agent = makeAgent();
    Livewire::actingAs(superAdmin())->test(AgentsIndex::class)->call('toggleStatus', $agent->id);
    app('auth')->forgetGuards();

    Livewire::test(AgentLogin::class)
        ->set('email', 'aiman@nadiqurban.com')->set('password', 'EjenNq2027x')
        ->call('login')
        ->assertHasErrors('email');
});

it('blocks staff pages from the portal and the portal from staff', function () {
    $this->actingAs(userWithRoles(RoleName::Sales))->get('/ejen/portal')->assertForbidden();
    auth()->logout();
    $this->get('/ejen/portal')->assertRedirect(route('agent.login'));
});

it('takes an FPX booking through an agent link: CHIP paid → verified, commission counted', function () {
    $agent = makeAgent();

    book('aiman-zulkifli')->assertRedirect();

    $order = Order::query()->with('payment')->latest('id')->firstOrFail();
    expect($order->agent_id)->toBe($agent->id)
        ->and($order->source)->toBe('public')
        ->and($order->quantity)->toBe(2)
        ->and($order->commission_sen)->toBe(10000)                // RM 50 × 2
        ->and($order->status)->toBe(OrderStatus::AwaitingPayment)
        ->and($order->participants()->pluck('name')->all())->toBe(['Rosmawati binti Idris', 'Faizal bin Kassim']);

    // Back from CHIP (fake = paid): the receipt page reconciles and verifies automatically.
    $tx = PaymentGatewayTransaction::where('order_id', $order->id)->firstOrFail();
    $this->get(route('booking.receipt', ['token' => $order->tracking_token, 'tx' => $tx->reference]))
        ->assertOk()->assertSee('Bayaran Berjaya')->assertSee('RESIT BAYARAN')->assertSee($order->tracking_no);

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::InProgress)
        ->and($order->stage)->toBe(OrderStage::PaymentVerified)
        ->and($order->payment->status)->toBe(PaymentStatus::Verified)
        ->and(AgentClick::where('agent_id', $agent->id)->sum('clicks'))->toBe(1);

    $row = AgentStats::perAgent(collect([$agent]), new Period)->first();
    expect($row['sales_sen'])->toBe(700000)->and($row['commission_sen'])->toBe(10000);
});

it('sends a bank-transfer booking with proof to Pengesahan Bayaran; commission counts after HQ verifies', function () {
    $agent = makeAgent();

    book('aiman-zulkifli', 'pindahan_bank', UploadedFile::fake()->image('resit.jpg'))
        ->assertRedirect();

    $order = Order::query()->latest('id')->firstOrFail();
    expect($order->status)->toBe(OrderStatus::Accepted)
        ->and($order->payment->proof())->not->toBeNull();

    $this->actingAs(superAdmin())->get('/pengesahan-bayaran')->assertSee($order->order_no);
    expect(AgentStats::perAgent(collect([$agent]), new Period)->first()['commission_sen'])->toBe(0);

    app(VerifyPayment::class)->handle($order, superAdmin());
    expect(AgentStats::perAgent(collect([$agent]), new Period)->first()['commission_sen'])->toBe(10000);
});

it('requires lafaz akad and a proof for manual payment', function () {
    $product = Product::where('name', 'Qurban Lembu Uganda')->firstOrFail();
    $t = Livewire::test(Booking::class)->call('start')->call('pick', $product->id)->call('toStep', 2);
    foreach (bookingForm() as $k => $v) {
        $t->set($k, $v);
    }
    $t->call('toStep', 3)->set('payType', 'cek')->call('pay')->assertHasErrors(['akad', 'proof']);

    expect(Order::count())->toBe(0);
});

it('applies a promo code and books without an agent from /tempah', function () {
    $product = Product::where('name', 'Qurban Lembu Uganda')->firstOrFail();
    $t = Livewire::test(Booking::class)->call('start')->call('pick', $product->id)->call('toStep', 2);
    foreach (bookingForm() as $k => $v) {
        $t->set($k, $v);
    }
    $t->call('toStep', 3)
        ->set('promo', 'awalqurban')->call('applyPromo')
        ->assertSee('Kod AWALQURBAN digunakan')
        ->set('promo', 'TIADA')->call('applyPromo')->assertSee('Kod promosi tidak sah.')
        ->set('promo', 'AWALQURBAN')->call('applyPromo')
        ->set('akad', true)->call('pay');

    $order = Order::latest('id')->firstOrFail();
    expect($order->agent_id)->toBeNull()
        ->and($order->commission_sen)->toBe(0)
        ->and($order->discount_sen)->toBe(35000);
});

it('counts one link click per visitor per day and none for portal previews', function () {
    $agent = makeAgent();

    $this->get('/tempah/aiman-zulkifli')->assertOk()->assertSee('Mulakan Tempahan')->assertSee('Aiman bin Zulkifli');

    // The country is not shown on the public booking cards.
    Livewire::test(Booking::class)->call('start')
        ->assertSee('1 bahagian')->assertDontSee('Uganda &middot;', false)->assertDontSee('Negara Pelaksanaan');
    $this->get('/tempah/aiman-zulkifli')->assertOk();
    $this->get('/tempah/aiman-zulkifli?pratonton=1')->assertOk();
    // Old /e/ links still lead to the agent's booking page.
    $this->get('/e/aiman-zulkifli?pratonton=1')->assertRedirect('/tempah/aiman-zulkifli?pratonton=1');

    expect(AgentStats::clicks($agent, new Period))->toBe(1);
    $this->get('/tempah/tiada-ejen')->assertOk()->assertDontSee('Ejen anda');
});

it('shows the agent their orders by tab and only their own payment proofs', function () {
    $agent = makeAgent();
    $other = makeAgent('NS02', 'Nurul Syafiqah', 'syafiqah@nadiqurban.com');
    book('aiman-zulkifli', 'pindahan_bank', UploadedFile::fake()->image('resit.jpg'));
    $order = Order::latest('id')->firstOrFail();

    Livewire::actingAs($agent->user)->test(AgentPortal::class)
        ->assertSee($order->order_no)->assertSee('Menunggu Pengesahan')
        ->set('tab', 'confirmed')->assertDontSee($order->order_no);

    $this->actingAs($agent->user)->get(route('agent.proof', $order->payment))->assertOk();
    $this->actingAs($other->user)->get(route('agent.proof', $order->payment))->assertNotFound();
});

it('refuses to delete an agent with orders but deletes one without', function () {
    $agent = makeAgent();
    $spare = makeAgent('NS02', 'Nurul Syafiqah', 'syafiqah@nadiqurban.com');
    book('aiman-zulkifli', 'cek', UploadedFile::fake()->image('cek.jpg'));

    Livewire::actingAs(superAdmin())->test(AgentsIndex::class)
        ->call('delete', $agent->id)->assertHasErrors('agent')
        ->call('delete', $spare->id)->assertHasNoErrors();

    expect(Agent::find($agent->id))->not->toBeNull()
        ->and(User::where('email', 'syafiqah@nadiqurban.com')->exists())->toBeFalse();
});

it('exports the agents to Excel and renders the commission invoice PDF', function () {
    Excel::fake();
    Pdf::fake();
    makeAgent();

    Livewire::actingAs(superAdmin())->test(AgentsIndex::class)->call('exportExcel');
    Excel::assertDownloaded('Komisen-Ejen-Semua.xlsx');

    $this->actingAs(superAdmin())->get(route('agents.invoice', ['tempoh' => 'month', 'tarikh' => '2027-06-01']))->assertOk();
    Pdf::assertRespondedWithPdf(fn ($pdf) => $pdf->viewName === 'pdf.agent-commission' && $pdf->viewData['number'] === 'INV-KOM-2027-06');
});

it('lets only agents.manage roles change agents', function () {
    $agent = makeAgent();

    Livewire::actingAs(userWithRoles(RoleName::Finance))->test(AgentsIndex::class)
        ->assertDontSee('Tambah Ejen')
        ->call('toggleStatus', $agent->id)->assertForbidden();

    $this->actingAs(userWithRoles(RoleName::Operations))->get('/pengurusan-ejen')->assertForbidden();
});
