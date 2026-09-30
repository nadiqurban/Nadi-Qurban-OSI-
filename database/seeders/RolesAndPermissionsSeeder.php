<?php

namespace Database\Seeders;

use App\Actions\Roles\SyncRolePermissions;
use App\Enums\Module;
use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Production-safe: creates the 46 module permissions and the six fixed roles.
 * The default matrix (design + Operasi column) is applied only when a role is
 * first created, so edits made in "Matriks Kebenaran" are never overwritten.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(SyncRolePermissions $sync): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Module::allPermissions() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (RoleName::cases() as $index => $roleName) {
            $role = Role::query()->firstOrNew(['name' => $roleName->value, 'guard_name' => 'web']);
            $isNew = ! $role->exists;

            $role->forceFill([
                'description' => $role->description ?? $roleName->description(),
                'icon' => $roleName->icon(),
                'tone' => $roleName->tone(),
                'sort' => $index + 1,
            ])->save();

            if ($isNew || $roleName === RoleName::SuperAdmin) {
                $sync->handle($role, $roleName->defaultMatrix());
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
