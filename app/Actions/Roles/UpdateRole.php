<?php

namespace App\Actions\Roles;

use App\Enums\AccessLevel;
use App\Enums\Severity;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class UpdateRole
{
    public function __construct(private readonly SyncRolePermissions $sync) {}

    /**
     * @param  array<string, string>  $levels  module => F|V|N
     */
    public function handle(Role $role, string $name, ?string $description, array $levels, User $actor): void
    {
        DB::transaction(function () use ($role, $name, $description, $levels, $actor) {
            $original = $role->only(['name', 'description']);

            // Fixed system roles keep their name (code relies on it); only the description changes.
            $role->forceFill([
                'name' => $role->roleName() ? $role->name : $name,
                'description' => $description,
            ])->save();

            $diff = $this->sync->handle($role, collect($levels)->map(fn (string $c) => AccessLevel::tryFrom($c) ?? AccessLevel::None)->all());

            Audit::log('role.updated', "Peranan {$role->name} dikemaskini", $role, Severity::Critical, [
                'before' => $original,
                'after' => $role->only(['name', 'description']),
                'changes' => $diff,
            ], $actor, 'rbac');
        });
    }
}
