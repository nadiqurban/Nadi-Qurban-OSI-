<?php

namespace App\Livewire\Agent;

use App\Actions\Auth\AttemptLogin;
use App\Actions\Auth\LoginResult;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Log Masuk Ejen (Portal Ejen.dc.html login view). Same security as staff login
 * (lockout after 5 tries, per-IP throttle, 30-min sessions); staff accounts are refused.
 */
#[Layout('layouts::agent')]
#[Title('Log Masuk Ejen')]
class Login extends Component
{
    #[Validate('required|email|max:255', as: 'emel')]
    public string $email = '';

    #[Validate('required|string|max:255', as: 'kata laluan')]
    public string $password = '';

    public function login(AttemptLogin $attempt): mixed
    {
        $this->validate();

        $result = $attempt->handle($this->email, $this->password);
        $this->reset('password');

        if ($result === LoginResult::Success && ! $this->user()->isAgent()) {
            Auth::guard('web')->logout();
            session()->regenerate();
            $this->addError('email', 'Akaun ini bukan akaun ejen. Staf sila log masuk di halaman utama.');

            return null;
        }

        return match ($result) {
            LoginResult::Success => $this->redirectRoute('agent.portal'),
            LoginResult::TwoFactorRequired => $this->redirectRoute('two-factor.challenge'),
            LoginResult::Locked => $this->addError('email', 'Akaun dikunci selepas 5 percubaan gagal. Cuba semula selepas 15 minit.'),
            LoginResult::Suspended => $this->addError('email', 'Akaun ejen anda tidak aktif. Sila hubungi pihak HQ.'),
            LoginResult::Throttled => $this->addError('email', 'Terlalu banyak cubaan log masuk. Sila cuba sebentar lagi.'),
            LoginResult::Invalid => $this->addError('email', 'Emel atau kata laluan tidak sah.'),
        };
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        return view('livewire.agent.login');
    }
}
