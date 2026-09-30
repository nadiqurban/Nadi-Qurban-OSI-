<?php

namespace App\Actions\Account;

use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;

class ChangePassword
{
    /** Sets a new password, clears the forced-change flag and ends the user's other sessions. */
    public function handle(User $user, string $password, string $event = 'password.changed'): void
    {
        $user->forceFill([
            'password' => $password,
            'must_change_password' => false,
            'password_changed_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $current = session()->getId();
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $current)
            ->delete();

        Audit::log($event, 'Kata laluan ditukar', $user, Severity::Warning, causer: $user, logName: 'auth');
    }
}
