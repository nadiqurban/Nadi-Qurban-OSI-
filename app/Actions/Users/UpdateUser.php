<?php

namespace App\Actions\Users;

use App\Enums\RoleName;
use App\Enums\Severity;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUser
{
    public function __construct(private readonly SetUserStatus $setStatus) {}

    /**
     * @param  array{name: string, email: string}  $data
     * @param  list<string>  $roles
     */
    public function handle(User $user, array $data, array $roles, UserStatus $status, User $actor): void
    {
        $this->guardSuperAdmin($user, $roles, $actor);

        DB::transaction(function () use ($user, $data, $roles, $status, $actor) {
            $user->fill(['name' => $data['name'], 'email' => mb_strtolower($data['email'])])->save();

            $before = $user->roles->pluck('name')->sort()->values()->all();
            $user->syncRoles($roles);
            $after = collect($roles)->sort()->values()->all();

            if ($before !== $after) {
                Audit::log('user.roles', "Peranan {$user->name} ditukar", $user, Severity::Critical, ['from' => $before, 'to' => $after], $actor, 'rbac');
            } else {
                Audit::log('user.updated', "Maklumat pengguna {$user->name} dikemaskini", $user, Severity::Info, [], $actor, 'rbac');
            }

            if ($status !== $user->status) {
                $this->setStatus->handle($user, $status, $actor);
            }
        });
    }

    /**
     * Nobody may remove their own Super Admin role, and the last Super Admin must stay.
     *
     * @param  list<string>  $roles
     */
    private function guardSuperAdmin(User $user, array $roles, User $actor): void
    {
        $losing = $user->isSuperAdmin() && ! in_array(RoleName::SuperAdmin->value, $roles, true);

        if (! $losing) {
            return;
        }

        if ($user->is($actor)) {
            throw ValidationException::withMessages(['roles' => 'Anda tidak boleh membuang peranan Super Admin anda sendiri.']);
        }

        if (User::role(RoleName::SuperAdmin->value)->count() <= 1) {
            throw ValidationException::withMessages(['roles' => 'Sekurang-kurangnya seorang Super Admin diperlukan.']);
        }
    }
}
