<?php

namespace App\Models;

use App\Enums\VendorPaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * HQ payment to a vendor for one PO. Receipt (image) and payment advice (PDF)
 * live on the private disk.
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $vendor_id
 * @property int $amount_sen
 * @property VendorPaymentStatus $status
 * @property Carbon|null $payment_date
 * @property string|null $approved_by_name
 * @property string|null $bank
 * @property string|null $reference
 * @property int|null $confirmed_by
 * @property Carbon|null $confirmed_at
 * @property-read PurchaseOrder $purchaseOrder
 * @property-read Vendor $vendor
 * @property-read User|null $confirmedBy
 */
class VendorPayment extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const BANKS = ['Maybank', 'Bank Islam', 'CIMB Bank'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => VendorPaymentStatus::class,
            'payment_date' => 'date',
            'confirmed_at' => 'datetime',
            'amount_sen' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->singleFile()->useDisk('local');
        $this->addMediaCollection('advice')->singleFile()->useDisk('local');
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public static function mediaUrl(Media $media, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('vendors.media', now()->addMinutes($minutes), ['media' => $media->id]);
    }
}
