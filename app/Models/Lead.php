<?php

namespace App\Models;

use App\Enums\LeadStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Sales CRM prospect.
 *
 * @property int $id
 * @property string $lead_no
 * @property string $name
 * @property string|null $company
 * @property string|null $phone
 * @property string|null $email
 * @property string $service
 * @property string|null $package
 * @property int $value_sen
 * @property LeadStage $stage
 * @property int $position
 * @property string|null $source
 * @property int|null $participants
 * @property string|null $notes
 * @property int|null $owner_id
 * @property int|null $order_id
 * @property Carbon|null $stage_changed_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $done_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User|null $owner
 * @property-read Order|null $order
 * @property-read Collection<int, LeadActivity> $activities
 */
class Lead extends Model
{
    use SoftDeletes;

    public const SERVICES = ['Qurban', 'Aqiqah', 'Dam', 'Nazar Haiwan', 'Korporat'];

    public const PACKAGES = ['Delima', 'Zamrud', 'Topaz', 'Nilam', 'Mutiara'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'stage' => LeadStage::class,
            'value_sen' => 'integer',
            'position' => 'integer',
            'participants' => 'integer',
            'stage_changed_at' => 'datetime',
            'closed_at' => 'datetime',
            'done_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    /** @return HasMany<LeadActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('created_at')->latest('id');
    }

    /** Service tag tone (design svcTag; others neutral). */
    public function serviceTone(): string
    {
        return match ($this->service) {
            'Qurban' => 'primary',
            'Aqiqah' => 'gold-ink',
            'Dam' => 'info',
            'Korporat' => 'purple',
            default => 'neutral',
        };
    }

    /** Days in the current stage, shown as "2h" (hari) on cards. */
    public function daysLabel(): string
    {
        return (int) ($this->stage_changed_at ?? $this->created_at)->diffInDays(now()).'h';
    }
}
