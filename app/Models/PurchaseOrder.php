<?php

namespace App\Models;

use App\Enums\PoStatus;
use App\Enums\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Purchase Order to a vendor. Amounts are stored in the PO currency's minor unit
 * (sen for RM, cents for USD) with the RM exchange-rate snapshot taken at creation.
 *
 * @property int $id
 * @property string $po_no
 * @property int $vendor_id
 * @property Service $service
 * @property string $animal_label
 * @property int $quantity
 * @property string $currency
 * @property string $exchange_rate
 * @property int $unit_price_minor
 * @property int $total_minor
 * @property int $total_rm_sen
 * @property Carbon|null $implementation_date
 * @property PoStatus $status
 * @property string|null $billing_address
 * @property string|null $shipping_address
 * @property string|null $notes
 * @property string|null $payment_terms
 * @property string|null $reference
 * @property Carbon|null $sent_at
 * @property Carbon|null $accepted_at
 * @property int|null $accepted_by
 * @property Carbon|null $in_progress_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property-read Vendor $vendor
 * @property-read User|null $acceptedBy
 * @property-read User|null $creator
 * @property-read VendorPayment|null $payment
 * @property-read VendorReport|null $report
 */
class PurchaseOrder extends Model
{
    use SoftDeletes;

    public const CURRENCIES = ['RM', 'USD'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'service' => Service::class,
            'status' => PoStatus::class,
            'implementation_date' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'in_progress_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'quantity' => 'integer',
            'unit_price_minor' => 'integer',
            'total_minor' => 'integer',
            'total_rm_sen' => 'integer',
        ];
    }

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasOne<VendorPayment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(VendorPayment::class);
    }

    /** @return HasOne<VendorReport, $this> */
    public function report(): HasOne
    {
        return $this->hasOne(VendorReport::class);
    }

    /** RM value of an amount in this PO's currency (minor units). */
    public function toRmSen(int $minor): int
    {
        return (int) round($minor * (float) $this->exchange_rate);
    }

    /**
     * Format an amount (in this PO's currency, minor units) in the requested display currency.
     * "RM 45,000" / "USD 9,574" — whole units like the design.
     */
    public function money(int $minor, string $display, float $usdRate): string
    {
        $rmSen = $this->toRmSen($minor);
        $value = $display === 'USD' ? $rmSen / 100 / max($usdRate, 0.0001) : $rmSen / 100;

        return $display.' '.number_format($value, $value == floor($value) ? 0 : 2);
    }
}
