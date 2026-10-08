<?php

use App\Enums\RoleName;
use App\Livewire\Products\Index as ProductsIndex;
use App\Livewire\Promo\Index as PromoIndex;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\Sequence;
use Database\Seeders\DemoCatalogSeeder;
use Database\Seeders\MasterDataSeeder;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([MasterDataSeeder::class, DemoCatalogSeeder::class]);
});

it('seeds master data and the design catalogue', function () {
    expect(Country::count())->toBe(14)
        ->and(Package::orderBy('sort')->pluck('code')->all())->toBe(['DEL', 'ZAM', 'TOP', 'NIL', 'MUT'])
        ->and(Product::count())->toBe(12)
        ->and(PromoCode::count())->toBe(6)
        ->and(Product::where('name', 'Qurban Lembu Uganda')->value('code'))->toBe('QB-LE-DEL');
});

it('shows product cards with price, stock colour and code', function () {
    $this->actingAs(userWithRoles(RoleName::Sales))->get('/produk')
        ->assertOk()
        ->assertSee('Qurban Lembu Uganda')
        ->assertSee('QB-LE-DEL')
        ->assertSee('RM 3,500')
        ->assertSee('48 unit')
        ->assertSee('Jumlah Produk');
});

it('filters products by service tab', function () {
    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)
        ->set('service', 'dam')
        ->assertSee('Dam Kambing Makkah')
        ->assertDontSee('QB-LE-DEL');
});

it('creates a product with country and auto code, logged to audit', function () {
    $admin = superAdmin();
    $uganda = Country::where('name', 'Uganda')->value('id');
    $topaz = Package::where('code', 'TOP')->value('id');

    Livewire::actingAs($admin)->test(ProductsIndex::class)
        ->call('create')
        ->set('name', 'Qurban Lembu Uganda Premium')
        ->set('serviceField', 'qurban')
        ->set('animal', 'lembu')
        ->set('packageId', $topaz)
        ->assertSee('QB-LE-TOP')
        ->set('countryId', $uganda)
        ->set('price', '3,800')
        ->set('stock', '25')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $p = Product::where('name', 'Qurban Lembu Uganda Premium')->firstOrFail();
    expect($p->code)->toBe('QB-LE-TOP')
        ->and($p->price_sen)->toBe(380000)
        ->and($p->country_id)->toBe($uganda)
        ->and(Activity::where('event', 'product.created')->exists())->toBeTrue();
});

it('suffixes the code when service/animal/package repeat in another country', function () {
    $delima = Package::where('code', 'DEL')->value('id');

    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)
        ->call('create')
        ->set('name', 'Qurban Lembu Chad')
        ->set('packageId', $delima)
        ->set('countryId', Country::where('name', 'Chad')->value('id'))
        ->set('price', '3300')
        ->set('stock', '5')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Qurban Lembu Chad')->value('code'))->toBe('QB-LE-DEL-2');
});

it('validates the product form in Malay', function () {
    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)
        ->call('create')
        ->set('price', 'abc')
        ->set('stock', '-1')
        ->call('save')
        ->assertHasErrors(['name', 'countryId', 'price', 'stock'])
        ->assertSee('Harga mesti jumlah RM yang sah');
});

it('edits a price and logs it as Amaran', function () {
    $p = Product::where('name', 'Aqiqah Kambing Malaysia')->firstOrFail();

    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)
        ->call('edit', $p->id)
        ->assertSet('price', '850')
        ->set('price', '900')
        ->call('save')
        ->assertHasNoErrors();

    expect($p->fresh()->price_sen)->toBe(90000)
        ->and(Activity::where('event', 'product.updated')->value('severity'))->toBe('amaran');
});

it('soft deletes a product', function () {
    $p = Product::firstOrFail();

    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)->call('delete', $p->id);

    expect(Product::find($p->id))->toBeNull()
        ->and(Product::withTrashed()->find($p->id))->not->toBeNull();
});

it('enforces product permissions per role', function () {
    // Kewangan: Tiada → 403. Operasi: Lihat → can view, cannot manage.
    $this->actingAs(userWithRoles(RoleName::Finance))->get('/produk')->assertForbidden();

    $ops = userWithRoles(RoleName::Operations);
    $this->actingAs($ops)->get('/produk')->assertOk()->assertDontSeeHtml('wire:click="create"');
    Livewire::actingAs($ops)->test(ProductsIndex::class)->call('create')->assertForbidden();
    Livewire::actingAs($ops)->test(ProductsIndex::class)->call('delete', Product::first()->id)->assertForbidden();

    // Sales: Penuh
    Livewire::actingAs(userWithRoles(RoleName::Sales))->test(ProductsIndex::class)->call('create')->assertOk();
});

it('lists active and ended promo codes in their tabs', function () {
    $this->actingAs(userWithRoles(RoleName::Sales))->get('/kod-promosi')
        ->assertOk()
        ->assertSee('AWALQURBAN')
        ->assertSee('RM 50')
        ->assertSee('31 Mac 2027')
        ->assertDontSee('EARLYBIRD26');

    Livewire::actingAs(superAdmin())->test(PromoIndex::class)
        ->set('tab', 'tamat')
        ->assertSee('EARLYBIRD26')
        ->assertSee('STAFFNQ')
        ->assertDontSee('AWALQURBAN');
});

it('moves exhausted and expired codes to Tamat Tempoh', function () {
    PromoCode::where('code', 'GROUP7')->update(['used_count' => 400]); // limit 400
    PromoCode::where('code', 'AQIQAH15')->update(['expires_at' => now()->subDay()]);

    expect(PromoCode::usable()->pluck('code')->sort()->values()->all())->toBe(['AWALQURBAN', 'RAYA50'])
        ->and(PromoCode::ended()->count())->toBe(4);
});

it('creates percent and fixed codes with normalised uppercase codes', function () {
    $admin = superAdmin();

    Livewire::actingAs($admin)->test(PromoIndex::class)
        ->call('create')
        ->set('code', 'qurban 2027!')
        ->set('description', 'Diskaun awal musim')
        ->set('type', 'peratus')
        ->set('value', '12')
        ->set('usageLimit', '100')
        ->set('expiresAt', '2027-06-30')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)->test(PromoIndex::class)
        ->call('create')
        ->set('code', 'KORBAN75')
        ->set('type', 'tetap')
        ->set('value', '75')
        ->call('save')
        ->assertHasNoErrors();

    $percent = PromoCode::where('code', 'QURBAN2027')->firstOrFail();
    $fixed = PromoCode::where('code', 'KORBAN75')->firstOrFail();

    expect($percent->discountFor(350000))->toBe(42000)
        ->and($fixed->value)->toBe(7500)
        ->and($fixed->discountFor(350000))->toBe(7500);
});

it('auto-generates NQ codes and rejects duplicates and bad values', function () {
    $component = Livewire::actingAs(superAdmin())->test(PromoIndex::class)->call('create')->call('autoCode');
    expect($component->get('code'))->toMatch('/^NQ[A-Z2-9]{6}$/');

    Livewire::actingAs(superAdmin())->test(PromoIndex::class)
        ->call('create')
        ->set('code', 'RAYA50')
        ->set('type', 'peratus')
        ->set('value', '150')
        ->call('save')
        ->assertHasErrors(['code', 'value']);
});

it('enforces promo permissions', function () {
    $this->actingAs(userWithRoles(RoleName::Finance))->get('/kod-promosi')->assertForbidden();
    Livewire::actingAs(userWithRoles(RoleName::AdminHq))->test(PromoIndex::class)->call('create')->assertOk();
    Livewire::actingAs(userWithRoles(RoleName::Sales))->test(PromoIndex::class)
        ->call('delete', PromoCode::first()->id)->assertOk();
});

it('numbers customers CUST-10240, CUST-10241 …', function () {
    $a = Customer::create(['name' => 'Ahmad Zaki bin Hassan', 'phone' => '012-3456789']);
    $b = Customer::create(['name' => 'Nurul Aina binti Rahim', 'phone' => '013-9988776']);

    expect($a->code)->toBe('CUST-10240')->and($b->code)->toBe('CUST-10241')
        ->and(Customer::normalisePhone('012-345 6789'))->toBe('0123456789')
        ->and(Sequence::next('test', 5))->toBe(5)
        ->and(Sequence::next('test', 5))->toBe(6);
});

it('stores the agent commission per product and shows it on the card', function () {
    $admin = superAdmin();
    $p = Product::where('name', 'Qurban Lembu Uganda')->firstOrFail();

    Livewire::actingAs($admin)->test(ProductsIndex::class)
        ->assertSee('Komisen')
        ->call('edit', $p->id)
        ->assertSet('commission', '50')
        ->set('commission', '75')
        ->call('save')
        ->assertHasNoErrors();

    expect($p->fresh()->commission_sen)->toBe(7500);

    $this->actingAs($admin)->get('/produk')->assertSee('RM 75');
});

it('rejects a commission that is invalid or not below the price', function () {
    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)
        ->call('edit', Product::where('name', 'Aqiqah Kambing Malaysia')->value('id'))
        ->set('commission', 'abc')->call('save')->assertHasErrors('commission')
        ->set('commission', '850')->call('save')->assertHasErrors('commission')
        ->set('commission', '')->call('save')->assertHasNoErrors();

    expect(Product::where('name', 'Aqiqah Kambing Malaysia')->value('commission_sen'))->toBe(0);
});

it('sets Jenis Kuantiti (Sebahagian / Ekor) on a product and uses it as the sold unit', function () {
    $uganda = Country::where('name', 'Uganda')->value('id');

    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)
        ->call('create')
        ->assertSet('unit', 'bahagian')
        ->assertSee('Jenis Kuantiti')
        ->set('animal', 'kambing')
        ->assertSet('unit', 'ekor')
        ->set('animal', 'lembu')
        ->set('unit', 'ekor')
        ->set('name', 'Lembu Seekor Uganda')
        ->set('countryId', $uganda)
        ->set('price', '3500')
        ->set('stock', '3')
        ->call('save')
        ->assertHasNoErrors();

    $p = Product::where('name', 'Lembu Seekor Uganda')->firstOrFail();
    expect($p->unit)->toBe('ekor')
        ->and($p->unitLabel())->toBe('1 ekor');

    Livewire::actingAs(superAdmin())->test(ProductsIndex::class)
        ->call('edit', $p->id)->assertSet('unit', 'ekor')
        ->set('unit', 'sekotak')->call('save')->assertHasErrors('unit');

    expect(Product::where('name', 'Qurban Kambing Indonesia')->first()?->unit ?? 'ekor')->toBe('ekor');
});

it('picks the product icon from the animal and Jenis Kuantiti', function () {
    $make = fn (string $animal, string $unit) => new Product(['animal' => $animal, 'unit' => $unit]);

    expect($make('lembu', 'bahagian')->iconClass())->toBe('nq-mask-lembu-bahagian')
        ->and($make('lembu', 'bahagian')->iconLabel())->toBe('Sebahagian Lembu')
        ->and($make('lembu', 'ekor')->iconClass())->toBe('nq-mask-lembu')
        ->and($make('kambing', 'ekor')->iconClass())->toBe('nq-mask-kambing')
        ->and($make('unta', 'bahagian')->iconClass())->toBe('nq-mask-unta');
});
