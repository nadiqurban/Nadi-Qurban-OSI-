<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property DiscountType $type
 * @property int $value Percent: whole percent. Fixed: sen.
 * @property int|null $usage_limit
 * @property int $used_count
 * @property int $total_discount_sen
 * @property Carbon|null $expires_at
 * @property bool $is_active
 */
class PromoCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'description', 'type', 'value', 'usage_limit', 'used_count',
        'total_discount_sen', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'total_discount_sen' => 'integer',
            'expires_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Discount in sen for a subtotal in sen, by type. Fixes the prototype bug where
     * "RM 50" was applied as 50%: fixed amounts are deducted as-is (capped at the subtotal).
     */
    public function discountFor(int $subtotalSen): int
    {
        if ($subtotalSen <= 0) {
            return 0;
        }

        return match ($this->type) {
            DiscountType::Percent => intdiv($subtotalSen * min(100, $this->value), 100),
            DiscountType::Fixed => min($this->value, $subtotalSen),
        };
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->endOfDay()->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /** Active, not expired and under its usage limit. */
    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->isExhausted();
    }

    /** Human label as in the design: "10%" / "RM 50". */
    public function discountLabel(): string
    {
        return $this->type === DiscountType::Percent ? $this->value.'%' : rm($this->value);
    }

    /**
     * Codes that can be applied right now (active, not expired, under limit).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUsable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->where(fn (Builder $q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    /**
     * "Tamat Tempoh" tab: expired, exhausted or switched off.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeEnded(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('is_active', false)
            ->orWhereDate('expires_at', '<', today())
            ->orWhere(fn (Builder $w) => $w->whereNotNull('usage_limit')->whereColumn('used_count', '>=', 'usage_limit')));
    }

    public static function findUsable(?string $code): ?self
    {
        if (! $code) {
            return null;
        }

        return self::query()->usable()->where('code', mb_strtoupper(trim($code)))->first();
    }
}
