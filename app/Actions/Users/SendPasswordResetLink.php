<?php

namespace App\Actions\Users;

use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLink
{
    /** Admin-triggered reset ("Set semula kata laluan"). Returns the broker status. */
    public function handle(User $user, User $actor): string
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        Audit::log('user.reset_link', "Pautan set semula kata laluan dihantar kepada {$user->name}", $user, Severity::Warning, causer: $actor, logName: 'rbac');

        return $status;
    }
}
