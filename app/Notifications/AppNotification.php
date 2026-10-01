<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Every in-app notification (6 types). Channels follow the recipient's
 * Keutamaan Notifikasi: App = database, Emel = mail, WA = WhatsApp.
 * The database row is written immediately; mail / WhatsApp go through the queue.
 */
class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NotificationType $type,
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $category = null,
    ) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        $pref = NotificationPreference::for($notifiable)[$this->type->value];

        return array_values(array_filter([
            $pref['app'] ? 'database' : null,
            $pref['mail'] && $notifiable->email ? 'mail' : null,
            $pref['wa'] && $notifiable->phone ? WhatsAppChannel::class : null,
        ]));
    }

    /** @return array<string, string> */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    /** @return array<string, string|null> */
    public function toArray(User $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'category' => $this->category ?? $this->type->category(),
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title.' — Nadi Qurban OSI')
            ->greeting('Assalamualaikum '.$notifiable->name.',')
            ->line($this->body);

        if ($this->url) {
            $mail->action('Buka dalam sistem', $this->url);
        }

        return $mail->salutation('Nadi Qurban Sdn. Bhd.');
    }

    public function toWhatsApp(User $notifiable): string
    {
        return "*{$this->title}*\n{$this->body}".($this->url ? "\n{$this->url}" : '');
    }
}
