<?php

use App\Enums\RoleName;
use App\Livewire\Akad\Index as AkadIndex;
use App\Livewire\Allocation\Index as AllocationIndex;
use App\Livewire\Documents\Index as DocumentsIndex;
use App\Livewire\Orders\Completed;
use App\Livewire\Settings\Webhooks;
use App\Livewire\Shipping\Index as ShippingIndex;
use App\Models\Allocation;
use App\Models\Document;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WebhookEndpoint;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\DemoFinanceSeeder;
use Database\Seeders\DemoOrderSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DemoVendorSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->seed([SettingsSeeder::class, MasterDataSeeder::class, DemoUserSeeder::class, DemoVendorSeeder::class, DemoCatalogSeeder::class, DemoOrderSeeder::class, DemoFinanceSeeder::class]);

    // A PIC for a vendor that has no allocated orders: must see nothing customer-related.
    $this->vendor = Vendor::query()->whereNotIn('id', Allocation::query()->select('vendor_id'))->firstOrFail();
    $this->pic = userWithRoles(RoleName::VendorPic);
    $this->pic->forceFill(['vendor_id' => $this->vendor->id])->save();
    $this->foreign = Order::query()->with('customer')->whereHas('allocation')->firstOrFail();
});

it('stops a vendor PIC from opening another vendor\'s customer data (IDOR)', function () {
    Livewire::actingAs($this->pic)->test(AkadIndex::class)->call('openDetail', $this->foreign->id)->assertNotFound();

    Livewire::actingAs($this->pic)->test(AllocationIndex::class)
        ->call('openDetail', $this->foreign->id)
        ->assertDontSee($this->foreign->customer->phone);
});

it('scopes shipping, completed orders and order PDFs to the PIC\'s own vendor', function () {
    Livewire::actingAs($this->pic)->test(ShippingIndex::class)->assertDontSee($this->foreign->order_no);
    Livewire::actingAs($this->pic)->test(Completed::class)->assertDontSee($this->foreign->order_no);

    $this->actingAs($this->pic)->get(route('orders.participants.pdf', ['ids' => [$this->foreign->id]]))->assertNotFound();
    expect(Order::query()->count())->toBe(0);   // global scope while the PIC is signed in
});

it('hides HQ documents from vendor PICs', function () {
    $doc = Document::query()->where('category', 'invois')->firstOrFail();

    Livewire::actingAs($this->pic)->test(DocumentsIndex::class)->assertDontSee($doc->name);
    $this->actingAs($this->pic)->get(route('documents.open', $doc))->assertNotFound();
});

it('keeps staff access unchanged', function () {
    $admin = User::query()->where('email', 'nurfitri@nadiqurban.com')->firstOrFail();

    Livewire::actingAs($admin)->test(AkadIndex::class)->call('openDetail', $this->foreign->id)->assertSee($this->foreign->customer->name);
    expect(Order::query()->count())->toBeGreaterThan(0);
});

it('sends baseline security headers', function () {
    $this->get('/login')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('refuses webhook endpoints that point at internal addresses (SSRF)', function () {
    $admin = superAdmin();

    foreach (['http://127.0.0.1/hook', 'http://169.254.169.254/latest/meta-data', 'http://10.0.0.5/x', 'http://localhost:8080/x'] as $url) {
        Livewire::actingAs($admin)->test(Webhooks::class)
            ->call('openForm')->set('url', $url)->set('events', ['payment.confirmed'])->call('save')
            ->assertHasErrors('url');
    }

    expect(WebhookEndpoint::query()->count())->toBe(0);
});
