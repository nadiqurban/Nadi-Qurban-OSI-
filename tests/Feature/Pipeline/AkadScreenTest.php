<?php

use App\Livewire\Akad\Index;
use App\Models\Order;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class]);
});

it('opens the akad modal', function () {
    $this->actingAs(superAdmin());
    $order = Order::where('order_no', 'NQ-QB-LE-001252')->firstOrFail();

    Livewire::test(Index::class)
        ->call('openAkad', $order->id)
        ->assertSee('Mohon Peserta Mengikuti Bacaan Lafaz Akad');
});
