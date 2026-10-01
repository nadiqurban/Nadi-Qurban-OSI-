<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $webhook_endpoint_id
 * @property string $event
 * @property int|null $http_status
 * @property int $attempt
 * @property string $status
 * @property string|null $error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read WebhookEndpoint $endpoint
 */
class WebhookDelivery extends Model
{
    use MassPrunable;

    public const PENDING = 'menunggu';

    public const SUCCESS = 'berjaya';

    public const FAILED = 'gagal';

    protected $guarded = ['id'];

    /** @return BelongsTo<WebhookEndpoint, $this> */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(90));
    }
}
