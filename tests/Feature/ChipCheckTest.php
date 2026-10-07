<?php

use App\Services\Chip\ChipClient;
use App\Services\Chip\ChipGateway;
use App\Support\Settings;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Http;

it('reports the CHIP connection without printing keys', function () {
    $this->seed(SettingsSeeder::class);
    $settings = app(Settings::class);
    $settings->set('chip.brand_id', 'brand-123');
    $settings->set('chip.secret_key', 'sk_live_secret_value_123456', encrypted: true);
    app()->instance(ChipGateway::class, new ChipClient($settings));

    Http::fake([
        '*/public_key/' => Http::response('"-----BEGIN PUBLIC KEY-----"', 200),
        '*/payment_methods/*' => Http::response(['available_payment_methods' => ['fpx', 'visa']], 200),
    ]);

    $this->artisan('chip:check')
        ->expectsOutputToContain('CHIP sebenar')
        ->expectsOutputToContain('Secret Key: DITERIMA (200)')
        ->expectsOutputToContain('Brand ID  : DITERIMA (200) · kaedah: fpx, visa')
        ->doesntExpectOutputToContain('sk_live_secret_value')
        ->assertSuccessful();
});
