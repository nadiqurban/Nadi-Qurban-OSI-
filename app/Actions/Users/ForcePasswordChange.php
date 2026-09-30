<?php

namespace App\Actions\Users;

use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;

class ForcePasswordChange
{
    public function handle(User $user, User $actor): void
    {
        $user->forceFill(['must_change_password' => true])->save();

        Audit::log('user.force_password', "{$user->name} dipaksa menukar kata laluan", $user, Severity::Warning, causer: $actor, logName: 'rbac');
    }
}
