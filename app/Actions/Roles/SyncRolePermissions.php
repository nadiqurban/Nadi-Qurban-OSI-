<?php

namespace App\Actions\Roles;

use App\Enums\AccessLevel;
use App\Enums\Module;
use App\Models\Role;

/**
 * Translate matrix levels (Penuh/Lihat/Tiada) into "{module}.view" / "{module}.manage".
 */
class SyncRolePermissions
{
    /**
     * @param  array<string, AccessLevel>  $levels  module value => level
     * @return array<string, array{from: string, to: string}> changed modules
     */
    public function handle(Role $role, array $levels): array
    {
        if ($role->isLockedMatrix()) {
            $levels = collect(Module::cases())->mapWithKeys(fn (Module $m) => [$m->value => AccessLevel::Full])->all();
        }

        $before = self::levelsFor($role);
        $permissions = [];

        foreach (Module::cases() as $module) {
            $level = $levels[$module->value] ?? $before[$module->value];

            if ($level !== AccessLevel::None) {
                $permissions[] = $module->viewPermission();
            }

            if ($level === AccessLevel::Full) {
                $permissions[] = $module->managePermission();
            }
        }

        $role->syncPermissions($permissions);

        $after = self::levelsFor($role->fresh() ?? $role);

        return collect($after)
            ->filter(fn (AccessLevel $level, string $module) => $before[$module] !== $level)
            ->map(fn (AccessLevel $level, string $module) => ['from' => $before[$module]->shortLabel(), 'to' => $level->shortLabel()])
            ->all();
    }

    /**
     * Current level per module for a role.
     *
     * @return array<string, AccessLevel>
     */
    public static function levelsFor(Role $role): array
    {
        $names = $role->permissions->pluck('name')->flip();

        return collect(Module::cases())
            ->mapWithKeys(fn (Module $m) => [$m->value => match (true) {
                $names->has($m->managePermission()) => AccessLevel::Full,
                $names->has($m->viewPermission()) => AccessLevel::View,
                default => AccessLevel::None,
            }])
            ->all();
    }
}
