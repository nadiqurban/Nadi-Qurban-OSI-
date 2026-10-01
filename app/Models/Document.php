<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Dokumen repository entry.
 *
 * @property int $id
 * @property string|null $key
 * @property string $name
 * @property DocumentCategory $category
 * @property string|null $service
 * @property string $source
 * @property string $extension
 * @property int|null $size
 * @property int|null $media_id
 * @property string|null $route_name
 * @property array<string, mixed>|null $route_params
 * @property int|null $uploaded_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Media|null $file
 * @property-read User|null $uploader
 */
class Document extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    public const SERVICES = ['Lembu', 'Kambing', 'Unta', 'Aqiqah', 'Lain'];

    /** Thumbnail icon + tone per file type (design typeMap). */
    public const TYPES = [
        'PDF' => ['file-pdf', 'danger'],
        'DOCX' => ['file-doc', 'info'],
        'DOC' => ['file-doc', 'info'],
        'XLSX' => ['file-xls', 'success'],
        'XLS' => ['file-xls', 'success'],
        'CSV' => ['file-csv', 'success'],
        'MP4' => ['file-video', 'purple'],
        'MOV' => ['file-video', 'purple'],
        'JPG' => ['file-image', 'gold'],
        'JPEG' => ['file-image', 'gold'],
        'PNG' => ['file-image', 'gold'],
        'WEBP' => ['file-image', 'gold'],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'route_params' => 'array',
            'size' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile()->useDisk('local');
    }

    /** @return BelongsTo<Media, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function type(): string
    {
        return mb_strtoupper($this->extension);
    }

    /** @return array{0: string, 1: string} */
    public function typeStyle(): array
    {
        return self::TYPES[$this->type()] ?? ['file', 'neutral'];
    }

    public function sizeLabel(): string
    {
        if ($this->size === null) {
            return 'Dijana';
        }

        return match (true) {
            $this->size >= 1024 ** 3 => round($this->size / 1024 ** 3, 1).' GB',
            $this->size >= 1024 ** 2 => round($this->size / 1024 ** 2, 1).' MB',
            default => max(1, (int) round($this->size / 1024)).' KB',
        };
    }

    /** Guess the service tag from a file name (design svcOf). */
    public static function serviceFromName(string $name): string
    {
        $n = mb_strtolower($name);

        return match (true) {
            str_contains($n, 'lembu') => 'Lembu',
            str_contains($n, 'kambing') => 'Kambing',
            str_contains($n, 'unta') => 'Unta',
            str_contains($n, 'aqiqah') => 'Aqiqah',
            default => 'Lain',
        };
    }
}
