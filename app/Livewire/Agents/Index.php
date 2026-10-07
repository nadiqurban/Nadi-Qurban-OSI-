<?php

namespace App\Livewire\Agents;

use App\Actions\Agents\DeleteAgent;
use App\Actions\Agents\SaveAgent;
use App\Actions\Users\SetUserStatus;
use App\Enums\Module;
use App\Enums\UserStatus;
use App\Exports\TableExport;
use App\Livewire\Users\Index as UsersIndex;
use App\Models\Agent;
use App\Models\User;
use App\Support\AgentStats;
use App\Support\Period;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pengurusan Ejen (Pengurusan Ejen.dc.html): agents as login users of the Portal Ejen,
 * sales & commission per period (Top 10), commission invoice (A4 PDF) and Excel export.
 *
 * @property-read EloquentCollection<int, Agent> $agents
 * @property-read Collection<int, array{agent: Agent, count: int, sales_sen: int, commission_sen: int}> $performance
 * @property-read Agent|null $viewing
 */
#[Layout('layouts::app')]
#[Title('Pengurusan Ejen')]
class Index extends Component
{
    #[Url(as: 'tempoh', except: 'all')]
    public string $period = 'all';

    #[Url(as: 'tarikh', except: '')]
    public string $refDate = '';

    public string $fromDate = '';

    public string $toDate = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $viewId = null;

    public bool $showView = false;

    // Tambah / Edit Ejen
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $gender = 'Lelaki';

    public string $birthDate = '';

    public string $district = '';

    public string $state = 'Selangor';

    public string $bankName = 'Maybank';

    public string $bankAccountName = '';

    public string $bankAccountNo = '';

    public function mount(): void
    {
        $this->refDate = $this->refDate ?: today()->toDateString();
        $this->fromDate = $this->fromDate ?: today()->startOfMonth()->toDateString();
        $this->toDate = $this->toDate ?: today()->toDateString();
    }

    public function periodFilter(): Period
    {
        return new Period($this->period, $this->refDate ?: null, $this->fromDate ?: null, $this->toDate ?: null);
    }

    /** @return EloquentCollection<int, Agent> */
    #[Computed]
    public function agents(): EloquentCollection
    {
        return Agent::query()->with('user')->get();
    }

    /** @return Collection<int, array{agent: Agent, count: int, sales_sen: int, commission_sen: int}> */
    #[Computed]
    public function performance(): Collection
    {
        return AgentStats::perAgent($this->agents, $this->periodFilter());
    }

    /** @return Collection<int, array{agent: Agent, count: int, sales_sen: int, commission_sen: int}> */
    public function rows(): Collection
    {
        $q = mb_strtolower(trim($this->search));

        return $this->performance
            ->filter(fn (array $r) => $q === '' || str_contains(mb_strtolower(implode(' ', [
                $r['agent']->code, $r['agent']->user->name, $r['agent']->user->email, $r['agent']->user->phone,
            ])), $q))
            ->sortBy(fn (array $r) => $r['agent']->code)
            ->values();
    }

    #[Computed]
    public function viewing(): ?Agent
    {
        return $this->viewId ? $this->agents->firstWhere('id', $this->viewId) : null;
    }

    public function view(int $id): void
    {
        $this->viewId = $this->agents->firstWhere('id', $id)?->id;
        $this->showView = $this->viewId !== null;
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Agents->managePermission()) ?? false;
    }

    public function setPeriod(string $key): void
    {
        $this->period = array_key_exists($key, Period::KEYS) ? $key : 'all';
        unset($this->performance);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['refDate', 'fromDate', 'toDate'], true)) {
            if ($property === 'refDate' && $this->period === 'all') {
                $this->period = 'month';
            }
            unset($this->performance);
        }
    }

    /**
     * "Semua Bulan" + the last 12 months, like the design's month picker.
     *
     * @return array<string, string>
     */
    public function monthOptions(): array
    {
        $months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
        $opts = ['__all' => 'Semua Bulan'];

        foreach (range(0, 11) as $i) {
            $d = today()->startOfMonth()->subMonthsNoOverflow($i);
            $opts[$d->format('Y-m')] = $months[$d->month - 1].' '.$d->year;
        }

        return $opts;
    }

    public function pickMonth(string $value): void
    {
        if ($value === '__all') {
            $this->period = 'all';
        } elseif (preg_match('/^\d{4}-\d{2}$/', $value)) {
            $this->period = 'month';
            $this->refDate = $value.'-01';
        }
        unset($this->performance);
    }

    // ---------------------------------------------------------------- form

    public function create(): void
    {
        $this->authorize(Module::Agents->managePermission());

        $this->resetForm();
        $this->password = UsersIndex::generatePassword();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize(Module::Agents->managePermission());

        $a = Agent::query()->with('user')->findOrFail($id);
        $this->resetForm();
        $this->showView = false;
        $this->editingId = $a->id;
        $this->code = $a->code;
        $this->name = $a->user->name;
        $this->email = $a->user->email;
        $this->phone = (string) $a->user->phone;
        $this->gender = $a->gender ?? 'Lelaki';
        $this->birthDate = $a->birth_date?->toDateString() ?? '';
        $this->district = (string) $a->district;
        $this->state = $a->state ?? 'Selangor';
        $this->bankName = $a->bank_name ?? 'Maybank';
        $this->bankAccountName = (string) $a->bank_account_name;
        $this->bankAccountNo = (string) $a->bank_account_no;
        $this->showForm = true;
    }

    public function autoPassword(): void
    {
        $this->password = UsersIndex::generatePassword();
    }

    public function save(SaveAgent $save): void
    {
        $this->authorize(Module::Agents->managePermission());

        $agent = $this->editingId ? Agent::query()->with('user')->findOrFail($this->editingId) : null;
        $this->code = mb_strtoupper(trim($this->code));
        $this->password = trim($this->password);

        $validated = $this->validate([
            'code' => ['required', 'regex:/^[A-Z0-9]{2,12}$/', Rule::unique('agents', 'code')->ignore($agent?->id)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($agent?->user_id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => $agent ? ['nullable', 'max:64', PasswordRule::defaults()] : ['required', 'max:64', PasswordRule::defaults()],
            'gender' => ['required', Rule::in(Agent::GENDERS)],
            'birthDate' => ['nullable', 'date_format:Y-m-d', 'before:today'],
            'district' => ['nullable', 'string', 'max:80'],
            'state' => ['required', Rule::in(Agent::STATES)],
            'bankName' => ['required', Rule::in(Agent::BANKS)],
            'bankAccountName' => ['nullable', 'string', 'max:120'],
            'bankAccountNo' => ['nullable', 'regex:/^[0-9 \-]{6,30}$/'],
        ], [
            'code.regex' => 'ID ejen mesti 2–12 huruf/nombor (cth. AZ01).',
            'bankAccountNo.regex' => 'Nombor akaun hanya nombor (6–30 digit).',
        ], [
            'code' => 'ID ejen', 'name' => 'nama penuh', 'email' => 'emel', 'phone' => 'no. telefon', 'password' => 'kata laluan',
            'gender' => 'jantina', 'birthDate' => 'tarikh lahir', 'district' => 'daerah', 'state' => 'negeri',
            'bankName' => 'bank', 'bankAccountName' => 'nama akaun', 'bankAccountNo' => 'nombor akaun',
        ]);

        $save->handle($agent, [
            'code' => $validated['code'],
            'name' => trim($this->name),
            'email' => trim($this->email),
            'phone' => trim($this->phone) ?: null,
            'password' => $this->password ?: null,
            'gender' => $this->gender,
            'birth_date' => $this->birthDate ?: null,
            'district' => trim($this->district) ?: null,
            'state' => $this->state,
            'bank_name' => $this->bankName,
            'bank_account_name' => trim($this->bankAccountName) ?: null,
            'bank_account_no' => trim($this->bankAccountNo) ?: null,
        ], $this->actor());

        $this->showForm = false;
        $this->resetForm();
        unset($this->agents, $this->performance);
    }

    public function toggleStatus(int $id, SetUserStatus $setStatus): void
    {
        $this->authorize(Module::Agents->managePermission());

        $agent = Agent::query()->with('user')->findOrFail($id);
        $setStatus->handle($agent->user, $agent->user->isSuspended() ? UserStatus::Active : UserStatus::Suspended, $this->actor());
        unset($this->agents, $this->performance);
    }

    public function delete(int $id, DeleteAgent $delete): void
    {
        $this->authorize(Module::Agents->managePermission());

        $delete->handle(Agent::query()->with('user')->findOrFail($id), $this->actor());
        $this->showView = false;
        $this->viewId = null;
        unset($this->agents, $this->performance);
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize(Module::Agents->viewPermission());

        $period = $this->periodFilter();
        $rows = $this->rows()->map(fn (array $r) => [
            $r['agent']->code, $r['agent']->user->name, $r['agent']->user->email, $r['agent']->user->phone,
            $r['agent']->gender, $r['agent']->birth_date?->format('d/m/Y'), $r['agent']->district, $r['agent']->state,
            $r['agent']->bank_name, $r['agent']->bank_account_name, $r['agent']->bank_account_no,
            $r['agent']->isActive() ? 'Aktif' : 'Tidak Aktif', $r['count'],
            round($r['sales_sen'] / 100, 2), round($r['commission_sen'] / 100, 2),
        ]);

        return Excel::download(new TableExport('Ejen',
            ['ID Ejen', 'Nama Penuh', 'Emel', 'No. Telefon', 'Jantina', 'Tarikh Lahir', 'Daerah', 'Negeri', 'Bank', 'Nama Akaun', 'Nombor Akaun', 'Status', 'Bil. Tempahan', 'Jualan (RM)', 'Komisen (RM)'],
            $rows, [9, 24, 26, 14, 10, 12, 14, 16, 16, 22, 18, 11, 12, 13, 13]),
            'Komisen-Ejen-'.$period->slug().'.xlsx');
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'name', 'email', 'phone', 'password', 'birthDate', 'district', 'bankAccountName', 'bankAccountNo');
        $this->gender = 'Lelaki';
        $this->state = 'Selangor';
        $this->bankName = 'Maybank';
        $this->resetValidation();
    }

    public function render(): mixed
    {
        $perf = $this->performance;

        return view('livewire.agents.index', [
            'p' => $this->periodFilter(),
            'rows' => $this->rows(),
            'top' => $perf->take(10),
            'maxSales' => max(1, (int) $perf->max('sales_sen')),
            'totals' => [
                'sales' => (int) $perf->sum('sales_sen'),
                'commission' => (int) $perf->sum('commission_sen'),
                'orders' => (int) $perf->sum('count'),
            ],
            'stats' => [
                ['icon' => 'users-three', 'tone' => 'primary', 'value' => (string) $this->agents->count(), 'label' => 'Jumlah Ejen'],
                ['icon' => 'check-circle', 'tone' => 'success', 'value' => (string) $this->agents->filter->isActive()->count(), 'label' => 'Ejen Aktif'],
                ['icon' => 'prohibit', 'tone' => 'danger', 'value' => (string) $this->agents->reject->isActive()->count(), 'label' => 'Tidak Aktif'],
            ],
        ]);
    }
}
