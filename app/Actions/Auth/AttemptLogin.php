<?php

namespace App\Actions\Auth;

use App\Enums\LoginStatus;
use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Password login with per-account lockout (5 failures → 15 min, PRD §6.1)
 * and a per-IP throttle against credential stuffing.
 */
class AttemptLogin
{
    public const IP_MAX_ATTEMPTS = 20;

    public function __construct(
        private readonly RecordLoginAttempt $record,
        private readonly CompleteLogin $complete,
    ) {}

    public function handle(string $email, string $password): LoginResult
    {
        $email = mb_strtolower(trim($email));
        $ipKey = 'login-ip:'.request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, self::IP_MAX_ATTEMPTS)) {
            return LoginResult::Throttled;
        }

        RateLimiter::hit($ipKey, 60);

        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            // Same work as a real check so timing does not reveal which emails exist.
            Hash::check($password, self::dummyHash());
            $this->record->handle(null, $email, LoginStatus::Failed);

            return LoginResult::Invalid;
        }

        if ($user->isLocked()) {
            $this->record->handle($user, $email, LoginStatus::Locked);

            return LoginResult::Locked;
        }

        if (! Hash::check($password, $user->password)) {
            return $this->registerFailure($user, $email);
        }

        if ($user->isSuspended()) {
            $this->record->handle($user, $email, LoginStatus::Failed);
            Audit::log('login.suspended', 'Cubaan log masuk akaun digantung', $user, Severity::Warning, causer: $user, logName: 'auth');

            return LoginResult::Suspended;
        }

        $user->forceFill(['failed_login_attempts' => 0, 'locked_until' => null])->save();
        RateLimiter::clear($ipKey);

        if ($user->hasTwoFactorEnabled()) {
            session()->put('login.2fa_user', $user->id);

            return LoginResult::TwoFactorRequired;
        }

        $this->complete->handle($user);

        return LoginResult::Success;
    }

    private static function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('nq-timing-guard');
    }

    private function registerFailure(User $user, string $email): LoginResult
    {
        $attempts = $user->failed_login_attempts + 1;

        if ($attempts >= User::MAX_LOGIN_ATTEMPTS) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => now()->addMinutes(User::LOCKOUT_MINUTES),
            ])->save();

            $this->record->handle($user, $email, LoginStatus::Locked);
            Audit::log('login.locked', 'Akaun dikunci selepas '.User::MAX_LOGIN_ATTEMPTS.' percubaan gagal', $user, Severity::Critical, causer: $user, logName: 'auth');

            return LoginResult::Locked;
        }

        $user->forceFill(['failed_login_attempts' => $attempts])->save();
        $this->record->handle($user, $email, LoginStatus::Failed);
        Audit::log('login.failed', 'Log masuk gagal', $user, Severity::Warning, ['attempt' => $attempts], $user, 'auth');

        return LoginResult::Invalid;
    }
}
