<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\AttemptLogin;
use App\Actions\Auth\LoginResult;
use App\Models\User;
use App\Support\LandingUrl;
use Illuminate\Support\Facades\Cookie;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Log Masuk')]
class Login extends Component
{
    public const REMEMBER_COOKIE = 'nq_login_email';

    #[Validate('required|email|max:255', as: 'emel')]
    public string $email = '';

    #[Validate('required|string|max:255', as: 'kata laluan')]
    public string $password = '';

    /** "Ingat saya" remembers the email only — sessions still end after 30 idle minutes. */
    public bool $remember = true;

    public function mount(): void
    {
        $this->email = (string) request()->cookie(self::REMEMBER_COOKIE, '');
    }

    public function login(AttemptLogin $attempt): mixed
    {
        $this->validate();

        $result = $attempt->handle($this->email, $this->password);
        $this->reset('password');

        Cookie::queue($this->remember
            ? Cookie::make(self::REMEMBER_COOKIE, mb_strtolower($this->email), 60 * 24 * 90)
            : Cookie::forget(self::REMEMBER_COOKIE));

        return match ($result) {
            LoginResult::Success => $this->redirect(LandingUrl::after($this->user()), navigate: false),
            LoginResult::TwoFactorRequired => $this->redirectRoute('two-factor.challenge'),
            LoginResult::Locked => $this->toLocked(),
            LoginResult::Suspended => $this->addError('email', 'Akaun anda telah digantung. Sila hubungi pentadbir sistem.'),
            LoginResult::Throttled => $this->addError('email', 'Terlalu banyak cubaan log masuk. Sila cuba sebentar lagi.'),
            LoginResult::Invalid => $this->addError('email', 'Emel atau kata laluan tidak sah.'),
        };
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    private function toLocked(): mixed
    {
        $user = User::query()->where('email', mb_strtolower($this->email))->first();
        session()->put('login.locked_until', $user?->locked_until?->timestamp);

        return $this->redirectRoute('login.locked');
    }

    public function render(): mixed
    {
        return view('livewire.auth.login');
    }
}
