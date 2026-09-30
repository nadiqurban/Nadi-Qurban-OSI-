<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Proof of execution (photos + videos + notes) uploaded by the vendor/HQ and
 * verified by HQ. Files live on the private disk.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $vendor_id
 * @property ReportStatus $status
 * @property string|null $notes
 * @property Carbon|null $submitted_at
 * @property Carbon|null $verified_at
 */
class ExecutionReport extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const IMAGE_MIMES = ['image/png', 'image/jpeg', 'image/webp'];

    public const VIDEO_MIMES = ['video/mp4', 'video/webm', 'video/quicktime'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->useDisk('local')->acceptsMimeTypes(self::IMAGE_MIMES);
        $this->addMediaCollection('videos')->useDisk('local')->acceptsMimeTypes(self::VIDEO_MIMES);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Signed, short-lived URL for one of this report's media files. */
    public static function mediaUrl(Media $media, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('execution.media', now()->addMinutes($minutes), ['media' => $media->id]);
    }
}
