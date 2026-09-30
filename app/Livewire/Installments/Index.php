<?php

namespace App\Livewire\Installments;

use App\Actions\Installments\CancelPlans;
use App\Actions\Installments\CreatePlan;
use App\Actions\Installments\SendPlanToOrder;
use App\Actions\Installments\SetPaidMonths;
use App\Enums\Animal;
use App\Enums\InstallmentPlanStatus;
use App\Enums\InstallmentStatus;
use App\Enums\Module;
use App\Enums\Service;
use App\Exports\TableExport;
use App\Livewire\Concerns\SelectsRows;
use App\Livewire\Forms\InstallmentPlanForm;
use App\Models\Country;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Bayaran Ansuran (Bayaran Ansuran.dc.html): instalment plans, the segmented
 * progress bar (confirm / undo manual payments), links, receipts and "Hantar".
 *
 * @property-read Collection<int, InstallmentPlan> $plans
 * @property-read array<string, int> $counts
 */
#[Layout('layouts::app')]
#[Title('Bayaran Ansuran')]
class Index extends Component
{
    use SelectsRows;
    use WithFileUploads;

    public const TABS = ['berjalan' => 'Berjalan', 'selesai' => 'Selesai', 'lewat' => 'Lewat Bayar', 'batal' => 'Tempahan Batal', 'semua' => 'Semua'];

    #[Url(as: 'tab', except: 'berjalan')]
    public string $tab = 'berjalan';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'servis', except: '')]
    public string $service = '';

    #[Url(as: 'haiwan', except: '')]
    public string $animal = '';

    #[Url(as: 'negara', except: '')]
    public string $country = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    public InstallmentPlanForm $form;

    public bool $showForm = false;

    // Sahkan Bayaran Diterima
    public bool $showPay = false;

    public ?int $payPlanId = null;

    public int $payMonth = 0;

    public string $payMethod = 'Perbankan Internet';

    // Butiran / link / resit / batal
    public bool $showDetail = false;

    public bool $showLink = false;

    public bool $showReceipt = false;

    public ?int $activePlanId = null;

    /** @var array<int, string> */
    public array $detailNames = [];

    public bool $showCancel = false;

    public string $cancelReason = '';

    /** @return Builder<InstallmentPlan> */
    private function filtered(): Builder
    {
        return InstallmentPlan::query()
            ->search($this->search)
            ->when(Service::tryFrom($this->service), fn (Builder $q, Service $s) => $q->where('service', $s))
            ->when(Animal::tryFrom($this->animal), fn (Builder $q, Animal $a) => $q->where('animal', $a))
            ->when($this->country !== '', fn (Builder $q) => $q->where('country_id', (int) $this->country))
            ->when(InstallmentPlanStatus::tryFrom($this->status), fn (Builder $q, InstallmentPlanStatus $s) => $q->where('status', $s));
    }

    /**
     * @param  Builder<InstallmentPlan>  $query
     * @return Builder<InstallmentPlan>
     */
    private function forTab(Builder $query, string $tab): Builder
    {
        return match ($tab) {
            'selesai' => $query->where('status', InstallmentPlanStatus::Completed),
            'lewat' => $query->where('status', InstallmentPlanStatus::Late),
            'batal' => $query->where('status', InstallmentPlanStatus::Cancelled),
            'semua' => $query,
            default => $query->whereIn('status', [InstallmentPlanStatus::Ongoing, InstallmentPlanStatus::Late]),
        };
    }

    /** @return Collection<int, InstallmentPlan> */
    #[Computed]
    public function plans(): Collection
    {
        return $this->forTab($this->filtered(), $this->tab)
            ->with(['customer', 'country', 'installments'])
            ->latest()
            ->limit(500)
            ->get();
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        return collect(array_keys(self::TABS))->mapWithKeys(fn (string $t) => [$t => $this->forTab(InstallmentPlan::query(), $t)->count()])->all();
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function stats(): array
    {
        $active = [InstallmentPlanStatus::Ongoing, InstallmentPlanStatus::Late, InstallmentPlanStatus::Completed];
        $paid = Installment::query()->where('status', InstallmentStatus::Paid)->whereHas('plan', fn (Builder $q) => $q->whereIn('status', $active))->sum('amount_sen');
        $deposits = InstallmentPlan::query()->whereIn('status', $active)->sum('deposit_sen');
        $outstanding = Installment::query()->where('status', InstallmentStatus::Unpaid)
            ->whereHas('plan', fn (Builder $q) => $q->whereIn('status', [InstallmentPlanStatus::Ongoing, InstallmentPlanStatus::Late]))->sum('amount_sen');

        return [
            ['icon' => 'calendar-check', 'tone' => 'primary', 'value' => (string) $this->counts['berjalan'], 'label' => 'Pelan Aktif'],
            ['icon' => 'coins', 'tone' => 'success', 'value' => rm_short((int) $paid + (int) $deposits), 'label' => 'Telah Dikutip'],
            ['icon' => 'hourglass-medium', 'tone' => 'warning', 'value' => rm_short((int) $outstanding), 'label' => 'Baki Tertunggak'],
            ['icon' => 'warning-circle', 'tone' => 'danger', 'value' => (string) $this->counts['lewat'], 'label' => 'Lewat Bayar'],
            ['icon' => 'x-circle', 'tone' => 'danger', 'value' => (string) $this->counts['batal'], 'label' => 'Tempahan Batal'],
        ];
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->plans->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'search', 'service', 'animal', 'country', 'status'], true)) {
            $this->selected = [];
            unset($this->plans);
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'service', 'animal', 'country', 'status');
    }

    private function refresh(): void
    {
        unset($this->plans, $this->counts);
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    private function plan(int $id): InstallmentPlan
    {
        /** @var InstallmentPlan */
        return InstallmentPlan::query()->with(['customer', 'country', 'installments'])->findOrFail($id);
    }

    // ------------------------------------------------------------ new plan

    public function create(): void
    {
        $this->authorize(Module::Installments->managePermission());
        $this->form->resetForm();
        $this->showForm = true;
    }

    public function updatedFormProductId(): void
    {
        $this->form->countryId = $this->form->product()?->country_id;
    }

    public function updatedFormPromo(): void
    {
        $this->form->promo = mb_strtoupper(trim($this->form->promo));
    }

    public function save(CreatePlan $create): void
    {
        $this->authorize(Module::Installments->managePermission());

        $this->form->validate();

        try {
            $plan = $create->handle(
                [
                    'name' => trim($this->form->name),
                    'phone' => trim($this->form->phone),
                    'email' => trim($this->form->email) ?: null,
                    'address' => trim($this->form->address) ?: null,
                    'postcode' => trim($this->form->postcode) ?: null,
                    'city' => trim($this->form->city) ?: null,
                    'state' => $this->form->state,
                ],
                [
                    'product_id' => (int) $this->form->productId,
                    'country_id' => $this->form->countryId,
                    'quantity' => $this->form->quantityInt(),
                    'year' => (int) $this->form->year,
                    'implementation_date' => $this->form->implementationDate ?: null,
                    'months' => $this->form->monthsInt(),
                    'deposit_sen' => $this->form->depositSen(),
                    'promo_code' => $this->form->promo ?: null,
                    'payment_method' => $this->form->paymentMethod,
                ],
                array_map(fn (int $i) => (string) ($this->form->participants[$i] ?? ''), range(0, $this->form->quantityInt() - 1)),
                $this->form->proof,
                $this->actor(),
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError('form.'.$field, $messages[0]);
            }

            return;
        }

        $this->showForm = false;
        $this->form->resetForm();
        $this->tab = 'berjalan';
        $this->refresh();
        $this->dispatch('toast', message: "Pelan ansuran {$plan->order_no} disimpan.");
    }

    // ------------------------------------------------------------ segments

    public function segment(int $planId, int $month): void
    {
        $this->authorize(Module::Installments->managePermission());

        $plan = $this->plan($planId);
        $paid = $plan->paidCount();

        if ($month > $paid) {
            $this->payPlanId = $plan->id;
            $this->payMonth = $month;
            $this->payMethod = SetPaidMonths::MANUAL_METHODS[0];
            $this->showPay = true;

            return;
        }

        // Paid segment: the last one undoes itself, an earlier one keeps months 1…N.
        $keep = $month === $paid ? $month - 1 : $month;

        try {
            app(SetPaidMonths::class)->undo($plan, $keep, $this->actor());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->refresh();
        $this->dispatch('toast', message: "Tanda bayaran {$plan->order_no} dikemaskini ({$keep}/{$plan->months} bulan).", tone: 'info');
    }

    public function confirmPayment(SetPaidMonths $setPaid): void
    {
        $this->authorize(Module::Installments->managePermission());

        $plan = $this->plan((int) $this->payPlanId);

        try {
            $setPaid->markReceived($plan, $this->payMonth, $this->payMethod, $this->actor());
        } catch (ValidationException $e) {
            $this->addError('payMethod', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->showPay = false;
        $this->refresh();
        $this->dispatch('toast', message: "Ansuran bulan {$this->payMonth} {$plan->order_no} ditanda dibayar.");
    }

    // ------------------------------------------------------------ row actions

    public function openDetail(int $planId): void
    {
        $plan = $this->plan($planId);
        $this->activePlanId = $plan->id;
        $this->detailNames = $plan->participantList();
        $this->showDetail = true;
    }

    public function saveNames(): void
    {
        $this->authorize(Module::Installments->managePermission());

        $this->validate(['detailNames.*' => ['nullable', 'string', 'max:150']], [], ['detailNames.*' => 'nama peserta']);

        $plan = $this->plan((int) $this->activePlanId);
        $plan->forceFill(['participant_names' => array_map(fn (int $i) => trim((string) ($this->detailNames[$i] ?? '')), range(0, $plan->quantity - 1))])->save();

        $this->showDetail = false;
        $this->dispatch('toast', message: 'Senarai peserta disimpan.');
    }

    public function openLink(int $planId): void
    {
        $this->activePlanId = $planId;
        $this->showLink = true;
    }

    public function openReceipt(int $planId): void
    {
        $this->activePlanId = $planId;
        $this->showReceipt = true;
    }

    public function send(int $planId, SendPlanToOrder $send): void
    {
        $this->authorize(Module::Installments->managePermission());

        try {
            $order = $send->handle($this->plan($planId), $this->actor());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->refresh();
        $this->dispatch('toast', message: "{$order->order_no} dihantar ke Pengesahan Bayaran.");
    }

    public function sendBulk(SendPlanToOrder $send): void
    {
        $this->authorize(Module::Installments->managePermission());

        $done = 0;

        foreach ($this->plans->whereIn('id', $this->selected) as $plan) {
            if ($plan->status === InstallmentPlanStatus::Completed && ! $plan->sent_at) {
                $send->handle($plan, $this->actor());
                $done++;
            }
        }

        $this->selected = [];
        $this->refresh();
        $this->dispatch('toast', message: "{$done} pelan dihantar ke Pengesahan Bayaran.");
    }

    public function openCancel(): void
    {
        $this->authorize(Module::Installments->managePermission());
        $this->cancelReason = '';
        $this->resetValidation();
        $this->showCancel = true;
    }

    public function cancelSelected(CancelPlans $cancel): void
    {
        $this->authorize(Module::Installments->managePermission());

        $this->validate(['cancelReason' => ['required', 'string', 'min:3', 'max:300']], [], ['cancelReason' => 'sebab pembatalan']);

        $count = $cancel->cancel(array_map('intval', $this->selected), trim($this->cancelReason), $this->actor());

        $this->showCancel = false;
        $this->selected = [];
        $this->tab = 'batal';
        $this->refresh();
        $this->dispatch('toast', message: "{$count} pelan dibatalkan.");
    }

    public function restore(int $planId, CancelPlans $cancel): void
    {
        $this->authorize(Module::Installments->managePermission());

        $cancel->restore($this->plan($planId), $this->actor());
        $this->refresh();
        $this->dispatch('toast', message: 'Pelan dipulihkan.');
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Installments->viewPermission());

        $rows = $this->plans->map(fn (InstallmentPlan $p) => [
            $p->order_no, $p->customer->name, $p->ibadahLabel(), $p->package_name, $p->country->name, $p->quantity,
            rm($p->total_sen), rm($p->monthly_sen), $p->paidCount(), $p->months, $p->status->label(),
        ]);

        return Excel::download(new TableExport('Ansuran',
            ['No. Tempahan', 'Pelanggan', 'Servis', 'Pakej', 'Negara', 'Kuantiti', 'Jumlah Pelan', 'Ansuran/Bulan', 'Dibayar (bulan)', 'Tempoh (bulan)', 'Status'],
            $rows, [20, 26, 18, 12, 14, 10, 12, 12, 14, 14, 14]), 'Bayaran-Ansuran-Nadi-Qurban.xlsx');
    }

    public function render(): mixed
    {
        $active = $this->activePlanId && ($this->showDetail || $this->showLink || $this->showReceipt) ? $this->plan($this->activePlanId) : null;
        $selectedPlans = $this->plans->whereIn('id', array_map('intval', $this->selected));

        return view('livewire.installments.index', [
            'canManage' => auth()->user()?->can(Module::Installments->managePermission()) ?? false,
            'activePlan' => $active,
            'payPlan' => $this->showPay && $this->payPlanId ? $this->plan($this->payPlanId) : null,
            'products' => $this->showForm ? Product::query()->where('is_active', true)->orderBy('name')->get() : collect(),
            'countries' => Country::query()->active()->orderBy('sort')->pluck('name', 'id')->all(),
            'breakdown' => $this->showForm ? $this->form->breakdown() : null,
            'selectedCount' => $selectedPlans->count(),
            'sendableCount' => $selectedPlans->filter(fn (InstallmentPlan $p) => $p->status === InstallmentPlanStatus::Completed && ! $p->sent_at)->count(),
        ]);
    }
}
