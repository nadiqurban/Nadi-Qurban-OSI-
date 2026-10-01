<?php

namespace App\Livewire\Settings;

use App\Enums\Module;
use App\Enums\Severity;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use App\Rules\PublicUrl;
use App\Support\Audit;
use App\Support\Webhooks as WebhookService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tetapan › Webhooks (Pengguna & Peranan.dc.html isWebhooks): stats, endpoint
 * CRUD with event subscriptions, signing secret, per-event toggles and the
 * delivery log. Payloads are signed with X-NQ-Signature (HMAC-SHA256).
 *
 * @property-read Collection<int, WebhookEndpoint> $endpoints
 */
#[Layout('layouts::app')]
#[Title('Webhooks')]
class Webhooks extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $url = '';

    public string $description = '';

    /** @var list<string> */
    public array $events = [];

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    /** @return Collection<int, WebhookEndpoint> */
    #[Computed]
    public function endpoints(): Collection
    {
        return WebhookEndpoint::query()->withCount([
            'deliveries as total_30d' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))->where('status', '!=', WebhookDelivery::PENDING),
            'deliveries as ok_30d' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))->where('status', WebhookDelivery::SUCCESS),
        ])->withMax('deliveries as last_at', 'created_at')->latest()->get();
    }

    public function openForm(?int $endpointId = null): void
    {
        $this->authorize(Module::Webhooks->managePermission());
        $this->resetErrorBag();
        $endpoint = $endpointId ? WebhookEndpoint::query()->findOrFail($endpointId) : null;
        $this->editingId = $endpoint?->id;
        $this->url = $endpoint->url ?? '';
        $this->description = (string) ($endpoint->description ?? '');
        $this->events = $endpoint->events ?? array_keys(WebhookEndpoint::EVENTS);
        $this->showForm = true;
    }

    public function toggleEvent(string $event): void
    {
        abort_unless(isset(WebhookEndpoint::EVENTS[$event]), 422);
        $this->events = in_array($event, $this->events, true) ? array_values(array_diff($this->events, [$event])) : [...$this->events, $event];
    }

    public function save(): void
    {
        $this->authorize(Module::Webhooks->managePermission());
        $this->validate([
            'url' => ['required', 'url:https,http', 'max:500', new PublicUrl],
            'description' => ['nullable', 'string', 'max:150'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => [Rule::in(array_keys(WebhookEndpoint::EVENTS))],
        ], ['events.required' => 'Pilih sekurang-kurangnya satu peristiwa.', 'url.starts_with' => 'Endpoint mesti menggunakan HTTPS.'],
            ['url' => 'Endpoint URL', 'events' => 'Peristiwa']);

        $data = ['url' => trim($this->url), 'description' => trim($this->description) ?: null, 'events' => $this->events];
        $endpoint = $this->editingId
            ? tap(WebhookEndpoint::query()->findOrFail($this->editingId))->update($data)
            : WebhookEndpoint::query()->create($data + ['created_by' => $this->actor()->id]);

        Audit::log('webhook.saved', 'Endpoint webhook '.$endpoint->host().($this->editingId ? ' dikemaskini' : ' didaftarkan'), $endpoint,
            Severity::Warning, ['events' => $endpoint->events], $this->actor(), 'settings');

        $this->showForm = false;
        unset($this->endpoints);
        $this->dispatch('toast', message: 'Endpoint disimpan.');
    }

    public function toggleActive(int $endpointId): void
    {
        $this->authorize(Module::Webhooks->managePermission());
        $endpoint = WebhookEndpoint::query()->findOrFail($endpointId);
        $endpoint->update(['is_active' => ! $endpoint->is_active]);
        unset($this->endpoints);
    }

    public function delete(int $endpointId): void
    {
        $this->authorize(Module::Webhooks->managePermission());
        $endpoint = WebhookEndpoint::query()->findOrFail($endpointId);
        $endpoint->delete();
        Audit::log('webhook.deleted', "Endpoint webhook {$endpoint->host()} dipadam", severity: Severity::Warning, causer: $this->actor(), logName: 'settings');
        unset($this->endpoints);
        $this->dispatch('toast', message: 'Endpoint dipadam.', tone: 'info');
    }

    /** "Uji" — send a signed test ping to one endpoint. */
    public function ping(int $endpointId, WebhookService $webhooks): void
    {
        $this->authorize(Module::Webhooks->managePermission());
        $endpoint = WebhookEndpoint::query()->findOrFail($endpointId);
        $webhooks->dispatch('ping', ['message' => 'Ujian webhook daripada Nadi Qurban OSI.'], $endpoint);
        $this->dispatch('toast', message: "Ujian dihantar ke {$endpoint->host()}.");
    }

    public function toggleGlobalEvent(string $event, WebhookService $webhooks): void
    {
        $this->authorize(Module::Webhooks->managePermission());
        abort_unless(isset(WebhookEndpoint::EVENTS[$event]), 422);
        $on = $webhooks->enabledEvents();
        $webhooks->setEnabledEvents(in_array($event, $on, true) ? array_values(array_diff($on, [$event])) : [...$on, $event]);
    }

    public function rotateSecret(WebhookService $webhooks): void
    {
        $this->authorize(Module::Webhooks->managePermission());
        $webhooks->rotateSecret();
        Audit::log('webhook.secret', 'Signing secret webhook dijana semula', severity: Severity::Critical, causer: $this->actor(), logName: 'settings');
        $this->dispatch('toast', message: 'Signing secret baharu dijana. Kemas kini sistem penerima anda.', tone: 'info');
    }

    public function render(WebhookService $webhooks): mixed
    {
        $since = now()->subDays(30);
        $done = WebhookDelivery::query()->where('created_at', '>=', $since)->where('status', '!=', WebhookDelivery::PENDING);
        $total = (clone $done)->count();
        $ok = (clone $done)->where('status', WebhookDelivery::SUCCESS)->count();
        $canManage = auth()->user()?->can(Module::Webhooks->managePermission()) ?? false;

        return view('livewire.settings.webhooks', [
            'canManage' => $canManage,
            'stats' => [
                ['icon' => 'webhooks-logo', 'tone' => 'primary', 'value' => (string) $this->endpoints->where('is_active', true)->count(), 'label' => 'Endpoint Aktif'],
                ['icon' => 'lightning', 'tone' => 'gold', 'value' => (string) count($webhooks->enabledEvents()), 'label' => 'Peristiwa'],
                ['icon' => 'check-circle', 'tone' => 'success', 'value' => $total > 0 ? number_format($ok / $total * 100, 1).'%' : '—', 'label' => 'Kadar Berjaya'],
                ['icon' => 'paper-plane-tilt', 'tone' => 'info', 'value' => number_format(WebhookDelivery::query()->where('created_at', '>=', $since)->count()), 'label' => 'Dihantar (30h)'],
            ],
            'enabled' => $webhooks->enabledEvents(),
            'secret' => $canManage ? $webhooks->secret() : null,
            'logs' => WebhookDelivery::query()->with('endpoint')->latest()->latest('id')->limit(10)->get(),
        ]);
    }
}
