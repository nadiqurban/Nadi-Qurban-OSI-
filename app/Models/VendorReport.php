<?php

namespace App\Models;

use App\Enums\VendorReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Vendor "Report Submission" for one PO. Files are media with a custom
 * property "animal" (Lembu / Kambing / Unta) so they can be grouped.
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $vendor_id
 * @property string|null $notes
 * @property VendorReportStatus $status
 * @property string|null $revision_note
 * @property int|null $submitted_by
 * @property Carbon|null $submitted_at
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read User|null $verifier
 */
class VendorReport extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => VendorReportStatus::class,
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('files')->useDisk('local');
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * HQ Verify checklist computed from the files.
     *
     * @return array{images: bool, videos: bool, pdf: bool}
     */
    public function checklist(): array
    {
        $mimes = $this->getMedia('files')->pluck('mime_type')->map(fn ($m) => (string) $m);

        return [
            'images' => $mimes->contains(fn (string $m) => str_starts_with($m, 'image/') && $m !== 'image/webp'),
            'videos' => $mimes->contains(fn (string $m) => str_starts_with($m, 'video/') || $m === 'image/webp'),
            'pdf' => $mimes->contains('application/pdf'),
        ];
    }
}
