<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value settings. Access through App\Support\Settings (cached), not directly.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property bool $is_encrypted
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_encrypted'];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }
}
