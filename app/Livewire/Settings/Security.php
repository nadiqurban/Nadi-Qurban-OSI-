<?php

namespace App\Livewire\Settings;

use App\Enums\Severity;
use App\Models\User;
use App\Support\Audit;
use App\Support\UserAgent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Keselamatan: optional TOTP 2FA, active sessions, login history.
 */
#[Layout('layouts::app')]
#[Title('Keselamatan')]
class Security extends Component
{
    /** Secret shown while enabling 2FA (confirmed before it is saved). */
    public ?string $pendingSecret = null;

    public string $code = '';

    public string $password = '';

    public string $sessionPassword = '';

    public ?string $message = null;

    public function startTwoFactor(Google2FA $google2fa): void
    {
        $this->pendingSecret = $google2fa->generateSecretKey(32);
        $this->reset('code');
        $this->resetValidation();
    }

    public function cancelTwoFactor(): void
    {
        $this->reset('pendingSecret', 'code');
    }

    public function confirmTwoFactor(Google2FA $google2fa): void
    {
        $this->validate(['code' => ['required', 'digits:6']], attributes: ['code' => 'kod']);

        if (! $this->pendingSecret || ! $google2fa->verifyKey($this->pendingSecret, $this->code)) {
            $this->addError('code', 'Kod pengesahan tidak sah. Cuba lagi.');

            return;
        }

        $user = $this->user();
        $user->forceFill(['two_factor_secret' => $this->pendingSecret, 'two_factor_confirmed_at' => now()])->save();
        Audit::log('2fa.enabled', 'Pengesahan dua langkah diaktifkan', $user, Severity::Warning, causer: $user, logName: 'auth');

        $this->reset('pendingSecret', 'code');
        $this->message = 'Pengesahan dua langkah (2FA) telah diaktifkan.';
    }

    public function disableTwoFactor(): void
    {
        $user = $this->user();

        $this->validate(['password' => ['required', 'string']], attributes: ['password' => 'kata laluan']);

        if (! Hash::check($this->password, $user->password)) {
            $this->addError('password', 'Kata laluan tidak betul.');

            return;
        }

        $user->forceFill(['two_factor_secret' => null, 'two_factor_confirmed_at' => null])->save();
        Audit::log('2fa.disabled', 'Pengesahan dua langkah dinyahaktifkan', $user, Severity::Critical, causer: $user, logName: 'auth');

        $this->reset('password');
        $this->message = 'Pengesahan dua langkah telah dinyahaktifkan.';
    }

    public function logoutOtherSessions(): void
    {
        $user = $this->user();

        $this->validate(['sessionPassword' => ['required', 'string']], attributes: ['sessionPassword' => 'kata laluan']);

        if (! Hash::check($this->sessionPassword, $user->password)) {
            $this->addError('sessionPassword', 'Kata laluan tidak betul.');

            return;
        }

        $count = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', session()->getId())
            ->delete();

        Audit::log('sessions.revoked', "Log keluar {$count} sesi lain", $user, Severity::Warning, causer: $user, logName: 'auth');

        $this->reset('sessionPassword');
        $this->message = $count > 0 ? "{$count} sesi lain telah dilog keluar." : 'Tiada sesi lain yang aktif.';
    }

    /** @return Collection<int, array{device: string, ip: string|null, last: Carbon, current: bool, mobile: bool}> */
    private function sessions(): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) {
                $ua = UserAgent::parse($s->user_agent);

                return [
                    'device' => collect([$ua->browser(), $ua->platform()])->filter()->implode(' · ') ?: 'Peranti tidak dikenali',
                    'ip' => is_string($s->ip_address) ? $s->ip_address : null,
                    'last' => Carbon::createFromTimestamp($s->last_activity),
                    'current' => $s->id === session()->getId(),
                    'mobile' => $ua->device() !== 'desktop',
                ];
            });
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $user = $this->user();
        $qr = null;

        if ($this->pendingSecret) {
            $url = app(Google2FA::class)->getQRCodeUrl(config('app.name'), $user->email, $this->pendingSecret);
            $qr = (string) QrCode::format('svg')->size(180)->margin(0)->generate($url);
        }

        return view('livewire.settings.security', [
            'user' => $user,
            'qr' => $qr,
            'sessions' => $this->sessions(),
            'history' => $user->loginHistories()->limit(5)->get(),
        ]);
    }
}
