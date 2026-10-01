<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $api_client_id
 * @property string $method
 * @property string $path
 * @property int $status
 * @property string|null $ip_address
 * @property Carbon $created_at
 */
class ApiRequest extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(90));
    }
}
