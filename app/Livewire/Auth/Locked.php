<?php

namespace App\Livewire\Auth;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::auth')]
#[Title('Akaun Dikunci')]
class Locked extends Component
{
    public int $secondsLeft = 0;

    public function mount(): void
    {
        $until = session('login.locked_until');
        $this->secondsLeft = $until ? max(0, (int) $until - now()->timestamp) : 0;
    }

    public function render(): mixed
    {
        return view('livewire.auth.locked');
    }
}
