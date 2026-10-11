<?php

namespace App\Livewire\Public;

use App\Actions\Agents\RegisterAgent;
use App\Models\Agent;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Pendaftaran Ejen (Pendaftaran Ejen.dc.html): public sign-up for the agent programme.
 * The account waits for HQ approval (Pengurusan Ejen › Pendaftaran Baharu).
 */
#[Layout('layouts::booking')]
#[Title('Pendaftaran Ejen')]
class AgentRegister extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $gender = 'Lelaki';

    public string $birthDate = '';

    public string $district = '';

    public string $state = 'Selangor';

    public string $bankName = 'Maybank';

    public string $bankAccountName = '';

    public string $bankAccountNo = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $agreed = false;

    /** @var TemporaryUploadedFile|null */
    public $photo = null;

    public ?string $doneName = null;

    public function updatedPhoto(): void
    {
        $this->resetErrorBag('photo');
        $this->validateOnly('photo', ['photo' => ['image', 'mimes:jpeg,jpg,png', 'max:10240']], [], ['photo' => 'gambar']);
    }

    public function submit(RegisterAgent $register): void
    {
        $this->name = trim($this->name);
        $this->email = mb_strtolower(trim($this->email));
        $this->phone = trim($this->phone);

        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'gender' => ['required', Rule::in(Agent::GENDERS)],
            'birthDate' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'district' => ['nullable', 'string', 'max:80'],
            'state' => ['required', Rule::in(Agent::STATES)],
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:10240'],
            'bankName' => ['required', Rule::in(Agent::BANKS)],
            'bankAccountName' => ['nullable', 'string', 'max:120'],
            'bankAccountNo' => ['nullable', 'regex:/^[0-9 \-]{6,30}$/'],
            'password' => ['required', 'max:64', PasswordRule::defaults()],
            'passwordConfirmation' => ['required', 'same:password'],
            'agreed' => ['accepted'],
        ], [
            'email.unique' => 'Emel ini sudah berdaftar.',
            'phone.regex' => 'Format no. telefon tidak sah.',
            'photo.required' => 'Sila muat naik gambar terkini.',
            'passwordConfirmation.same' => 'Kata laluan tidak sepadan.',
            'agreed.accepted' => 'Sila tandakan pengesahan terma & syarat.',
            'bankAccountNo.regex' => 'Nombor akaun hanya nombor (6–30 digit).',
        ], [
            'name' => 'nama penuh', 'email' => 'emel', 'phone' => 'no. telefon', 'gender' => 'jantina', 'birthDate' => 'tarikh lahir',
            'district' => 'daerah', 'state' => 'negeri', 'photo' => 'gambar', 'bankName' => 'bank', 'bankAccountName' => 'nama akaun',
            'bankAccountNo' => 'nombor akaun', 'password' => 'kata laluan', 'passwordConfirmation' => 'sahkan kata laluan',
        ]);

        $key = 'daftar-ejen:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('form', 'Terlalu banyak pendaftaran dari peranti ini. Sila cuba sebentar lagi.');

            return;
        }

        RateLimiter::hit($key, 3600);

        $agent = $register->handle([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => trim($this->password),
            'gender' => $this->gender,
            'birth_date' => $this->birthDate ?: null,
            'district' => trim($this->district) ?: null,
            'state' => $this->state,
            'bank_name' => $this->bankName,
            'bank_account_name' => trim($this->bankAccountName) ?: null,
            'bank_account_no' => trim($this->bankAccountNo) ?: null,
        ], $this->photo);

        $this->doneName = $agent->user->name;
        $this->reset('password', 'passwordConfirmation', 'photo');
    }

    public function render(): mixed
    {
        return view('livewire.public.agent-register');
    }
}
