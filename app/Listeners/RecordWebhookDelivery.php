<?php

namespace App\Listeners;

use App\Models\WebhookDelivery;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

/** Keeps the "Log Penghantaran" row of each outgoing webhook in sync with its attempts. */
class RecordWebhookDelivery
{
    public function handleSucceeded(WebhookCallSucceededEvent $event): void
    {
        $this->record($event, WebhookDelivery::SUCCESS);
    }

    public function handleFailed(WebhookCallFailedEvent $event): void
    {
        $this->record($event, WebhookDelivery::PENDING);   // will retry
    }

    public function handleFinalFailure(FinalWebhookCallFailedEvent $event): void
    {
        $this->record($event, WebhookDelivery::FAILED);
    }

    private function record(WebhookCallEvent $event, string $status): void
    {
        $uuid = $event->meta['delivery'] ?? null;

        if (! $uuid) {
            return;
        }

        WebhookDelivery::query()->where('uuid', $uuid)->update([
            'status' => $status,
            'attempt' => $event->attempt,
            'http_status' => $event->response?->getStatusCode(),
            'error' => $status === WebhookDelivery::SUCCESS ? null : mb_substr((string) ($event->errorMessage ?: $event->errorType ?: 'Tiada respons'), 0, 300),
            'updated_at' => now(),
        ]);
    }
}
