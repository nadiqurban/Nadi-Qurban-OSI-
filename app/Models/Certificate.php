<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One certificate per participant (bahagian): NQ-SIJIL-{year}-{seq4}.
 *
 * @property int $id
 * @property int $order_id
 * @property int $position
 * @property string $certificate_no
 * @property string $recipient_name
 * @property Carbon $generated_at
 * @property-read Order $order
 */
class Certificate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
