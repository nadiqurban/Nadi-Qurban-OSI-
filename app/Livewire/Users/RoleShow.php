<?php

namespace App\Livewire\Users;

use App\Actions\Roles\SyncRolePermissions;
use App\Actions\Roles\UpdateRole;
use App\Enums\AccessLevel;
use App\Enums\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Butiran Peranan')]
class RoleShow extends Component
{
    public Role $role;

    public bool $showEdit = false;

    public string $name = '';

    public string $description = '';

    /** @var array<string, string> module => F|V|N */
    public array $levels = [];

    public function mount(Role $role): void
    {
        $this->role = $role->load('permissions');
    }

    public function openEdit(): void
    {
        $this->authorize(Module::Users->managePermission());

        $this->name = $this->role->name;
        $this->description = (string) $this->role->description;
        $this->levels = collect(SyncRolePermissions::levelsFor($this->role))->map->value->all();
        $this->resetValidation();
        $this->showEdit = true;
    }

    public function cycle(string $module): void
    {
        $this->authorize(Module::Users->managePermission());

        if ($this->role->isLockedMatrix() || ! isset($this->levels[$module])) {
            return;
        }

        $this->levels[$module] = AccessLevel::from($this->levels[$module])->next()->value;
    }

    public function save(UpdateRole $update): void
    {
        $this->authorize(Module::Users->managePermission());

        $this->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($this->role->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ], attributes: ['name' => 'nama peranan', 'description' => 'keterangan']);

        /** @var User $actor */
        $actor = auth()->user();
        $update->handle($this->role, $this->name, $this->description ?: null, $this->levels, $actor);

        $this->role->refresh()->load('permissions');
        $this->showEdit = false;
    }

    public function render(): mixed
    {
        $levels = SyncRolePermissions::levelsFor($this->role);

        return view('livewire.users.role-show', [
            'members' => User::query()->role($this->role->name)->orderBy('name')->get(),
            'perms' => collect(Module::cases())->map(fn (Module $m) => ['module' => $m, 'level' => $levels[$m->value]]),
            'canManage' => auth()->user()?->can(Module::Users->managePermission()) ?? false,
        ])->title($this->role->name.' · Peranan');
    }
}
