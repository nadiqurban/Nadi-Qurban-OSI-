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

    /** Super Admin permissions are fixed (always full) so nobody can lock the system out. */
    public function isLockedMatrix(): bool
    {
        return $this->roleName() === RoleName::SuperAdmin;
    }

    public function tagTone(): string
    {
        return $this->roleName()?->tagTone() ?? ($this->tone ?? 'neutral');
    }
}
