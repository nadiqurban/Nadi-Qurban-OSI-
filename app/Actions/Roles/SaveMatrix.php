<?php

namespace App\Actions\Roles;

use App\Enums\AccessLevel;
use App\Enums\Severity;
use App\Models\Role;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class SaveMatrix
{
    public function __construct(private readonly SyncRolePermissions $sync) {}

    /**
     * @param  array<int|string, array<string, string>>  $matrix  role id => [module => F|V|N]
     * @return int number of changed cells
     */
    public function handle(array $matrix, User $actor): int
    {
        return DB::transaction(function () use ($matrix, $actor) {
            $changed = 0;

            foreach (Role::query()->with('permissions')->whereIn('id', array_keys($matrix))->get() as $role) {
                $levels = collect($matrix[$role->id])
                    ->map(fn (string $code) => AccessLevel::tryFrom($code) ?? AccessLevel::None)
                    ->all();

                $diff = $this->sync->handle($role, $levels);

                if ($diff !== []) {
                    $changed += count($diff);
                    Audit::log('role.permissions', "Kebenaran peranan {$role->name} dikemaskini", $role, Severity::Critical, ['changes' => $diff], $actor, 'rbac');
                }
            }

            return $changed;
        });
    }
}
