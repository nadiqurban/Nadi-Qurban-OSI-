<?php

namespace App\Models;

use App\Enums\ReportType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Generated report (Pusat Laporan → "Laporan Dijana").
 *
 * @property int $id
 * @property ReportType $type
 * @property string $name
 * @property string $format
 * @property array{from?: string, to?: string, countries?: list<int>}|null $filters
 * @property string $status
 * @property string|null $path
 * @property int|null $size
 * @property string|null $error
 * @property int|null $requested_by
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property-read string $file_name
 * @property-read User|null $requester
 */
class ReportExport extends Model
{
    public const PROCESSING = 'diproses';

    public const DONE = 'siap';

    public const FAILED = 'gagal';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['type' => ReportType::class, 'filters' => 'array', 'completed_at' => 'datetime', 'size' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getFileNameAttribute(): string
    {
        return Str::of(str_replace('&', 'dan', $this->name))->ascii()->replaceMatches('/[^A-Za-z0-9]+/', '-')->trim('-').'.'.$this->format;
    }

    public function isDone(): bool
    {
        return $this->status === self::DONE;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::DONE => 'Siap',
            self::FAILED => 'Gagal',
            default => 'Diproses',
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            self::DONE => 'success',
            self::FAILED => 'danger',
            default => 'warning',
        };
    }
}
