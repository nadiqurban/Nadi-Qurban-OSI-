<?php

namespace App\Models;

use App\Enums\OrderStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property OrderStage $stage
 * @property int|null $user_id
 * @property string|null $note
 * @property Carbon $created_at
 */
class OrderStageHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'stage', 'user_id', 'note', 'created_at'];

    protected function casts(): array
    {
        return [
            'stage' => OrderStage::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
