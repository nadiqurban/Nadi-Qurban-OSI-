<?php

namespace App\Models;

use App\Enums\AkadMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property AkadMethod $method
 * @property int|null $witness_id
 * @property bool $consented
 * @property Carbon $recorded_at
 * @property-read Order $order
 * @property-read User|null $witness
 */
class AkadRecord extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'method' => AkadMethod::class,
            'consented' => 'boolean',
            'recorded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function witness(): BelongsTo
    {
        return $this->belongsTo(User::class, 'witness_id');
    }
}
