<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * A system integrating through /api/v1 (one Sanctum token per client).
 *
 * @property int $id
 * @property string $name
 * @property string $environment
 * @property string $prefix
 * @property int|null $created_by
 * @property Carbon|null $revoked_at
 * @property Carbon $created_at
 * @property-read User|null $creator
 */
class ApiClient extends Model
{
    use HasApiTokens;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['revoked_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ApiRequest, $this> */
    public function requests(): HasMany
    {
        return $this->hasMany(ApiRequest::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }
}
