<?php

namespace App\Livewire\Settings;

use App\Enums\Module;
use App\Enums\Severity;
use App\Services\Chip\ChipClient;
use App\Services\Chip\ChipGateway;
use App\Support\Audit;
use App\Support\Settings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tetapan › Integrasi API. Phase 5: CHIP Collect keys for the instalment portal
 * (secret key stored encrypted, never sent back to the browser). The rest of the
 * integrations page (API keys, other gateways) arrives in Phase 9.
 */
#[Layout('layouts::app')]
#[Title('Integrasi API')]
class Integrations extends Component
{
    public string $brandId = '';

    /** New secret key; empty = keep the stored one. */
    public string $secretKey = '';

    public string $webhookPublicKey = '';

    public bool $hasSecret = false;

    public function mount(Settings $settings): void
    {
        $this->brandId = (string) $settings->get('chip.brand_id');
        $this->webhookPublicKey = (string) $settings->get('chip.webhook_public_key');
        $this->hasSecret = (string) $settings->get('chip.secret_key') !== '';
    }

    public function save(Settings $settings): void
    {
        $this->authorize(Module::Api->managePermission());

        $this->validate([
            'brandId' => ['required', 'uuid'],
            'secretKey' => [$this->hasSecret ? 'nullable' : 'required', 'string', 'min:20', 'max:200'],
            'webhookPublicKey' => ['nullable', 'string', 'max:4000', 'regex:/^\s*$|-----BEGIN PUBLIC KEY-----/'],
        ], ['webhookPublicKey.regex' => 'Kunci awam mesti dalam format PEM (-----BEGIN PUBLIC KEY-----).'], [
            'brandId' => 'Brand ID', 'secretKey' => 'Secret Key', 'webhookPublicKey' => 'kunci awam webhook',
        ]);

        $settings->set('chip.brand_id', trim($this->brandId));
        $settings->set('chip.webhook_public_key', trim($this->webhookPublicKey) ?: null);

        if (trim($this->secretKey) !== '') {
            $settings->set('chip.secret_key', trim($this->secretKey), encrypted: true);
            $this->hasSecret = true;
        }

        $this->secretKey = '';
        Audit::log('settings.chip', 'Kunci CHIP Collect dikemaskini', severity: Severity::Warning, causer: auth()->user(), logName: 'settings');
        $this->dispatch('toast', message: 'Tetapan CHIP Collect disimpan.');
    }

    public function render(ChipGateway $chip): mixed
    {
        return view('livewire.settings.integrations', [
            'canManage' => auth()->user()?->can(Module::Api->managePermission()) ?? false,
            'configured' => $chip->isConfigured(),
            'fake' => ! $chip instanceof ChipClient,
            'webhookUrl' => route('webhooks.chip'),
        ]);
    }
}
