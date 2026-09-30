<?php

namespace App\Livewire\Settings;

use App\Actions\Account\ChangePassword;
use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
#[Title('Profil & Akaun')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    public ?string $saved = null;

    public function mount(): void
    {
        $user = $this->user();
        $this->name = $user->name;
        $this->phone = (string) $user->phone;
        $this->email = $user->email;
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048']], attributes: ['photo' => 'foto']);

        $user = $this->user();
        $old = $user->avatar_path;
        $path = $this->photo->store('avatars', 'public');

        $user->forceFill(['avatar_path' => $path])->save();

        if ($old) {
            Storage::disk('public')->delete($old);
        }

        $this->photo = null;
        $this->saved = 'Foto profil dikemaskini.';
    }

    public function save(ChangePassword $change): void
    {
        $user = $this->user();

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        $changingPassword = $this->password !== '' || $this->current_password !== '';

        if ($changingPassword) {
            $rules['current_password'] = ['required', 'string'];
            $rules['password'] = ['required', 'confirmed', PasswordRule::defaults()];
        }

        $this->validate($rules, attributes: [
            'name' => 'nama penuh', 'phone' => 'no. telefon', 'email' => 'emel',
            'current_password' => 'kata laluan semasa', 'password' => 'kata laluan baharu',
        ]);

        if ($changingPassword && ! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Kata laluan semasa tidak betul.');

            return;
        }

        $user->fill([
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'email' => mb_strtolower($this->email),
        ]);

        if ($user->isDirty()) {
            $changes = array_keys($user->getDirty());
            $user->save();
            Audit::log('profile.updated', 'Profil dikemaskini', $user, Severity::Info, ['fields' => $changes], $user, 'auth');
        }

        if ($changingPassword) {
            $change->handle($user, $this->password);
        }

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->saved = 'Perubahan disimpan.';
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        return view('livewire.settings.profile', ['user' => $this->user()]);
    }
}
