<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Lupa Kata Laluan')]
class ForgotPassword extends Component
{
    #[Validate('required|email|max:255', as: 'emel')]
    public string $email = '';

    public bool $sent = false;

    public function send(): void
    {
        $this->validate();

        $key = 'forgot:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('email', 'Terlalu banyak permintaan. Sila cuba sebentar lagi.');

            return;
        }

        RateLimiter::hit($key, 300);

        // Same response whether or not the email exists (no account enumeration).
        Password::sendResetLink(['email' => mb_strtolower($this->email)]);

        $this->sent = true;
    }

    public function render(): mixed
    {
        return view('livewire.auth.forgot-password');
    }
}
