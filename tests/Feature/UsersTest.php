<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Livewire\Users\Index as UsersIndex;
use App\Livewire\Users\RoleShow;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

it('renders the users list with stats and role tags', function () {
    $admin = superAdmin();
    userWithRoles(RoleName::Finance)->update(['name' => 'Fatimah Noor']);

    $this->actingAs($admin)->get('/pengguna')
        ->assertOk()
        ->assertSee('Pengguna &amp; Peranan', false)
        ->assertSee('Jumlah Pengguna')
        ->assertSee('Fatimah Noor')
        ->assertSee('Kewangan');
});

it('filters users by search and role', function () {
    $admin = superAdmin();
    userWithRoles(RoleName::Sales)->update(['name' => 'Rahim Salleh']);
    userWithRoles(RoleName::Finance)->update(['name' => 'Fatimah Noor']);

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->set('search', 'rahim')->assertSee('Rahim Salleh')->assertDontSee('Fatimah Noor')
        ->set('search', '')->set('roleFilter', 'Kewangan')->assertSee('Fatimah Noor')->assertDontSee('Rahim Salleh');
});

it('creates a user with several roles and a temporary password', function () {
    $admin = superAdmin();

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('create')
        ->assertSet('showForm', true)
        ->set('name', 'Nur Hidayah')
        ->set('email', 'hidayah@nadiqurban.com')
        ->call('toggleRole', 'Sales')
        ->call('toggleRole', 'Kewangan')
        ->set('tempPassword', 'Sementara9!')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showForm', false);

    $user = User::where('email', 'hidayah@nadiqurban.com')->firstOrFail();
    expect($user->hasAllRoles(['Sales', 'Kewangan']))->toBeTrue()
        ->and($user->must_change_password)->toBeTrue()
        ->and(Hash::check('Sementara9!', $user->password))->toBeTrue();
});

it('validates the user form in Malay', function () {
    Livewire::actingAs(superAdmin())->test(UsersIndex::class)
        ->call('create')
        ->set('tempPassword', '')
        ->call('save')
        ->assertHasErrors(['name', 'email', 'roles', 'tempPassword'])
        ->assertSee('Pilih sekurang-kurangnya satu peranan.');
});

it('changes roles and logs a Kritikal entry', function () {
    $admin = superAdmin();
    $user = userWithRoles(RoleName::Sales);

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('edit', $user->id)
        ->assertSet('roles', ['Sales'])
        ->call('toggleRole', 'Sales')
        ->call('toggleRole', 'Operasi')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->getRoleNames()->all())->toBe(['Operasi'])
        ->and(Activity::where('event', 'user.roles')->value('severity'))->toBe('kritikal');
});

it('suspends and reactivates a user from Akses & Keselamatan', function () {
    $admin = superAdmin();
    $user = userWithRoles(RoleName::Sales);

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openAccess', $user->id)
        ->call('toggleSuspend')
        ->assertSee('telah digantung');

    expect($user->fresh()->status)->toBe(UserStatus::Suspended)
        ->and(Activity::where('event', 'user.suspended')->value('severity'))->toBe('kritikal');

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openAccess', $user->id)
        ->call('toggleSuspend');

    expect($user->fresh()->status)->toBe(UserStatus::Active);
});

it('cannot suspend yourself or drop your own Super Admin role', function () {
    $admin = superAdmin();

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openAccess', $admin->id)
        ->call('toggleSuspend')
        ->assertHasErrors('status');

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('edit', $admin->id)
        ->call('toggleRole', 'Super Admin')
        ->call('toggleRole', 'Sales')
        ->call('save')
        ->assertHasErrors('roles');

    expect($admin->fresh()->isSuperAdmin())->toBeTrue();
});

it('sends a reset link and forces a password change', function () {
    Notification::fake();
    $admin = superAdmin();
    $user = userWithRoles(RoleName::Sales);

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openAccess', $user->id)
        ->call('sendReset')
        ->call('forceChange');

    Notification::assertSentTo($user, ResetPassword::class);
    expect($user->fresh()->must_change_password)->toBeTrue();
});

it('shows role detail and updates role permissions', function () {
    $admin = superAdmin();
    $role = Role::findByName(RoleName::Operations->value);
    $member = userWithRoles(RoleName::Operations);

    $this->actingAs($admin)->get(route('users.role', $role))
        ->assertOk()->assertSee('Operasi')->assertSee($member->email)->assertSee('Kebenaran Modul');

    Livewire::actingAs($admin)->test(RoleShow::class, ['role' => $role])
        ->call('openEdit')
        ->set('description', 'Urus logistik & pelaksanaan.')
        ->call('cycle', 'crm') // Tiada → Penuh
        ->call('save')
        ->assertHasNoErrors();

    $role->refresh();
    expect($role->description)->toBe('Urus logistik & pelaksanaan.')
        ->and($role->hasPermissionTo('crm.manage'))->toBeTrue()
        ->and($role->name)->toBe('Operasi'); // fixed role names cannot be renamed
});
