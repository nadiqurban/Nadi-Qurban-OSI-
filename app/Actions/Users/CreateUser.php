<?php

namespace App\Actions\Users;

use App\Enums\Severity;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    /**
     * New users get a temporary password and must change it on first login.
     *
     * @param  array{name: string, email: string, phone?: string|null}  $data
     * @param  list<string>  $roles
     */
    public function handle(array $data, array $roles, string $temporaryPassword, User $actor): User
    {
        return DB::transaction(function () use ($data, $roles, $temporaryPassword, $actor) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'password' => $temporaryPassword,
                'status' => UserStatus::Active,
                'must_change_password' => true,
            ]);

            $user->syncRoles($roles);

            Audit::log('user.created', "Pengguna {$user->name} dicipta", $user, Severity::Warning, ['roles' => $roles], $actor, 'rbac');

            return $user;
        });
    }
}
