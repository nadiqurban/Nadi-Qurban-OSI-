<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $position
 * @property string|null $avatar_path
 * @property int|null $vendor_id
 * @property UserStatus $status
 * @property bool $must_change_password
 * @property int $failed_login_attempts
 * @property Carbon|null $locked_until
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $password_changed_at
 * @property string|null $two_factor_secret
 * @property Carbon|null $two_factor_confirmed_at
 * @property-read string $role_label
 * @property-read string|null $avatar_url
 */
#[Fillable(['name', 'email', 'phone', 'position', 'password', 'status', 'must_change_password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const MAX_LOGIN_ATTEMPTS = 5;

    public const LOCKOUT_MINUTES = 15;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'must_change_password' => 'boolean',
            'locked_until' => 'datetime',
            'last_seen_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return HasMany<LoginHistory, $this> */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class)->latest('created_at');
    }

    /**
     * Vendor PIC users are linked to their vendor.
     *
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isVendorPic(): bool
    {
        return $this->vendor_id !== null;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin->value);
    }

    /**
     * Label shown under the user's name (sidebar/header): highest-priority role.
     *
     * @return Attribute<string, never>
     */
    protected function roleLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $names = $this->roles->pluck('name');

            foreach (RoleName::cases() as $role) {
                if ($names->contains($role->value)) {
                    return $role->value;
                }
            }

            return $names->first() ?? '—';
        });
    }

    /** @return Attribute<string|null, never> */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null);
    }
}
