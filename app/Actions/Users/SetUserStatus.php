<?php

namespace App\Actions\Users;

use App\Enums\Severity;
use App\Enums\UserStatus;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetUserStatus
{
    public function handle(User $user, UserStatus $status, User $actor): void
    {
        if ($status === UserStatus::Suspended && $user->is($actor)) {
            throw ValidationException::withMessages(['status' => 'Anda tidak boleh menggantung akaun anda sendiri.']);
        }

        $user->forceFill([
            'status' => $status,
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ])->save();

        if ($status === UserStatus::Suspended) {
            // End every active session of the suspended user immediately.
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        Audit::log(
            $status === UserStatus::Suspended ? 'user.suspended' : 'user.activated',
            $status === UserStatus::Suspended ? "Akaun {$user->name} digantung" : "Akaun {$user->name} diaktifkan semula",
            $user,
            Severity::Critical,
            causer: $actor,
            logName: 'rbac',
        );
    }
}
