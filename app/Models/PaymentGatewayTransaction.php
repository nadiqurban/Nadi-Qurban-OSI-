<?php

namespace App\Models;

use App\Enums\PortalPayMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One gateway checkout (CHIP Purchase) for one or more instalments.
 *
 * @property int $id
 * @property string $gateway
 * @property string $reference
 * @property string|null $purchase_id
 * @property int|null $installment_plan_id
 * @property int|null $order_id Tempahan Awam (online payment of a public booking)
 * @property list<int> $installment_ids
 * @property int $amount_sen
 * @property PortalPayMethod|null $method
 * @property string $status created / paid / failed / cancelled
 * @property string|null $checkout_url
 * @property array<string, mixed>|null $payload
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property-read InstallmentPlan|null $plan
 * @property-read Order|null $order
 */
class PaymentGatewayTransaction extends Model
{
    public const CREATED = 'created';

    public const PAID = 'paid';

    public const FAILED = 'failed';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'installment_ids' => 'array',
            'payload' => 'array',
            'method' => PortalPayMethod::class,
            'paid_at' => 'datetime',
            'amount_sen' => 'integer',
        ];
    }

    /** @return BelongsTo<InstallmentPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class, 'installment_plan_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }
}
