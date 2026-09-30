<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Sejarah Log Masuk')]
class LoginHistory extends Component
{
    public function render(): mixed
    {
        /** @var User $user */
        $user = auth()->user();

        return view('livewire.auth.login-history', [
            'history' => $user->loginHistories()->limit(10)->get(),
        ]);
    }
}
