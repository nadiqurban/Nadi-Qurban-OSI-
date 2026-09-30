<?php

namespace App\Livewire\Auth;

use App\Actions\Account\ChangePassword;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Tetapkan Kata Laluan Baharu')]
class ResetPassword extends Component
{
    #[Locked]
    public string $token = '';

    #[Locked]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $done = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(ChangePassword $change): void
    {
        $this->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], attributes: ['password' => 'kata laluan']);

        $status = Password::reset(
            ['email' => $this->email, 'token' => $this->token, 'password' => $this->password, 'password_confirmation' => $this->password_confirmation],
            function (User $user, string $password) use ($change) {
                $change->handle($user, $password, 'password.reset');
                $user->forceFill(['remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('password', __($status));

            return;
        }

        $this->reset('password', 'password_confirmation');
        $this->done = true;
    }

    public function render(): mixed
    {
        return view('livewire.auth.reset-password');
    }
}
