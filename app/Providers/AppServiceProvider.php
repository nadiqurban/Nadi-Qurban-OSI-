<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\User;
use App\Services\Chip\ChipClient;
use App\Services\Chip\ChipGateway;
use App\Services\Chip\FakeChipGateway;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);

        $this->app->singleton(ChipGateway::class, fn ($app) => config('services.chip.fake') && ! $app->isProduction()
            ? new FakeChipGateway
            : new ChipClient($app->make(Settings::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('ms');

        Model::preventLazyLoading(! $this->app->isProduction());

        // Super Admin always has every permission (its matrix column is fixed to Penuh).
        Gate::before(fn (User $user) => $user->hasRole(RoleName::SuperAdmin->value) ? true : null);

        // Password policy = design rules: ≥ 8 chars, upper & lower case, a number.
        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(8)->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->mixedCase()->numbers());

        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject('Tetapan Semula Kata Laluan — Nadi Qurban OSI')
            ->greeting('Assalamualaikum '.$user->name.',')
            ->line('Kami menerima permintaan untuk menetapkan semula kata laluan akaun anda.')
            ->action('Tetapkan Kata Laluan Baharu', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('Pautan ini tamat tempoh dalam '.config('auth.passwords.users.expire').' minit.')
            ->line('Jika anda tidak membuat permintaan ini, abaikan emel ini.')
            ->salutation('Nadi Qurban Sdn. Bhd.'));
    }
}
