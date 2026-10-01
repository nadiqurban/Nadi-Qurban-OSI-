<?php

namespace App\Livewire\Notifications;

use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Notifikasi (Notifikasi.dc.html): feed with tabs, mark read / all read,
 * Ringkasan panel or Keutamaan Notifikasi grid (App / Emel / WA per type).
 *
 * @property-read LengthAwarePaginator<int, DatabaseNotification> $notifications
 */
#[Layout('layouts::app')]
#[Title('Notifikasi')]
class Index extends Component
{
    use WithPagination;

    public const TABS = ['semua' => 'Semua', 'belum' => 'Belum Dibaca', 'tempahan' => 'Tempahan', 'kewangan' => 'Kewangan', 'sistem' => 'Sistem'];

    #[Url(as: 'tab', except: 'semua')]
    public string $tab = 'semua';

    #[Url(as: 'tetapan', except: false)]
    public bool $settings = false;

    /** @var array<string, array{app: bool, mail: bool, wa: bool}> */
    public array $prefs = [];

    public function mount(): void
    {
        $this->prefs = NotificationPreference::for($this->user());
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    /** @return LengthAwarePaginator<int, DatabaseNotification> */
    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        $q = $this->user()->notifications();

        match ($this->tab) {
            'belum' => $q->whereNull('read_at'),
            'tempahan' => $q->where('data->category', 'Tempahan'),
            'kewangan' => $q->where('data->category', 'Kewangan'),
            'sistem' => $q->whereIn('data->category', ['Sistem', 'Vendor', 'Sijil']),
            default => null,
        };

        return $q->paginate(15);
    }

    /** @return array<string, int> category => count */
    public function summary(): array
    {
        $counts = $this->user()->notifications()->get(['data'])->countBy(fn (DatabaseNotification $n) => $n->data['category'] ?? 'Sistem');

        return collect(NotificationType::categories())->mapWithKeys(fn ($style, string $cat) => [$cat => (int) ($counts[$cat] ?? 0)])->all();
    }

    public function open(string $id): mixed
    {
        $n = $this->user()->notifications()->findOrFail($id);
        $n->markAsRead();
        $url = $n->data['url'] ?? null;

        return $url ? $this->redirect($url) : null;
    }

    public function markRead(string $id): void
    {
        $this->user()->notifications()->findOrFail($id)->markAsRead();
        $this->dispatch('notifications-updated');
    }

    public function remove(string $id): void
    {
        $this->user()->notifications()->findOrFail($id)->delete();
        unset($this->notifications);
        $this->dispatch('notifications-updated');
    }

    public function markAllRead(): void
    {
        $this->user()->unreadNotifications()->update(['read_at' => now()]);
        unset($this->notifications);
        $this->dispatch('notifications-updated');
        $this->dispatch('toast', message: 'Semua notifikasi ditanda dibaca.');
    }

    public function togglePref(string $type, string $channel): void
    {
        $t = NotificationType::from($type);
        abort_unless(in_array($channel, ['app', 'mail', 'wa'], true), 422);

        $this->prefs[$type][$channel] = ! $this->prefs[$type][$channel];
        NotificationPreference::query()->updateOrCreate(['user_id' => $this->user()->id, 'type' => $t], $this->prefs[$type]);
    }

    #[On('notifications-updated')]
    public function refreshList(): void
    {
        unset($this->notifications);
    }

    public function render(): mixed
    {
        $user = $this->user();
        $unread = $user->unreadNotifications()->count();

        return view('livewire.notifications.index', [
            'unread' => $unread,
            'tabCounts' => ['semua' => $user->notifications()->count(), 'belum' => $unread],
        ]);
    }
}
