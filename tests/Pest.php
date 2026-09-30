<?php

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => test()->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature', 'Browser');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Viewports used by the verification loop (CLAUDE.md)
|--------------------------------------------------------------------------
*/

const DESKTOP = [1440, 900];
const PHONE = [390, 844];

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function userWithRoles(RoleName ...$roles): User
{
    $user = User::factory()->create();
    $user->syncRoles(array_map(fn (RoleName $r) => $r->value, $roles));

    return $user->fresh() ?? $user;
}

function superAdmin(): User
{
    return userWithRoles(RoleName::SuperAdmin);
}
