<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\On;
use Livewire\Component;

/** Header bell: live unread count (wire:poll 30s) + dropdown with the latest 5. */
class NotificationBell extends Component
{
    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function open(string $id): mixed
    {
        /** @var DatabaseNotification $n */
        $n = $this->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        $url = $n->data['url'] ?? null;

        return $url ? $this->redirect($url, navigate: true) : null;
    }

    public function markAllRead(): void
    {
        $this->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->dispatch('notifications-updated');
    }

    #[On('notifications-updated')]
    public function refresh(): void {}

    public function render(): mixed
    {
        $user = $this->user();

        return view('livewire.notification-bell', [
            'unread' => $user->unreadNotifications()->count(),
            'latest' => $user->notifications()->limit(5)->get(),
            'canPage' => $user->can('notifications.view'),
        ]);
    }
}
