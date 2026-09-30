<?php

namespace App\Livewire\Users;

use App\Actions\Roles\SaveMatrix;
use App\Actions\Roles\SyncRolePermissions;
use App\Actions\Users\CreateUser;
use App\Actions\Users\ForcePasswordChange;
use App\Actions\Users\SendPasswordResetLink;
use App\Actions\Users\SetUserStatus;
use App\Actions\Users\UpdateUser;
use App\Enums\AccessLevel;
use App\Enums\Module;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * @property-read Collection<int, Role> $allRoles
 * @property-read LengthAwarePaginator<int, User> $users
 * @property-read User|null $accessUser
 * @property-read array<int, array{icon: string, tone: string, value: string, label: string}> $stats
 */
#[Layout('layouts::app')]
#[Title('Pengguna & Peranan')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'tab', except: 'pengguna')]
    public string $tab = 'pengguna';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'peranan', except: '')]
    public string $roleFilter = '';

    // Tambah / Edit pengguna
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    /** @var list<string> */
    public array $roles = [];

    public string $status = 'aktif';

    public string $tempPassword = '';

    /** Shown once after creating a user so the admin can hand it over. */
    public ?string $createdNotice = null;

    // Akses & Keselamatan
    public bool $showAccess = false;

    public ?int $accessUserId = null;

    public ?string $accessMessage = null;

    /** @var array<int|string, array<string, string>> role id => [module => F|V|N] */
    public array $matrix = [];

    public bool $matrixSaved = false;

    public function mount(): void
    {
        $this->loadMatrix();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    /** @return Collection<int, Role> */
    #[Computed]
    public function allRoles(): Collection
    {
        return Role::query()->withCount('users')->orderBy('sort')->get();
    }

    /** @return array<int, array{icon: string, tone: string, value: string, label: string}> */
    #[Computed]
    public function stats(): array
    {
        $total = User::query()->count();
        $suspended = User::query()->where('status', UserStatus::Suspended)->count();

        return [
            ['icon' => 'users-three', 'tone' => 'primary', 'value' => number_format($total), 'label' => 'Jumlah Pengguna'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => number_format($total - $suspended), 'label' => 'Aktif'],
            ['icon' => 'shield-check', 'tone' => 'info', 'value' => (string) $this->allRoles->count(), 'label' => 'Peranan'],
            ['icon' => 'lock-key', 'tone' => 'danger', 'value' => number_format($suspended), 'label' => 'Digantung'],
        ];
    }

    /** @return LengthAwarePaginator<int, User> */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with('roles')
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('email', 'like', '%'.$this->search.'%')))
            ->when($this->roleFilter !== '', fn (Builder $q) => $q->role($this->roleFilter))
            ->orderByDesc('last_seen_at')
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function accessUser(): ?User
    {
        return $this->accessUserId ? User::query()->find($this->accessUserId) : null;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Users->managePermission()) ?? false;
    }

    // ---------------------------------------------------------------- users

    public function create(): void
    {
        $this->authorize(Module::Users->managePermission());

        $this->resetForm();
        $this->tempPassword = self::generatePassword();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize(Module::Users->managePermission());

        $user = User::query()->with('roles')->findOrFail($id);
        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = (string) $user->phone;
        $this->roles = $user->roles->pluck('name')->values()->all();
        $this->status = $user->status->value;
        $this->showForm = true;
    }

    public function toggleRole(string $role): void
    {
        $this->roles = in_array($role, $this->roles, true)
            ? array_values(array_diff($this->roles, [$role]))
            : [...$this->roles, $role];
    }

    public function autoPassword(): void
    {
        $this->tempPassword = self::generatePassword();
    }

    public function save(CreateUser $create, UpdateUser $update): void
    {
        $this->authorize(Module::Users->managePermission());

        $roleNames = Role::query()->pluck('name')->all();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in($roleNames)],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'tempPassword' => $this->editingId ? ['nullable'] : ['required', 'string', 'min:8', 'max:64'],
        ], [
            'roles.required' => 'Pilih sekurang-kurangnya satu peranan.',
        ], [
            'name' => 'nama penuh', 'email' => 'emel', 'phone' => 'no. telefon',
            'roles' => 'peranan', 'tempPassword' => 'kata laluan sementara',
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        if ($this->editingId) {
            $user = User::query()->with('roles')->findOrFail($this->editingId);
            $update->handle($user, ['name' => $validated['name'], 'email' => $validated['email']], $this->roles, UserStatus::from($this->status), $actor);
            $user->forceFill(['phone' => $validated['phone'] ?: null])->save();
            $this->createdNotice = null;
        } else {
            $user = $create->handle(
                ['name' => $validated['name'], 'email' => $validated['email'], 'phone' => $validated['phone'] ?: null],
                $this->roles,
                $this->tempPassword,
                $actor,
            );
            $this->createdNotice = "Pengguna {$user->name} dicipta. Kata laluan sementara: {$this->tempPassword} — pengguna perlu menukarnya semasa log masuk pertama.";
        }

        $this->showForm = false;
        $this->resetForm();
        unset($this->users, $this->stats, $this->allRoles);
    }

    public function openAccess(int $id): void
    {
        $this->authorize(Module::Users->managePermission());

        $this->accessUserId = User::query()->findOrFail($id)->id;
        $this->accessMessage = null;
        $this->showAccess = true;
    }

    public function sendReset(SendPasswordResetLink $send): void
    {
        $this->authorize(Module::Users->managePermission());
        $user = $this->accessUser ?? abort(404);

        $send->handle($user, $this->actor());
        $this->accessMessage = 'Pautan set semula kata laluan dihantar ke '.$user->email.'.';
    }

    public function forceChange(ForcePasswordChange $force): void
    {
        $this->authorize(Module::Users->managePermission());
        $user = $this->accessUser ?? abort(404);

        $force->handle($user, $this->actor());
        $this->accessMessage = $user->name.' perlu menukar kata laluan pada log masuk seterusnya.';
    }

    public function toggleSuspend(SetUserStatus $setStatus): void
    {
        $this->authorize(Module::Users->managePermission());
        $user = $this->accessUser ?? abort(404);

        $next = $user->isSuspended() ? UserStatus::Active : UserStatus::Suspended;
        $setStatus->handle($user, $next, $this->actor());

        $this->accessMessage = $next === UserStatus::Suspended
            ? 'Akaun '.$user->name.' telah digantung dan semua sesinya ditamatkan.'
            : 'Akaun '.$user->name.' telah diaktifkan semula.';
        unset($this->accessUser, $this->users, $this->stats);
    }

    // ---------------------------------------------------------------- matrix

    public function cycle(int $roleId, string $module): void
    {
        $this->authorize(Module::Users->managePermission());

        $role = $this->allRoles->firstWhere('id', $roleId);

        if (! $role || $role->isLockedMatrix() || ! isset($this->matrix[$roleId][$module])) {
            return;
        }

        $this->matrix[$roleId][$module] = AccessLevel::from($this->matrix[$roleId][$module])->next()->value;
        $this->matrixSaved = false;
    }

    public function saveMatrix(SaveMatrix $save): void
    {
        $this->authorize(Module::Users->managePermission());

        $save->handle($this->matrix, $this->actor());
        $this->loadMatrix();
        $this->matrixSaved = true;
    }

    private function loadMatrix(): void
    {
        $this->matrix = Role::query()->with('permissions')->orderBy('sort')->get()
            ->mapWithKeys(fn (Role $role) => [$role->id => collect(SyncRolePermissions::levelsFor($role))->map->value->all()])
            ->all();
    }

    // ---------------------------------------------------------------- helpers

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'email', 'phone', 'roles', 'tempPassword');
        $this->status = UserStatus::Active->value;
        $this->resetValidation();
    }

    public static function generatePassword(): string
    {
        // Guaranteed upper, lower, digit and symbol so it passes the password policy.
        return str_shuffle('Nq'.random_int(2, 9).'@'.Str::password(8, symbols: false));
    }

    public function render(): mixed
    {
        return view('livewire.users.index', [
            'modules' => Module::cases(),
            'roleNames' => RoleName::cases(),
        ]);
    }
}
