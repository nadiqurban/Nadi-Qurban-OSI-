<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $lead_id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property int|null $user_id
 * @property string|null $actor_label
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class LeadActivity extends Model
{
    public const UPDATED_AT = null;

    /** Icon + tone per type (design `activities`). */
    public const STYLES = [
        'call' => ['phone-call', 'primary'],
        'email' => ['envelope-simple', 'info'],
        'whatsapp' => ['chat-circle-text', 'gold'],
        'created' => ['user-plus', 'purple'],
        'note' => ['note-pencil', 'primary'],
        'stage' => ['arrows-left-right', 'info'],
        'done' => ['check-circle', 'success'],
        'order' => ['shopping-cart-simple', 'success'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function who(): string
    {
        return $this->user->name ?? $this->actor_label ?? 'Sistem';
    }
}
