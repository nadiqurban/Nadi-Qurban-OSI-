<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\CompleteLogin;
use App\Actions\Auth\RecordLoginAttempt;
use App\Enums\LoginStatus;
use App\Models\User;
use App\Support\LandingUrl;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

#[Layout('layouts::auth')]
#[Title('Pengesahan Dua Langkah')]
class TwoFactorChallenge extends Component
{
    public string $code = '';

    public function mount(): mixed
    {
        if (! session('login.2fa_user')) {
            return $this->redirectRoute('login');
        }

        return null;
    }

    public function verify(Google2FA $google2fa, CompleteLogin $complete, RecordLoginAttempt $record): mixed
    {
        $this->validate(['code' => ['required', 'digits:6']], attributes: ['code' => 'kod']);

        $user = User::query()->find(session('login.2fa_user'));

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return $this->redirectRoute('login');
        }

        $key = '2fa:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('code', 'Terlalu banyak cubaan. Sila log masuk semula.');
            session()->forget('login.2fa_user');

            return null;
        }

        if (! $google2fa->verifyKey((string) $user->two_factor_secret, $this->code)) {
            RateLimiter::hit($key, 300);
            $record->handle($user, $user->email, LoginStatus::Failed);
            $this->addError('code', 'Kod pengesahan tidak sah.');

            return null;
        }

        RateLimiter::clear($key);
        $complete->handle($user);

        return $this->redirect(LandingUrl::after($user));
    }

    public function render(): mixed
    {
        return view('livewire.auth.two-factor-challenge');
    }
}
