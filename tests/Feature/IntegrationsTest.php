<?php

use App\Enums\OrderStage;
use App\Enums\RoleName;
use App\Events\OrderStageChanged;
use App\Livewire\GlobalSearch;
use App\Livewire\Settings\Integrations;
use App\Livewire\Settings\Webhooks;
use App\Models\ApiClient;
use App\Models\ApiRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Support\PaymentGateways;
use App\Support\Settings;
use App\Support\Webhooks as WebhookService;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\WebhookServer\CallWebhookJob;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class]);
    $this->admin = superAdmin();
});

function apiToken(array $abilities = ['read', 'write']): array
{
    $client = ApiClient::query()->create(['name' => 'ERP', 'environment' => 'live', 'prefix' => 'nq_x']);

    return [$client, $client->createToken('live', $abilities)->plainTextToken];
}

// ---------------------------------------------------------------- API v1

it('rejects requests without a valid key', function () {
    $this->getJson('/api/v1/products')->assertUnauthorized();
    $this->withToken('nq_999|nope')->getJson('/api/v1/products')->assertUnauthorized();
});

it('lists products and reads orders by order or tracking number', function () {
    [, $token] = apiToken(['read']);
    $order = Order::query()->with('customer')->firstOrFail();

    $this->withToken($token)->getJson('/api/v1/products')->assertOk()
        ->assertJsonPath('data.0.price.formatted', fn ($v) => str_starts_with($v, 'RM '));

    $this->withToken($token)->getJson('/api/v1/orders/'.$order->order_no)->assertOk()
        ->assertJsonPath('data.order_no', $order->order_no)
        ->assertJsonPath('data.customer.name', $order->customer->name);

    $this->withToken($token)->getJson('/api/v1/orders/'.strtolower($order->tracking_no).'/status')->assertOk()
        ->assertJsonPath('data.stage.code', $order->stage->value);

    $this->withToken($token)->getJson('/api/v1/orders/NQ-XX-XX-000000')->assertNotFound();
    expect(ApiRequest::query()->count())->toBe(4);
});

it('creates orders only with the write ability and validates input', function () {
    [, $readOnly] = apiToken(['read']);
    [, $token] = apiToken(['read', 'write']);
    $product = Product::query()->where('is_active', true)->where('stock', '>', 5)->firstOrFail();
    $body = ['customer' => ['name' => 'Pelanggan API', 'phone' => '0123456789', 'state' => 'Selangor'], 'product_id' => $product->id, 'quantity' => 1, 'payment_method' => 'fpx', 'participants' => ['Pelanggan API']];

    $this->withToken($readOnly)->postJson('/api/v1/orders', $body)->assertForbidden();
    app('auth')->forgetGuards();
    $this->withToken($token)->postJson('/api/v1/orders', ['product_id' => $product->id])->assertUnprocessable()
        ->assertJsonValidationErrors(['customer.name', 'customer.phone', 'quantity', 'payment_method']);

    $res = $this->withToken($token)->postJson('/api/v1/orders', $body)->assertCreated();
    expect(Order::query()->where('order_no', $res->json('data.order_no'))->exists())->toBeTrue()
        ->and($res->json('data.participants'))->toBe(['Pelanggan API']);
});

it('rate-limits each key to 120 requests per minute and blocks revoked keys', function () {
    [$client, $token] = apiToken(['read']);

    $this->withToken($token)->getJson('/api/v1/products')->assertOk()->assertHeader('X-RateLimit-Limit', '120');

    $client->update(['revoked_at' => now()]);
    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/products')->assertUnauthorized();
});

// ------------------------------------------------------- Integrasi page

it('creates an API key shown once and revokes it', function () {
    $component = Livewire::actingAs($this->admin)->test(Integrations::class)
        ->assertSee('Integrasi API')
        ->assertSee('Gerbang Pembayaran')
        ->call('openKey')
        ->set('keyName', 'Sistem ERP')
        ->set('keyAbilities', ['read', 'write'])
        ->call('createKey')
        ->assertHasNoErrors();

    $token = $component->get('newToken');
    expect($token)->toContain('|nq_');
    $client = ApiClient::query()->where('name', 'Sistem ERP')->firstOrFail();
    expect($client->tokens()->first()->abilities)->toBe(['read', 'write'])
        ->and($client->prefix)->toBe('nq_live_'.mb_substr(explode('|nq_', $token)[1], 0, 6));

    $this->withToken($token)->getJson('/api/v1/products')->assertOk();

    Livewire::actingAs($this->admin)->test(Integrations::class)->call('revoke', $client->id);
    expect($client->fresh()->revoked_at)->not->toBeNull()->and($client->tokens()->count())->toBe(0);
});

it('saves gateway credentials encrypted and switches gateways on/off', function () {
    Livewire::actingAs($this->admin)->test(Integrations::class)
        ->call('openGateway', 'toyyibpay')
        ->set('secret', 'tp-secret-1234567890')
        ->set('form.category_code', '7hk2mabc')
        ->set('form.env', 'sandbox')
        ->call('saveGateway')
        ->assertHasNoErrors()
        ->call('openGateway', 'billplz')
        ->call('saveGateway')
        ->assertHasErrors(['secret', 'form.collection_id']);

    $raw = DB::table('settings')->where('key', 'toyyibpay.secret_key')->value('value');
    expect($raw)->not->toContain('tp-secret')
        ->and(app(Settings::class)->get('toyyibpay.secret_key'))->toBe('tp-secret-1234567890')
        ->and(app(PaymentGateways::class)->status('toyyibpay'))->toBe(['Aktif', 'success'])
        ->and(app(PaymentGateways::class)->endpoint('toyyibpay'))->toBe('https://dev.toyyibpay.com');

    Livewire::actingAs($this->admin)->test(Integrations::class)->call('toggleGateway', 'toyyibpay');
    expect(app(PaymentGateways::class)->status('toyyibpay')[0])->toBe('Tidak Aktif');

    Livewire::actingAs(userWithRoles(RoleName::Finance))->test(Integrations::class)->call('openKey')->assertForbidden();
});

// --------------------------------------------------------------- Webhooks

it('manages endpoints and signs outgoing events', function () {
    Queue::fake();

    Livewire::actingAs($this->admin)->test(Webhooks::class)
        ->assertSee('Signing Secret')
        ->call('openForm')
        ->set('url', 'https://erp.contoh.com/hooks')
        ->set('events', ['payment.confirmed'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('https://erp.contoh.com/hooks');

    $order = Order::query()->firstOrFail();
    event(new OrderStageChanged($order, OrderStage::Received, OrderStage::PaymentVerified));
    event(new OrderStageChanged($order, OrderStage::ReportVerified, OrderStage::AwbGenerated)); // not subscribed

    $secret = app(WebhookService::class)->secret();
    Queue::assertPushed(CallWebhookJob::class, 1);
    Queue::assertPushed(CallWebhookJob::class, function (CallWebhookJob $job) use ($secret, $order) {
        return $job->webhookUrl === 'https://erp.contoh.com/hooks'
            && $job->payload['event'] === 'payment.confirmed'
            && $job->payload['data']['order']['order_no'] === $order->order_no
            && $job->headers['X-NQ-Signature'] === hash_hmac('sha256', json_encode($job->payload), $secret);
    });

    $delivery = WebhookDelivery::query()->firstOrFail();
    expect($delivery->status)->toBe(WebhookDelivery::PENDING)->and($delivery->event)->toBe('payment.confirmed');
});

it('records delivery results and respects disabled events', function () {
    Queue::fake();
    $endpoint = WebhookEndpoint::query()->create(['url' => 'https://a.test/h', 'events' => array_keys(WebhookEndpoint::EVENTS)]);
    $service = app(WebhookService::class);

    $service->dispatch('order.completed', ['x' => 1]);
    $ok = WebhookDelivery::query()->latest('id')->firstOrFail();
    event(new WebhookCallSucceededEvent('post', $endpoint->url, [], [], ['delivery' => $ok->uuid], [], 1, new Response(200), null, null, $ok->uuid, null));
    expect($ok->fresh()->status)->toBe(WebhookDelivery::SUCCESS)->and($ok->fresh()->http_status)->toBe(200);

    $service->dispatch('report.verified', ['x' => 1]);
    $bad = WebhookDelivery::query()->latest('id')->firstOrFail();
    event(new FinalWebhookCallFailedEvent('post', $endpoint->url, [], [], ['delivery' => $bad->uuid], [], 3, new Response(500), null, 'Server error', $bad->uuid, null));
    expect($bad->fresh()->status)->toBe(WebhookDelivery::FAILED)->and($bad->fresh()->attempt)->toBe(3);

    Livewire::actingAs($this->admin)->test(Webhooks::class)->call('toggleGlobalEvent', 'awb.generated')->assertSee('500');
    expect($service->dispatch('awb.generated', []))->toBe(0);
});

// ----------------------------------------------------------- ⌘K search

it('searches across modules within the user permissions', function () {
    $order = Order::query()->with('customer')->firstOrFail();

    Livewire::actingAs($this->admin)->test(GlobalSearch::class)
        ->set('q', $order->order_no)
        ->assertSee($order->order_no)
        ->set('q', 'Uganda Charity')
        ->assertSee('Vendor')
        ->set('q', 'x')
        ->assertSee('Mula menaip');

    Livewire::actingAs(userWithRoles(RoleName::Sales))->test(GlobalSearch::class) // no vendors.view
        ->set('q', 'Uganda Charity')
        ->assertDontSee('VND-');
});
