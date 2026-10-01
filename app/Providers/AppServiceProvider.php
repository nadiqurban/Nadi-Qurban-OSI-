<?php

namespace App\Providers;

use App\Enums\RoleName;
use App\Models\Certificate;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Chip\ChipClient;
use App\Services\Chip\ChipGateway;
use App\Services\Chip\FakeChipGateway;
use App\Support\DashboardStats;
use App\Support\DocumentRegistry;
use App\Support\Settings;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use LogicException;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->singleton(DocumentRegistry::class);

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

        // Dokumen: auto-register system files (uploads elsewhere + generated PDFs).
        Event::listen(MediaHasBeenAddedEvent::class, fn (MediaHasBeenAddedEvent $e) => app(DocumentRegistry::class)->media($e->media));
        Certificate::created(fn (Certificate $c) => app(DocumentRegistry::class)->certificate($c));
        Invoice::created(fn (Invoice $i) => app(DocumentRegistry::class)->invoice($i));
        Quotation::created(fn (Quotation $q) => app(DocumentRegistry::class)->quotation($q));

        // Integrasi API: 120 requests / minute per API key.
        RateLimiter::for('api-v1', fn (Request $request) => Limit::perMinute(120)->by('api:'.($request->user()?->getKey() ?? $request->ip())));

        // Dashboard figures are cached 5 minutes; any order/payment/vendor change invalidates them.
        foreach ([Order::class, Payment::class, Vendor::class] as $model) {
            $model::saved(fn () => DashboardStats::flush());
            $model::deleted(fn () => DashboardStats::flush());
        }

        // Audit trail is immutable: rows are only ever inserted (and pruned by activitylog:clean).
        Activity::updating(fn () => throw new LogicException('Log audit tidak boleh diubah.'));
        Activity::deleting(fn () => throw new LogicException('Log audit tidak boleh dipadam.'));

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
