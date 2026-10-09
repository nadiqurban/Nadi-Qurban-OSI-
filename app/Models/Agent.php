<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Sales agent (Pengurusan Ejen.dc.html): a User with role "Ejen" + this profile.
 * Name, email, phone and password live on the user; status = user status.
 *
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property string $slug
 * @property string|null $gender
 * @property Carbon|null $birth_date
 * @property string|null $district
 * @property string|null $state
 * @property string|null $bank_name
 * @property string|null $bank_account_name
 * @property string|null $bank_account_no
 * @property-read User $user
 */
class Agent extends Model
{
    public const GENDERS = ['Lelaki', 'Perempuan'];

    public const STATES = [
        'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis', 'Pulau Pinang',
        'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya',
    ];

    public const BANKS = [
        'Maybank', 'CIMB Bank', 'Bank Islam', 'RHB Bank', 'Public Bank', 'Hong Leong Bank', 'AmBank', 'Bank Rakyat',
        'BSN', 'Affin Bank', 'Bank Muamalat', 'Alliance Bank', 'OCBC Bank', 'Agrobank',
    ];

    protected $fillable = [
        'user_id', 'code', 'slug', 'gender', 'birth_date', 'district', 'state',
        'bank_name', 'bank_account_name', 'bank_account_no',
    ];

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<AgentClick, $this> */
    public function clicks(): HasMany
    {
        return $this->hasMany(AgentClick::class);
    }

    public function isActive(): bool
    {
        return ! $this->user->isSuspended();
    }

    /** Public sales link by agent code, e.g. https://www.appnadiqurban.my/tempah/NQ001 (old name links still work). */
    public function shareUrl(): string
    {
        return route('booking.agent', $this->code);
    }

    /** "Aiman bin Zulkifli" → "aiman-zulkifli" (first two meaningful words), unique among agents. */
    public static function makeSlug(string $name, ?int $ignoreId = null): string
    {
        $words = collect(explode(' ', Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString()))
            ->reject(fn (string $w) => in_array($w, ['bin', 'binti', 'bt', 'b', 'a/l', 'a/p'], true))
            ->take(2);

        $base = $words->implode('-') ?: 'ejen';
        $base = $base === 'resit' ? 'resit-ejen' : $base;   // /tempah/resit/... is the receipt
        $slug = $base;
        $n = 2;

        while (self::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }

    /** Count one visit to the agent's link (per day row). */
    public function recordClick(): void
    {
        $row = AgentClick::query()->firstOrCreate(['agent_id' => $this->id, 'date' => today()->toDateString()], ['clicks' => 0]);
        $row->increment('clicks');
    }
}
