<?php

namespace App\Actions\Users;

use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class SetUserPassword
{
    /**
     * Super Admin sets another user's password directly. The user can log in with it
     * straight away (no forced change); their other sessions are ended.
     */
    public function handle(User $user, string $password, User $actor): void
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Hanya Super Admin boleh menetapkan kata laluan pengguna.');
        }

        DB::transaction(function () use ($user, $password, $actor) {
            $user->forceFill([
                'password' => $password,
                'must_change_password' => false,
                'password_changed_at' => now(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->where('id', '!=', session()->getId())
                ->delete();

            Audit::log('user.password_set', "Kata laluan {$user->name} ditetapkan oleh Super Admin", $user, Severity::Critical, [], $actor, 'auth');
        });
    }
}
