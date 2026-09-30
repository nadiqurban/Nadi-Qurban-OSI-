<?php

namespace App\Models;

use App\Enums\Courier;
use App\Enums\PostType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * AWB / postage for the certificate (one per order). Also backs the "Waybill"
 * action in Tempahan & Pelanggan (PRD §12.11).
 *
 * @property int $id
 * @property int $order_id
 * @property Courier $courier
 * @property PostType $post_type
 * @property string $consignment_no
 * @property string $recipient_name
 * @property string|null $phone
 * @property string $address
 * @property string|null $postcode
 * @property string|null $city
 * @property string|null $state
 * @property Carbon $generated_at
 * @property Carbon|null $delivered_at
 * @property-read Order $order
 */
class Shipment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'courier' => Courier::class,
            'post_type' => PostType::class,
            'generated_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
