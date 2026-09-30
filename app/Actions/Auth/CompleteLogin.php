<?php

namespace App\Actions\Auth;

use App\Enums\LoginStatus;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\Auth;

class CompleteLogin
{
    public function __construct(
        private readonly RecordLoginAttempt $record,
    ) {}

    public function handle(User $user): void
    {
        // No "remember me" cookie: sessions must expire after 30 idle minutes (PRD §2).
        Auth::login($user);
        session()->regenerate();
        session()->forget('login.2fa_user');

        $user->forceFill(['last_seen_at' => now()])->save();

        $this->record->handle($user, $user->email, LoginStatus::Success);
        Audit::log('login', 'Log masuk ke sistem', $user, causer: $user, logName: 'auth');
    }
}
