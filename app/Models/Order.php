<?php

namespace App\Models;

use App\Enums\Animal;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\Service;
use App\Models\Scopes\VendorPicOrderScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * One order moving through the pipeline (PRD §5). Prices are a snapshot in sen.
 *
 * @property int $id
 * @property string $order_no
 * @property string $tracking_no
 * @property string $tracking_token
 * @property int $customer_id
 * @property int|null $product_id
 * @property string $product_name
 * @property Service $service
 * @property Animal $animal
 * @property string $package_name
 * @property int $country_id
 * @property int $quantity
 * @property int $year
 * @property Carbon|null $implementation_date
 * @property int $unit_price_sen
 * @property int $subtotal_sen
 * @property int $discount_sen
 * @property int $total_sen
 * @property int|null $promo_code_id
 * @property string|null $promo_code
 * @property PaymentMethod $payment_method
 * @property bool $is_instalment
 * @property OrderStatus $status
 * @property OrderStage $stage
 * @property Carbon|null $accepted_at
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon $created_at
 * @property-read Customer $customer
 * @property-read Country $country
 * @property-read Product|null $product
 * @property-read Payment|null $payment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderParticipant> $participants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderStageHistory> $stageHistories
 * @property-read AkadRecord|null $akad
 * @property-read Allocation|null $allocation
 * @property-read ExecutionReport|null $executionReport
 * @property-read Shipment|null $shipment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Certificate> $certificates
 */
#[ScopedBy([VendorPicOrderScope::class])]
class Order extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'service' => Service::class,
            'animal' => Animal::class,
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'stage' => OrderStage::class,
            'is_instalment' => 'boolean',
            'implementation_date' => 'date',
            'accepted_at' => 'datetime',
            'quantity' => 'integer',
            'year' => 'integer',
            'unit_price_sen' => 'integer',
            'subtotal_sen' => 'integer',
            'discount_sen' => 'integer',
            'total_sen' => 'integer',
        ];
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /** @return BelongsTo<PromoCode, $this> */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class)->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Latest payment record (orders are paid in full or come from a completed instalment plan).
     *
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<OrderParticipant, $this> */
    public function participants(): HasMany
    {
        return $this->hasMany(OrderParticipant::class)->orderBy('position');
    }

    /** @return HasMany<OrderStageHistory, $this> */
    public function stageHistories(): HasMany
    {
        return $this->hasMany(OrderStageHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasOne<AkadRecord, $this> */
    public function akad(): HasOne
    {
        return $this->hasOne(AkadRecord::class);
    }

    /** @return HasOne<Allocation, $this> */
    public function allocation(): HasOne
    {
        return $this->hasOne(Allocation::class);
    }

    /** @return HasOne<ExecutionReport, $this> */
    public function executionReport(): HasOne
    {
        return $this->hasOne(ExecutionReport::class);
    }

    /** @return HasOne<Shipment, $this> */
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    /** @return HasMany<Certificate, $this> */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class)->orderBy('position');
    }

    /**
     * Participant names, one per share; falls back to the customer's name for the first.
     *
     * @return Collection<int, string>
     */
    public function participantNames(): Collection
    {
        $names = $this->participants->pluck('name', 'position');

        return collect(range(1, $this->quantity))->map(
            fn (int $i) => (string) ($names->get($i) ?: ($i === 1 ? $this->customer->name : '')),
        );
    }

    /** Public tracking link (Phase 4 /jejak). The token unmasks participant names. */
    public function trackingUrl(): string
    {
        return url('/jejak').'?'.http_build_query(['track' => $this->tracking_no, 't' => $this->tracking_token]);
    }

    /** "Qurban — Lembu" */
    public function ibadahLabel(): string
    {
        return $this->service->label().' — '.$this->animal->label();
    }

    /**
     * Vendor PIC users only see orders allocated to their own vendor.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user?->isVendorPic()) {
            return $query;
        }

        return $query->whereHas('allocation', fn (Builder $a) => $a->where('vendor_id', $user->vendor_id));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $digits = preg_replace('/\D+/', '', $term) ?? '';

        return $query->where(fn (Builder $q) => $q
            ->where('order_no', 'like', "%{$term}%")
            ->orWhere('tracking_no', 'like', "%{$term}%")
            ->orWhereHas('customer', fn (Builder $c) => $c
                ->where('name', 'like', "%{$term}%")
                ->when(strlen($digits) >= 4, fn (Builder $p) => $p->orWhereRaw(
                    "REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '+', '') LIKE ?",
                    ["%{$digits}%"],
                ))));
    }
}
