<?php

namespace App\Support;

use App\Enums\NotificationType;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/** Sends an AppNotification to every active user allowed to see the module (Super Admin always). */
final class Notifier
{
    public static function send(
        NotificationType $type,
        string $permission,
        string $title,
        string $body,
        ?string $url = null,
        ?string $category = null,
        ?User $except = null,
    ): void {
        $recipients = self::recipients($permission)->reject(fn (User $u) => $except && $u->is($except));

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new AppNotification($type, $title, $body, $url, $category));
        }
    }

    /** @return Collection<int, User> */
    public static function recipients(string $permission): Collection
    {
        return User::query()
            ->where('status', UserStatus::Active)
            ->where(fn ($q) => $q->permission($permission)->orWhereHas('roles', fn ($r) => $r->where('name', RoleName::SuperAdmin->value)))
            ->get();
    }
}
