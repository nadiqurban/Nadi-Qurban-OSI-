<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Package tier (Delima, Zamrud, Topaz, Nilam, Mutiara).
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property bool $is_active
 * @property int $sort
 */
class Package extends Model
{
    protected $fillable = ['name', 'code', 'is_active', 'sort'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort');
    }
}
