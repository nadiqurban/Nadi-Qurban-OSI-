<?php

namespace App\Actions\Users;

use App\Enums\RoleName;
use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    /**
     * Super Admin permanently deletes a user. Records they created keep existing
     * (every users FK is nullOnDelete); roles, sessions and notifications go with them.
     */
    public function handle(User $user, User $actor): void
    {
        if (! $actor->isSuperAdmin()) {
            throw new AuthorizationException('Hanya Super Admin boleh memadam pengguna.');
        }

        if ($user->is($actor)) {
            throw ValidationException::withMessages(['delete' => 'Anda tidak boleh memadam akaun anda sendiri.']);
        }

        if ($user->isSuperAdmin() && User::role(RoleName::SuperAdmin->value)->count() <= 1) {
            throw ValidationException::withMessages(['delete' => 'Sekurang-kurangnya seorang Super Admin diperlukan.']);
        }

        DB::transaction(function () use ($user, $actor) {
            Audit::log('user.deleted', "Pengguna {$user->name} ({$user->email}) dipadam", $user, Severity::Critical, [
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
            ], $actor, 'rbac');

            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            $user->notifications()->delete();
            $user->delete();
        });

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }
}
