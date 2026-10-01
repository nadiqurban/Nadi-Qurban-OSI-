<?php

namespace App\Support;

use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Str;
use Spatie\WebhookServer\WebhookCall;

/**
 * Outgoing webhooks (Tetapan › Webhooks): one signing secret for all endpoints
 * (header X-NQ-Signature = HMAC-SHA256 of the JSON body), per-event on/off,
 * 3 attempts with exponential backoff via spatie/laravel-webhook-server.
 */
final class Webhooks
{
    public function __construct(private readonly Settings $settings) {}

    public function secret(): string
    {
        $secret = (string) $this->settings->get('webhooks.secret');

        if ($secret === '') {
            $secret = $this->rotateSecret();
        }

        return $secret;
    }

    public function rotateSecret(): string
    {
        $secret = 'whsec_'.Str::lower(Str::random(32));
        $this->settings->set('webhooks.secret', $secret, encrypted: true);

        return $secret;
    }

    /** @return list<string> globally enabled event keys */
    public function enabledEvents(): array
    {
        $stored = $this->settings->get('webhooks.events');

        return $stored === null ? array_keys(WebhookEndpoint::EVENTS) : array_values(array_intersect(array_keys(WebhookEndpoint::EVENTS), (array) json_decode((string) $stored, true)));
    }

    /** @param list<string> $events */
    public function setEnabledEvents(array $events): void
    {
        $this->settings->set('webhooks.events', json_encode(array_values(array_intersect(array_keys(WebhookEndpoint::EVENTS), $events))));
    }

    /**
     * Queue the event to every active endpoint subscribed to it.
     *
     * @param  array<string, mixed>  $data
     */
    public function dispatch(string $event, array $data, ?WebhookEndpoint $only = null): int
    {
        if (! $only && ! in_array($event, $this->enabledEvents(), true)) {
            return 0;
        }

        $endpoints = $only ? collect([$only]) : WebhookEndpoint::query()->where('is_active', true)->get()
            ->filter(fn (WebhookEndpoint $e) => in_array($event, $e->events, true));

        foreach ($endpoints as $endpoint) {
            $delivery = WebhookDelivery::query()->create([
                'uuid' => (string) Str::uuid(),
                'webhook_endpoint_id' => $endpoint->id,
                'event' => $event,
                'status' => WebhookDelivery::PENDING,
            ]);

            WebhookCall::create()
                ->url($endpoint->url)
                ->payload(['id' => $delivery->uuid, 'event' => $event, 'created_at' => now()->toIso8601String(), 'data' => $data])
                ->useSecret($this->secret())
                ->meta(['delivery' => $delivery->uuid])
                ->dispatch();
        }

        return $endpoints->count();
    }
}
