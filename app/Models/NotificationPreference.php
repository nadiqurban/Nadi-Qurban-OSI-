<?php

namespace App\Models;

use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property NotificationType $type
 * @property bool $app
 * @property bool $mail
 * @property bool $wa
 */
class NotificationPreference extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => NotificationType::class, 'app' => 'boolean', 'mail' => 'boolean', 'wa' => 'boolean'];
    }

    /**
     * Effective channels for a user (stored rows over type defaults).
     *
     * @return array<string, array{app: bool, mail: bool, wa: bool}>
     */
    public static function for(User $user): array
    {
        $rows = self::query()->where('user_id', $user->id)->get()->keyBy(fn (self $p) => $p->type->value);

        return collect(NotificationType::cases())->mapWithKeys(fn (NotificationType $t) => [
            $t->value => isset($rows[$t->value])
                ? ['app' => $rows[$t->value]->app, 'mail' => $rows[$t->value]->mail, 'wa' => $rows[$t->value]->wa]
                : $t->defaults(),
        ])->all();
    }
}
