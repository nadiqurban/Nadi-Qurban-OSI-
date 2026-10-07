<?php

use App\Enums\AccessLevel;
use App\Enums\Module;
use App\Enums\RoleName;
use App\Livewire\Settings\Company;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\Role;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('seeds seven roles and the design permission matrix', function () {
    expect(Role::count())->toBe(7);

    $sales = Role::findByName(RoleName::Sales->value);
    expect($sales->hasPermissionTo('crm.manage'))->toBeTrue()
        ->and($sales->hasPermissionTo('orders.manage'))->toBeTrue()
        ->and($sales->hasPermissionTo('dashboard.view'))->toBeTrue()
        ->and($sales->hasPermissionTo('dashboard.manage'))->toBeFalse()
        ->and($sales->hasPermissionTo('finance.view'))->toBeFalse();

    $vendor = Role::findByName(RoleName::VendorPic->value);
    expect($vendor->hasPermissionTo('execution.manage'))->toBeTrue()
        ->and($vendor->hasPermissionTo('dashboard.view'))->toBeFalse();
});

it('returns 403 for modules the role cannot view', function () {
    $sales = userWithRoles(RoleName::Sales);

    $this->actingAs($sales)->get('/kewangan')->assertForbidden();
    $this->actingAs($sales)->get('/pengguna')->assertForbidden();
    $this->actingAs($sales)->get('/tetapan/syarikat')->assertForbidden();
    $this->actingAs($sales)->get('/crm')->assertOk();
    $this->actingAs($sales)->get('/tempahan')->assertOk();
});

it('hides sidebar modules the user cannot view', function () {
    $this->actingAs(userWithRoles(RoleName::Sales))->get('/crm')
        ->assertSee('Sales CRM')
        ->assertDontSee('>Kewangan<', false)
        ->assertDontSee('Audit Log');
});

it('combines permissions from multiple roles', function () {
    $user = userWithRoles(RoleName::Sales, RoleName::Finance);

    $this->actingAs($user)->get('/kewangan')->assertOk();
    $this->actingAs($user)->get('/crm')->assertOk();
    $this->actingAs($user)->get('/dashboard')->assertSee('Kewangan')->assertSee('Sales CRM');
});

it('sends each role to its first allowed module', function () {
    $this->actingAs(userWithRoles(RoleName::AdminHq))->get('/')->assertRedirect(route('dashboard'));
    $this->actingAs(userWithRoles(RoleName::VendorPic))->get('/')->assertRedirect(route('akad.index'));
});

it('enforces manage permission on every Livewire action, not just the buttons', function () {
    // Admin HQ may view Pengguna (Lihat) but not manage it.
    $hq = userWithRoles(RoleName::AdminHq);

    Livewire::actingAs($hq)->test(UsersIndex::class)
        ->assertOk()
        ->assertDontSeeHtml('wire:click="create"')
        ->call('create')->assertForbidden();

    Livewire::actingAs($hq)->test(UsersIndex::class)->call('saveMatrix')->assertForbidden();
    Livewire::actingAs($hq)->test(Company::class)->call('save')->assertForbidden();
});

it('lets a Super Admin edit the matrix and logs it as Kritikal', function () {
    $admin = superAdmin();
    $sales = Role::findByName(RoleName::Sales->value);

    $component = Livewire::actingAs($admin)->test(UsersIndex::class, ['tab' => 'peranan']);
    expect($component->get('matrix')[$sales->id][Module::Finance->value])->toBe('N');

    // Tiada → Penuh
    $component->call('cycle', $sales->id, Module::Finance->value)
        ->call('saveMatrix')
        ->assertSet('matrixSaved', true);

    expect($sales->fresh()->hasPermissionTo('finance.manage'))->toBeTrue();

    $log = Activity::where('event', 'role.permissions')->latest('id')->first();
    expect($log->severity)->toBe('kritikal')
        ->and($log->properties['changes']['finance'])->toBe(['from' => 'Tiada', 'to' => 'Penuh']);

    $this->actingAs(userWithRoles(RoleName::Sales))->get('/kewangan')->assertOk();
});

it('keeps the Super Admin column fixed at Penuh', function () {
    $admin = superAdmin();
    $role = Role::findByName(RoleName::SuperAdmin->value);

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('cycle', $role->id, Module::Users->value)
        ->assertSet("matrix.{$role->id}.users", AccessLevel::Full->value);
});
