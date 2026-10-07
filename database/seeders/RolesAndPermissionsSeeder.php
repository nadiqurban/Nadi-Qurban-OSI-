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
 * Production-safe: creates the module permissions and the fixed roles.
 * The default matrix (design + Operasi column) is applied only when a role is
 * first created, so edits made in "Matriks Kebenaran" are never overwritten.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(SyncRolePermissions $sync): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $existing = Permission::query()->pluck('name')->all();

        foreach (Module::allPermissions() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Modules added after install (e.g. Pengurusan Ejen): give existing roles their default level.
        $newModules = collect(Module::cases())
            ->filter(fn (Module $m) => $existing !== [] && ! in_array($m->viewPermission(), $existing, true))
            ->map->value->all();

        foreach (RoleName::cases() as $index => $roleName) {
            $role = Role::query()->firstOrNew(['name' => $roleName->value, 'guard_name' => 'web']);
            $isNew = ! $role->exists;

            $role->forceFill([
                'description' => $role->description ?? $roleName->description(),
                'icon' => $roleName->icon(),
                'tone' => $roleName->tone(),
                'sort' => $index + 1,
            ])->save();

            if ($isNew || $role->isLockedMatrix()) {
                $sync->handle($role, $roleName->defaultMatrix());
            } elseif ($newModules !== []) {
                $role->load('permissions');
                $sync->handle($role, array_intersect_key($roleName->defaultMatrix(), array_flip($newModules)));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
