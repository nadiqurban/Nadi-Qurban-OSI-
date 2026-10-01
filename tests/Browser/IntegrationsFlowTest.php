<?php

use App\Models\ApiClient;
use App\Models\Order;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class]);
    $this->admin = User::where('email', 'nurfitri@nadiqurban.com')->firstOrFail();
});

it('shows Integrasi API and creates a key', function () {
    $client = ApiClient::query()->create(['name' => 'Nadi Qurban Production', 'environment' => 'live', 'prefix' => 'nq_live_88f3a1']);
    $client->createToken('live', ['read']);
    $this->actingAs($this->admin);

    visit('/tetapan/integrasi')
        ->resize(...DESKTOP)
        ->assertSee('Integrasi API')
        ->assertSee('Nadi Qurban Production')
        ->assertSee('Gerbang Pembayaran')
        ->screenshot(fullPage: true, filename: 'integrations')
        ->click('#btn-new-key')
        ->wait(0.4)
        ->type('#key-name', 'Sistem ERP')
        ->click('#btn-create-key')
        ->wait(0.8)
        ->assertSee('tidak akan dipaparkan lagi')
        ->assertNoJavaScriptErrors();
});

it('shows Webhooks with endpoints and delivery log', function () {
    $endpoint = WebhookEndpoint::query()->create(['url' => 'https://erp.nadiqurban.com/hooks', 'description' => 'ERP', 'events' => array_keys(WebhookEndpoint::EVENTS)]);
    WebhookDelivery::query()->create(['uuid' => (string) Str::uuid(), 'webhook_endpoint_id' => $endpoint->id, 'event' => 'payment.confirmed', 'http_status' => 200, 'status' => 'berjaya']);
    $this->actingAs($this->admin);

    visit('/tetapan/webhooks')
        ->resize(...DESKTOP)
        ->assertSee('Endpoint Berdaftar')
        ->assertSee('payment.confirmed')
        ->assertSee('Signing Secret')
        ->screenshot(fullPage: true, filename: 'webhooks')
        ->assertNoJavaScriptErrors();
});

it('opens the command palette with Ctrl+K and navigates by keyboard', function () {
    $order = Order::query()->latest('id')->firstOrFail();
    $this->actingAs($this->admin);

    visit('/dashboard')
        ->resize(...DESKTOP)
        ->keys('#dash-date', 'Control+k')
        ->wait(0.4)
        ->type('#global-search', $order->order_no)
        ->wait(1)
        ->assertSee($order->customer->name)
        ->screenshot(filename: 'command-palette')
        ->keys('#global-search', 'Enter')
        ->wait(1.5)
        ->assertPathIs('/tempahan/'.$order->id)
        ->assertNoJavaScriptErrors();
});

it('fits phones', function (string $path) {
    $this->actingAs($this->admin);

    visit($path)
        ->resize(...PHONE)
        ->assertScript('document.documentElement.scrollWidth <= window.innerWidth', true)
        ->assertNoJavaScriptErrors();
})->with(['/tetapan/integrasi', '/tetapan/webhooks']);
