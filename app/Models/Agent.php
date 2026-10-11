<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
 * @property string|null $registration_status null = approved, "menunggu" (Pendaftaran Baharu) or "ditolak"
 * @property-read User $user
 */
class Agent extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const PENDING = 'menunggu';

    public const REJECTED = 'ditolak';

    public const PHOTO_MIMES = ['image/jpeg', 'image/png'];

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
        'bank_name', 'bank_account_name', 'bank_account_no', 'registration_status',
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

    public function registerMediaCollections(): void
    {
        // Gambar Terkini (passport style, 320×320 JPEG) — private disk, served to HQ only.
        $this->addMediaCollection('photo')->singleFile()->useDisk('local')->acceptsMimeTypes(self::PHOTO_MIMES);
    }

    public function photo(): ?Media
    {
        return $this->getFirstMedia('photo');
    }

    public function isPending(): bool
    {
        return $this->registration_status === self::PENDING;
    }

    public function isRejected(): bool
    {
        return $this->registration_status === self::REJECTED;
    }

    public function isActive(): bool
    {
        return $this->registration_status === null && ! $this->user->isSuspended();
    }

    /** Status label + tone for Pengurusan Ejen: Aktif / Tidak Aktif / Menunggu / Ditolak. */
    public function statusLabel(): string
    {
        return match (true) {
            $this->isPending() => 'Menunggu',
            $this->isRejected() => 'Ditolak',
            $this->isActive() => 'Aktif',
            default => 'Tidak Aktif',
        };
    }

    public function statusClasses(): string
    {
        return match (true) {
            $this->isPending() => 'bg-warning-soft text-warning',
            $this->isActive() => 'bg-success-soft text-success',
            default => 'bg-danger-soft text-danger',
        };
    }

    /** "Aiman Zulkifli" → "AZ" (avatar without a photo). */
    public function initials(): string
    {
        return collect(explode(' ', $this->user->name))->filter()->take(2)
            ->map(fn (string $w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    /**
     * Next free agent code for a self-registration: initials of the first two meaningful
     * words + the next running number (Pendaftaran Ejen.dc.html), e.g. "Siti Aminah" → "SA04".
     */
    public static function nextCode(string $name): string
    {
        $words = collect(explode(' ', Str::of($name)->ascii()->upper()->replaceMatches('/[^A-Z ]+/', ' ')->squish()->toString()))
            ->reject(fn (string $w) => in_array($w, ['BIN', 'BINTI', 'BT', 'B', 'AL', 'AP'], true))->values();
        $initials = $words->count() > 1 ? $words[0][0].$words[1][0] : mb_substr((string) ($words[0] ?? 'EJ'), 0, 2);
        $initials = str_pad($initials, 2, 'J');

        $n = (int) self::query()->pluck('code')
            ->map(fn (string $c) => (int) preg_replace('/\D+/', '', $c))->max();

        do {
            $code = $initials.str_pad((string) ++$n, 2, '0', STR_PAD_LEFT);
        } while (self::query()->where('code', $code)->exists());

        return $code;
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
