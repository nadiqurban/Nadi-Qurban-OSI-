<?php

namespace App\Models;

use App\Enums\LoginStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $email
 * @property LoginStatus $status
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $browser
 * @property string|null $platform
 * @property string|null $device
 * @property string|null $location
 * @property string|null $session_id
 * @property Carbon $created_at
 */
class LoginHistory extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => LoginStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** "Chrome · Windows 11" */
    public function deviceLabel(): string
    {
        return collect([$this->browser, $this->platform])->filter()->implode(' · ') ?: 'Peranti tidak dikenali';
    }

    /** IP with last two octets masked, as in the design: "202.184.x.x". */
    public function maskedIp(): string
    {
        if (! $this->ip_address) {
            return '-';
        }

        if (str_contains($this->ip_address, ':')) {
            return implode(':', array_slice(explode(':', $this->ip_address), 0, 3)).':…';
        }

        $parts = explode('.', $this->ip_address);

        return $parts[0].'.'.($parts[1] ?? 'x').'.x.x';
    }

    public function isCurrentSession(): bool
    {
        return $this->session_id !== null && $this->session_id === session()->getId();
    }
}
