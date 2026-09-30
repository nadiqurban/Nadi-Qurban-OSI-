<?php

namespace App\Models;

use App\Enums\PoStatus;
use App\Enums\VendorLevel;
use App\Enums\VendorStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Implementation vendor (Vendor.dc.html). Code "SP 001" is editable; vendor_no
 * "VND-2010" is the system id shown on the profile.
 *
 * @property int $id
 * @property string $code
 * @property string|null $vendor_no
 * @property string $name
 * @property string|null $company
 * @property string|null $supplier
 * @property int $country_id
 * @property VendorLevel|null $level
 * @property VendorStatus $status
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $pic_name
 * @property list<string>|null $animals
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $bank_holder
 * @property string|null $swift
 * @property string|null $bank_address
 * @property int|null $rank
 * @property string|null $rating
 * @property-read Country $country
 * @property-read int|null $active_po_count
 * @property-read int|null $completed_po_count
 * @property-read int|null $counted_po_count
 * @property-read int|null $open_po
 * @property-read Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read Collection<int, VendorRankHistory> $rankHistories
 */
class Vendor extends Model
{
    use SoftDeletes;

    public const ANIMALS = ['Lembu', 'Kambing', 'Unta'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'level' => VendorLevel::class,
            'animals' => 'array',
            'rating' => 'decimal:1',
            'rank' => 'integer',
        ];
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return HasMany<Allocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    /** @return HasMany<PurchaseOrder, $this> */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->latest('id');
    }

    /** @return HasMany<VendorPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(VendorPayment::class);
    }

    /** @return HasMany<VendorRankHistory, $this> */
    public function rankHistories(): HasMany
    {
        return $this->hasMany(VendorRankHistory::class)->latest('created_at')->latest('id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** "Uganda Charity" → "UC" */
    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)) ?: [])->take(2)
            ->map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    /** Avatar tile colours rotate like the design (avPairs). */
    public function avatarClasses(): string
    {
        return ['bg-primary-soft text-primary', 'bg-gold-soft text-gold', 'bg-info-soft text-info', 'bg-success-soft text-success', 'bg-purple-soft text-purple', 'bg-[#FBE9E7] text-[#D9531E]'][($this->id - 1) % 6];
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            VendorStatus::Active => 'success',
            VendorStatus::Pending => 'warning',
            VendorStatus::Suspended => 'danger',
        };
    }

    /** Company name for documents (falls back to the vendor name). */
    public function companyName(): string
    {
        return $this->company ?: $this->name;
    }

    /**
     * Vendors that can receive orders: status Aktif (Agihan Negara dropdown).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VendorStatus::Active)->orderBy('name');
    }

    /**
     * Card counters: PO Aktif, PO Selesai, PO counted for "Kadar Siap".
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithPoStats(Builder $query): Builder
    {
        $active = array_map(fn (PoStatus $s) => $s->value, array_filter(PoStatus::cases(), fn (PoStatus $s) => $s->isActive()));

        return $query->withCount([
            'purchaseOrders as active_po_count' => fn (Builder $q) => $q->whereIn('status', $active),
            'purchaseOrders as completed_po_count' => fn (Builder $q) => $q->where('status', PoStatus::Completed),
            'purchaseOrders as counted_po_count' => fn (Builder $q) => $q->whereNotIn('status', [PoStatus::Draft, PoStatus::Cancelled]),
        ]);
    }

    /** "96%" — completed ÷ issued (excl. drafts & cancelled); "—" without POs. */
    public function completionLabel(): string
    {
        $counted = (int) ($this->counted_po_count ?? 0);

        return $counted > 0 ? round(((int) ($this->completed_po_count ?? 0)) / $counted * 100).'%' : '—';
    }
}
