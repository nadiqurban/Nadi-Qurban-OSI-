<?php

namespace App\Models;

use App\Enums\RoleName;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $tone
 * @property int $sort
 */
class Role extends SpatieRole
{
    public function roleName(): ?RoleName
    {
        return RoleName::tryFrom($this->name);
    }

    /**
     * Fixed columns: Super Admin always full (nobody can lock the system out);
     * Ejen always none (agents only use the Portal Ejen, never staff modules).
     */
    public function isLockedMatrix(): bool
    {
        return in_array($this->roleName(), [RoleName::SuperAdmin, RoleName::Agent], true);
    }

    public function lockedNote(): string
    {
        return $this->roleName() === RoleName::Agent
            ? 'Ejen hanya menggunakan Portal Ejen — tiada akses ke modul staf.'
            : 'Super Admin sentiasa mempunyai akses penuh ke semua modul.';
    }

    public function tagTone(): string
    {
        return $this->roleName()?->tagTone() ?? ($this->tone ?? 'neutral');
    }
}
