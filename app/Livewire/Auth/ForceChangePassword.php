<?php

namespace App\Livewire\Auth;

use App\Actions\Account\ChangePassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Tukar Kata Laluan')]
class ForceChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function change(ChangePassword $change): mixed
    {
        /** @var User $user */
        $user = auth()->user();

        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::defaults()],
        ], [
            'password.different' => 'Kata laluan baharu mesti berbeza daripada kata laluan semasa.',
        ], ['current_password' => 'kata laluan semasa', 'password' => 'kata laluan baharu']);

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Kata laluan semasa tidak betul.');

            return null;
        }

        $change->handle($user, $this->password);

        return $this->redirectIntended(route('home'));
    }

    public function render(): mixed
    {
        return view('livewire.auth.force-change-password');
    }
}
