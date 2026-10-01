<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Outgoing webhook receiver (Tetapan › Webhooks).
 *
 * @property int $id
 * @property string $url
 * @property string|null $description
 * @property list<string> $events
 * @property bool $is_active
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property-read Collection<int, WebhookDelivery> $deliveries
 */
class WebhookEndpoint extends Model
{
    /** Subscribable events (design hookEvents). */
    public const EVENTS = [
        'payment.confirmed' => 'Bayaran Disahkan',
        'order.completed' => 'Tempahan Selesai',
        'awb.generated' => 'AWB Dijana',
        'report.verified' => 'Laporan Disahkan',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['events' => 'array', 'is_active' => 'boolean'];
    }

    /** @return HasMany<WebhookDelivery, $this> */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function host(): string
    {
        return (string) (parse_url($this->url, PHP_URL_HOST) ?: $this->url);
    }
}
