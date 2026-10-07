<?php

use App\Enums\OrderStage;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Document;
use App\Models\InstallmentPlan;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Navigation;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
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
