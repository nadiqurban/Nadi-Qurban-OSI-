<?php

namespace App\Livewire\Settings;

use App\Enums\Module;
use App\Enums\Severity;
use App\Models\ApiClient;
use App\Models\ApiRequest;
use App\Models\User;
use App\Services\Chip\ChipClient;
use App\Services\Chip\ChipGateway;
use App\Support\Audit;
use App\Support\PaymentGateways;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tetapan › Integrasi API (Pengguna & Peranan.dc.html isApi): API keys (Sanctum,
 * shown once), base URL + rate-limit meter, third-party connections, and the
 * CHIP IN / Billplz / toyyibPay collection gateways. Secrets are stored encrypted
 * and never sent back to the browser.
 *
 * @property-read Collection<int, ApiClient> $clients
 */
#[Layout('layouts::app')]
#[Title('Integrasi API')]
class Integrations extends Component
{
    public const RATE_LIMIT = 120;

    // ---- API key modal
    public bool $showKey = false;

    public string $keyName = '';

    public string $keyEnv = 'live';

    /** @var list<string> */
    public array $keyAbilities = ['read'];

    /** Plain token, shown once right after creation. */
    public ?string $newToken = null;

    // ---- gateway modals
    public string $gateway = '';     // chip | billplz | toyyibpay

    public bool $showGateway = false;

    /** @var array<string, string> non-secret fields */
    public array $form = [];

    /** New secret values; empty = keep the stored one. */
    public string $secret = '';

    public string $secret2 = '';

    /** @var list<string> */
    public array $chipMethods = [];

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    /** @return Collection<int, ApiClient> */
    #[Computed]
    public function clients(): Collection
    {
        return ApiClient::query()->whereNull('revoked_at')->with('tokens')->latest()->get();
    }

    // ------------------------------------------------------------ API keys

    public function openKey(): void
    {
        $this->authorize(Module::Api->managePermission());
        $this->resetErrorBag();
        $this->reset('keyName', 'keyEnv', 'newToken');
        $this->keyAbilities = ['read'];
        $this->showKey = true;
    }

    public function createKey(): void
    {
        $this->authorize(Module::Api->managePermission());
        $this->validate([
            'keyName' => ['required', 'string', 'max:100'],
            'keyEnv' => ['required', Rule::in(['live', 'test'])],
            'keyAbilities' => ['required', 'array', 'min:1'],
            'keyAbilities.*' => [Rule::in(['read', 'write'])],
        ], [], ['keyName' => 'Nama Kunci', 'keyAbilities' => 'Kebenaran']);

        $client = ApiClient::query()->create([
            'name' => trim($this->keyName),
            'environment' => $this->keyEnv,
            'prefix' => '',
            'created_by' => $this->actor()->id,
        ]);
        $plain = $client->createToken($this->keyEnv, $this->keyAbilities)->plainTextToken;
        $client->update(['prefix' => 'nq_'.$this->keyEnv.'_'.mb_substr((string) str($plain)->after('|')->after('nq_'), 0, 6)]);

        Audit::log('api.key_created', "Kunci API \"{$client->name}\" ({$this->keyEnv}) dijana", $client, Severity::Warning,
            ['abilities' => $this->keyAbilities], $this->actor(), 'settings');

        $this->newToken = $plain;
        unset($this->clients);
    }

    public function revoke(int $clientId): void
    {
        $this->authorize(Module::Api->managePermission());
        $client = ApiClient::query()->findOrFail($clientId);
        $client->tokens()->delete();
        $client->update(['revoked_at' => now()]);

        Audit::log('api.key_revoked', "Kunci API \"{$client->name}\" dibatalkan", $client, Severity::Warning, causer: $this->actor(), logName: 'settings');
        unset($this->clients);
        $this->dispatch('toast', message: "Kunci {$client->name} dibatalkan.", tone: 'info');
    }

    // ------------------------------------------------------------ gateways

    public function openGateway(string $gateway, Settings $settings, PaymentGateways $gateways): void
    {
        $this->authorize(Module::Api->managePermission());
        abort_unless(isset(PaymentGateways::GATEWAYS[$gateway]), 404);
        $this->resetErrorBag();
        $this->gateway = $gateway;
        $this->secret = $this->secret2 = '';
        $this->form = match ($gateway) {
            'chip' => ['brand_id' => (string) $settings->get('chip.brand_id'), 'webhook_public_key' => (string) $settings->get('chip.webhook_public_key')],
            'billplz' => ['collection_id' => (string) $settings->get('billplz.collection_id')],
            default => ['category_code' => (string) $settings->get('toyyibpay.category_code'), 'env' => (string) $settings->get('toyyibpay.env', 'production')],
        };
        $this->chipMethods = $gateways->chipMethods();
        $this->showGateway = true;
    }

    public function toggleChipMethod(string $method): void
    {
        abort_unless(isset(PaymentGateways::CHIP_METHODS[$method]), 422);
        $this->chipMethods = in_array($method, $this->chipMethods, true)
            ? array_values(array_diff($this->chipMethods, [$method]))
            : [...$this->chipMethods, $method];
    }

    public function saveGateway(Settings $settings): void
    {
        $this->authorize(Module::Api->managePermission());
        $g = $this->gateway;
        $has = fn (string $key) => (string) $settings->get($key) !== '';

        // Pasted keys often carry spaces / line breaks around them.
        $this->form = array_map(trim(...), $this->form);
        $this->secret = trim($this->secret);

        match ($g) {
            'chip' => $this->validate([
                'form.brand_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9-]+$/'],
                'secret' => [$has('chip.secret_key') ? 'nullable' : 'required', 'string', 'min:20', 'max:200'],
                'form.webhook_public_key' => ['nullable', 'string', 'max:4000', 'regex:/^\s*$|-----BEGIN PUBLIC KEY-----/'],
                'chipMethods' => ['required', 'array', 'min:1'],
            ], ['form.brand_id.regex' => 'Brand ID hanya huruf, nombor dan sengkang (salin dari CHIP › Developers › Brands).', 'form.webhook_public_key.regex' => 'Kunci awam mesti dalam format PEM (-----BEGIN PUBLIC KEY-----).', 'chipMethods.required' => 'Hidupkan sekurang-kurangnya satu kaedah.'],
                ['form.brand_id' => 'Brand ID', 'secret' => 'Secret Key']),
            'billplz' => $this->validate([
                'secret' => [$has('billplz.secret_key') ? 'nullable' : 'required', 'string', 'min:10', 'max:200'],
                'form.collection_id' => ['required', 'string', 'alpha_dash', 'max:40'],
                'secret2' => ['nullable', 'string', 'max:200'],
            ], [], ['secret' => 'API Secret Key', 'form.collection_id' => 'Collection ID', 'secret2' => 'X-Signature Key']),
            default => $this->validate([
                'secret' => [$has('toyyibpay.secret_key') ? 'nullable' : 'required', 'string', 'min:10', 'max:200'],
                'form.category_code' => ['required', 'string', 'alpha_dash', 'max:40'],
                'form.env' => ['required', Rule::in(['sandbox', 'production'])],
            ], [], ['secret' => 'Secret Key', 'form.category_code' => 'Category Code']),
        };

        match ($g) {
            'chip' => $settings->setMany([
                'chip.brand_id' => trim($this->form['brand_id']),
                'chip.webhook_public_key' => trim($this->form['webhook_public_key'] ?? '') ?: null,
                'chip.methods' => json_encode($this->chipMethods),
            ]),
            'billplz' => $settings->set('billplz.collection_id', trim($this->form['collection_id'])),
            default => $settings->setMany(['toyyibpay.category_code' => trim($this->form['category_code']), 'toyyibpay.env' => $this->form['env']]),
        };

        $secretKey = ['chip' => 'chip.secret_key', 'billplz' => 'billplz.secret_key', 'toyyibpay' => 'toyyibpay.secret_key'][$g];
        if (trim($this->secret) !== '') {
            $settings->set($secretKey, trim($this->secret), encrypted: true);
        }
        if ($g === 'billplz' && trim($this->secret2) !== '') {
            $settings->set('billplz.x_signature', trim($this->secret2), encrypted: true);
        }
        $settings->set($g.'.enabled', '1');

        $this->secret = $this->secret2 = '';
        $this->showGateway = false;
        Audit::log('settings.'.$g, 'Tetapan '.PaymentGateways::GATEWAYS[$g][0].' dikemaskini', severity: Severity::Warning, causer: $this->actor(), logName: 'settings');
        $this->dispatch('toast', message: 'Tetapan '.PaymentGateways::GATEWAYS[$g][0].' disimpan & disambung.');
    }

    public function toggleGateway(string $gateway, Settings $settings, PaymentGateways $gateways): void
    {
        $this->authorize(Module::Api->managePermission());
        abort_unless(isset(PaymentGateways::GATEWAYS[$gateway]), 404);
        $on = ! $gateways->enabled($gateway);
        $settings->set($gateway.'.enabled', $on ? '1' : '0');

        Audit::log('settings.'.$gateway, PaymentGateways::GATEWAYS[$gateway][0].($on ? ' diaktifkan' : ' dinyahaktifkan'), severity: Severity::Warning, causer: $this->actor(), logName: 'settings');
        $this->dispatch('toast', message: PaymentGateways::GATEWAYS[$gateway][0].($on ? ' diaktifkan.' : ' dinyahaktifkan.'), tone: $on ? 'success' : 'info');
    }

    // ------------------------------------------------------------ view data

    /** @return list<array{name: string, desc: string, icon: string, tone: string, active: bool}> */
    private function connections(PaymentGateways $gateways): array
    {
        $fpx = collect(array_keys(PaymentGateways::GATEWAYS))->first(fn ($g) => $gateways->status($g)[0] === 'Aktif');

        return [
            ['name' => 'WhatsApp Cloud API', 'desc' => 'Notifikasi status tempahan kepada pelanggan', 'icon' => 'whatsapp-logo', 'tone' => 'success', 'active' => (bool) config('services.whatsapp.url') && (bool) config('services.whatsapp.token')],
            ['name' => 'Gerbang Pembayaran FPX', 'desc' => 'Pengesahan bayaran masa nyata'.($fpx ? ' ('.PaymentGateways::GATEWAYS[$fpx][0].')' : ''), 'icon' => 'bank', 'tone' => 'info', 'active' => $fpx !== null],
            ['name' => 'EasyParcel', 'desc' => 'Tempahan kurier & penjejakan AWB', 'icon' => 'truck', 'tone' => 'warning', 'active' => (bool) config('services.easyparcel.key')],
            ['name' => 'Google Sheets Export', 'desc' => 'Segerak data tempahan ke spreadsheet', 'icon' => 'table', 'tone' => 'neutral', 'active' => false],
        ];
    }

    public function render(PaymentGateways $gateways, ChipGateway $chip): mixed
    {
        $connections = $this->connections($gateways);
        $minute = ApiRequest::query()->where('created_at', '>=', now()->subMinute())
            ->selectRaw('api_client_id, COUNT(*) as n')->groupBy('api_client_id')->pluck('n')->max() ?? 0;

        return view('livewire.settings.integrations', [
            'canManage' => auth()->user()?->can(Module::Api->managePermission()) ?? false,
            'connections' => $connections,
            'stats' => [
                ['icon' => 'plugs-connected', 'tone' => 'success', 'value' => (string) collect($connections)->where('active', true)->count(), 'label' => 'Sambungan Aktif'],
                ['icon' => 'key', 'tone' => 'primary', 'value' => (string) $this->clients->count(), 'label' => 'Kunci API'],
                ['icon' => 'arrows-left-right', 'tone' => 'info', 'value' => self::compact(ApiRequest::query()->where('created_at', '>=', now()->subDays(30))->count()), 'label' => 'Panggilan (30h)'],
                ['icon' => 'gauge', 'tone' => 'warning', 'value' => self::RATE_LIMIT.'/min', 'label' => 'Had Kadar'],
            ],
            'usagePct' => (int) min(100, round((int) $minute / self::RATE_LIMIT * 100)),
            'gateways' => $gateways,
            'baseUrl' => url('/api/v1'),
            'chipFake' => ! $chip instanceof ChipClient,
            'chipWebhookUrl' => route('webhooks.chip'),
        ]);
    }

    public static function compact(int $n): string
    {
        return $n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1), '0'), '.').'K' : (string) $n;
    }
}
