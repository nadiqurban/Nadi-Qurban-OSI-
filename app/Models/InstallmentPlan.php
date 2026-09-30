<?php

namespace App\Models;

use App\Enums\Animal;
use App\Enums\InstallmentPayMethod;
use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Enums\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Bayaran Ansuran plan. Prices are a snapshot in sen; the order number is reserved
 * at creation and becomes the Order when the plan is completed and sent ("Hantar").
 *
 * @property int $id
 * @property string $order_no
 * @property int $customer_id
 * @property int|null $product_id
 * @property string $product_name
 * @property Service $service
 * @property Animal $animal
 * @property string $package_name
 * @property int $country_id
 * @property int $quantity
 * @property list<string>|null $participant_names
 * @property int $year
 * @property Carbon|null $implementation_date
 * @property int $unit_price_sen
 * @property int $subtotal_sen
 * @property int $discount_sen
 * @property int $total_sen
 * @property int $deposit_sen
 * @property int|null $promo_code_id
 * @property string|null $promo_code
 * @property int $months
 * @property int $monthly_sen
 * @property InstallmentPayMethod $payment_method
 * @property InstallmentPlanStatus $status
 * @property string|null $cancel_reason
 * @property Carbon|null $cancelled_at
 * @property string $pay_token
 * @property Carbon|null $sent_at
 * @property int|null $order_id
 * @property Carbon $created_at
 * @property-read Customer $customer
 * @property-read Country $country
 * @property-read Order|null $order
 * @property-read Collection<int, Installment> $installments
 */
class InstallmentPlan extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'service' => Service::class,
            'animal' => Animal::class,
            'payment_method' => InstallmentPayMethod::class,
            'status' => InstallmentPlanStatus::class,
            'participant_names' => 'array',
            'implementation_date' => 'date',
            'cancelled_at' => 'datetime',
            'sent_at' => 'datetime',
            'quantity' => 'integer',
            'months' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('deposit_proof')->singleFile()->useDisk('local');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return HasMany<Installment, $this> */
    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('seq');
    }

    /** @return HasMany<PaymentGatewayTransaction, $this> */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentGatewayTransaction::class);
    }

    /** Balance financed by the instalments (total − deposit). */
    public function financedSen(): int
    {
        return max(0, $this->total_sen - $this->deposit_sen);
    }

    public function paidCount(): int
    {
        return $this->installments->where('status', InstallmentStatus::Paid)->count();
    }

    public function paidSen(): int
    {
        return (int) $this->installments->where('status', InstallmentStatus::Paid)->sum('amount_sen');
    }

    public function balanceSen(): int
    {
        return (int) $this->installments->where('status', InstallmentStatus::Unpaid)->sum('amount_sen');
    }

    public function progressPct(): int
    {
        return $this->months > 0 ? (int) round($this->paidCount() / $this->months * 100) : 0;
    }

    public function isFullyPaid(): bool
    {
        return $this->installments->isNotEmpty() && $this->installments->every(fn (Installment $i) => $i->status === InstallmentStatus::Paid);
    }

    public function nextDue(): ?Installment
    {
        return $this->installments->firstWhere('status', InstallmentStatus::Unpaid);
    }

    /** "Qurban — Lembu" */
    public function ibadahLabel(): string
    {
        return $this->service->label().' — '.$this->animal->label();
    }

    /** Receipt number NQRCPT + last 6 digits of the order no. */
    public function receiptNo(): string
    {
        return 'NQRCPT'.substr((string) preg_replace('/\D+/', '', $this->order_no), -6);
    }

    public function portalUrl(): string
    {
        return route('installments.portal', $this->pay_token);
    }

    /**
     * Participant names, one per share (first defaults to the customer).
     *
     * @return list<string>
     */
    public function participantList(): array
    {
        $names = $this->participant_names ?? [];

        return array_map(
            fn (int $i) => trim((string) ($names[$i] ?? '')) ?: ($i === 0 ? $this->customer->name : 'Peserta '.($i + 1)),
            range(0, max(0, $this->quantity - 1)),
        );
    }

    /** Pre-filled WhatsApp reminder (prototype message). */
    public function whatsappUrl(): string
    {
        $phone = (string) preg_replace('/^0/', '60', (string) preg_replace('/\D+/', '', $this->customer->phone));
        $message = 'Salam '.$this->customer->name.', ini peringatan mesra daripada Nadi Qurban. Baki ansuran '.$this->order_no
            .' ('.rm($this->total_sen).') masih menunggu bayaran. Sila buat pembayaran ansuran bulanan ('.rm($this->monthly_sen)
            .'). Terima kasih.'."\n".$this->portalUrl();

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
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

        return $query->where(fn (Builder $q) => $q
            ->where('order_no', 'like', "%{$term}%")
            ->orWhereHas('customer', fn (Builder $c) => $c->where('name', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")));
    }
}
