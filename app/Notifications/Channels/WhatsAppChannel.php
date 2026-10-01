<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp delivery. With WHATSAPP_API_URL + WHATSAPP_API_TOKEN set it POSTs
 * {to, message} to that gateway; otherwise the message is only logged
 * (provider integration is configured in Phase 9).
 */
class WhatsAppChannel
{
    public function send(User $notifiable, AppNotification $notification): void
    {
        $to = preg_replace('/^0/', '60', (string) preg_replace('/\D+/', '', (string) $notifiable->phone));
        $message = $notification->toWhatsApp($notifiable);
        $url = config('services.whatsapp.url');

        if (! $url || ! config('services.whatsapp.token')) {
            Log::info('[WhatsApp] '.$to.': '.str_replace("\n", ' | ', $message));

            return;
        }

        Http::withToken((string) config('services.whatsapp.token'))->timeout(10)->retry(2, 500)
            ->post((string) $url, ['to' => $to, 'message' => $message])
            ->throw();
    }
}
