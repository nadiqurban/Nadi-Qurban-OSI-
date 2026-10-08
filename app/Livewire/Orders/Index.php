<?php

namespace App\Livewire\Orders;

use App\Actions\Crm\Leads;
use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\DeleteOrders;
use App\Actions\Orders\UpdateOrderStatus;
use App\Actions\Pipeline\IssueCertificates;
use App\Enums\Animal;
use App\Enums\Courier;
use App\Enums\Module;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\Service;
use App\Exports\CustomersExport;
use App\Exports\OrdersExport;
use App\Livewire\Forms\OrderForm;
use App\Models\Certificate;
use App\Models\Country;
use App\Models\Lead;
use App\Models\Order;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\User;
use App\Support\CertificateTemplate;
use App\Support\ParticipantGroups;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Senarai Tempahan (Tempahan & Pelanggan.dc.html list view): stats, period pills,
 * search + filters, 13-column table, bulk bar, new-order modal, participant
 * groups, waybill and proof viewer.
 *
 * @property-read LengthAwarePaginator<int, Order> $orders
 * @property-read list<array{icon: string, tone: string, value: string, label: string}> $stats
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Order> $selectedOrders
 */
#[Layout('layouts::app')]
#[Title('Senarai Tempahan')]
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public const PERIODS = ['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan', 'custom' => 'Custom'];

    #[Url(as: 'tempoh', except: 'bulanan')]
    public string $period = 'bulanan';

    #[Url(as: 'dari', except: '')]
    public string $from = '';

    #[Url(as: 'hingga', except: '')]
    public string $to = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $service = '';

    #[Url(except: '')]
    public string $animal = '';

    #[Url(as: 'negara', except: '')]
    public string $country = '';

    #[Url(except: '')]
    public string $status = '';

    /** @var list<int> */
    public array $selected = [];

    public OrderForm $form;

    public bool $showForm = false;

    public bool $showGroups = false;

    public bool $showWaybill = false;

    public string $courier = 'pos_laju';

    public string $groupsDate = '';

    public ?int $proofOrderId = null;

    public bool $showProof = false;

    /** Sales CRM "Tukar ke Tempahan": lead that prefilled the new-order modal. */
    #[Locked]
    public ?int $fromLead = null;

    public function mount(): void
    {
        $this->form->resetForm();

        $leadId = (int) request()->query('lead');
        $user = auth()->user();
        if ($leadId > 0 && $user?->can(Module::Orders->managePermission()) && $user->can(Module::Crm->viewPermission())) {
            $lead = Lead::query()->whereNull('order_id')->find($leadId);
            if ($lead) {
                $this->fromLead = $lead->id;
                $this->form->name = $lead->name;
                $this->form->phone = (string) $lead->phone;
                $this->form->email = (string) $lead->email;
                $this->showForm = true;
            }
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['period', 'from', 'to', 'search', 'service', 'animal', 'country', 'status'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    // ------------------------------------------------------------ queries

    /** @return Builder<Order> */
    private function filtered(): Builder
    {
        return Order::query()
            ->search($this->search)
            ->when($this->service !== '', fn ($q) => $q->where('service', $this->service))
            ->when($this->animal !== '', fn ($q) => $q->where('animal', $this->animal))
            ->when($this->country !== '', fn ($q) => $q->where('country_id', (int) $this->country))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->periodRange(), fn ($q, array $range) => $q->whereBetween('created_at', $range));
    }

    /** @return array{0: Carbon, 1: Carbon}|null */
    private function periodRange(): ?array
    {
        return match ($this->period) {
            'harian' => [today(), today()->endOfDay()],
            'mingguan' => [now()->startOfWeek(), now()->endOfWeek()],
            'bulanan' => [now()->startOfMonth(), now()->endOfMonth()],
            'tahunan' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => $this->from !== '' || $this->to !== ''
                ? [$this->from !== '' ? Carbon::parse($this->from)->startOfDay() : Carbon::create(2000), $this->to !== '' ? Carbon::parse($this->to)->endOfDay() : now()->addYears(5)]
                : null,
            default => null,
        };
    }

    /** @return LengthAwarePaginator<int, Order> */
    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return $this->filtered()
            ->with(['customer', 'country', 'payment.order', 'payment.media', 'agent'])
            ->latest()
            ->latest('id')
            ->paginate(10);
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    #[Computed]
    public function stats(): array
    {
        $counts = Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            ['icon' => 'shopping-cart-simple', 'tone' => 'primary', 'value' => number_format((int) $counts->sum()), 'label' => 'Jumlah Tempahan'],
            ['icon' => 'spinner-gap', 'tone' => 'info', 'value' => number_format((int) ($counts[OrderStatus::InProgress->value] ?? 0)), 'label' => 'Dalam Proses'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => number_format((int) ($counts[OrderStatus::Completed->value] ?? 0)), 'label' => 'Selesai'],
            ['icon' => 'hourglass-medium', 'tone' => 'warning', 'value' => number_format((int) ($counts[OrderStatus::AwaitingPayment->value] ?? 0)), 'label' => 'Menunggu Bayaran'],
        ];
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()->with(['package', 'country'])->where('is_active', true)->orderBy('name')->get();
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function selectedOrders(): Collection
    {
        return Order::query()->with(['customer', 'country', 'participants'])->whereIn('id', $this->selected)->orderBy('order_no')->get();
    }

    /** @return array<string, array<string, string>> */
    public function filterOptions(): array
    {
        return [
            'service' => Service::options(),
            'animal' => Animal::options(),
            'country' => Country::query()->active()->pluck('name', 'id')->mapWithKeys(fn ($n, $id) => [(string) $id => $n])->all(),
            'status' => OrderStatus::options(),
        ];
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->service !== '' || $this->animal !== '' || $this->country !== '' || $this->status !== '';
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Orders->managePermission()) ?? false;
    }

    // ------------------------------------------------------------ selection

    public function toggleAll(): void
    {
        $pageIds = $this->orders->pluck('id')->map(fn ($id) => (int) $id)->all();
        $allSelected = array_diff($pageIds, $this->selected) === [];

        $this->selected = $allSelected
            ? array_values(array_diff($this->selected, $pageIds))
            : array_values(array_unique([...$this->selected, ...$pageIds]));
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'service', 'animal', 'country', 'status');
        $this->resetPage();
    }

    // ------------------------------------------------------------ bulk actions

    public function markAccepted(UpdateOrderStatus $update): void
    {
        $this->authorize(Module::Orders->managePermission());

        $count = $update->handle($this->selected, OrderStatus::Accepted, $this->actor());
        $this->afterBulk("{$count} tempahan ditanda Diterima dan dihantar ke Pengesahan Bayaran.");
    }

    /** "Batal": cancelled orders are deleted for good (no record kept). */
    public function cancelSelected(DeleteOrders $delete): void
    {
        $this->authorize(Module::Orders->managePermission());

        $count = $delete->handle(array_map('intval', $this->selected), $this->actor());
        $this->afterBulk("{$count} tempahan dibatalkan dan dipadam.");
    }

    public function setStatus(string $status, UpdateOrderStatus $update): void
    {
        $this->authorize(Module::Orders->managePermission());

        $target = OrderStatus::from($status);
        abort_unless(in_array($target, OrderStatus::manual(), true), 422);

        $count = $update->handle($this->selected, $target, $this->actor());
        $this->afterBulk("Status {$count} tempahan dikemaskini kepada {$target->label()}.");
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Orders->viewPermission());

        $query = $this->selected !== [] ? Order::query()->whereIn('id', $this->selected) : $this->filtered();

        return Excel::download(new OrdersExport($query->latest()->latest('id')), 'Tempahan-Nadi-Qurban-'.now()->format('Ymd').'.xlsx');
    }

    public function exportCustomers(): BinaryFileResponse
    {
        $this->authorize(Module::Orders->viewPermission());

        return Excel::download(new CustomersExport($this->selectedOrders), 'Maklumat-Pelanggan-Nadi-Qurban.xlsx');
    }

    public function openGroups(): void
    {
        $this->groupsDate = (string) ($this->selectedOrders->first()?->implementation_date?->toDateString() ?? now()->toDateString());
        $this->showGroups = true;
    }

    /** "Generate Sijil": issue one certificate per participant, then download the A5 PDF. */
    public function generateCertificates(IssueCertificates $issue, CertificateTemplate $template): ?StreamedResponse
    {
        $this->authorize(Module::Certificates->managePermission());

        $orders = $this->selectedOrders->filter(fn (Order $o) => $o->status !== OrderStatus::Cancelled && $o->stage->position() >= OrderStage::PaymentVerified->position());

        if ($orders->isEmpty()) {
            $this->dispatch('toast', message: 'Sijil hanya boleh dijana untuk tempahan yang bayarannya telah disahkan.', tone: 'danger');

            return null;
        }

        /** @var User $user */
        $user = auth()->user();
        $pages = $issue->handle($orders->values(), $user)
            ->map(fn (Certificate $c) => $template->render(CertificateTemplate::valuesFor($c)))->values()->all();

        return $template->download($pages, $orders->count() === 1 ? 'Sijil-'.$orders->first()->order_no.'.pdf' : 'Sijil-Nadi-Qurban.pdf');
    }

    public function viewProof(int $orderId): void
    {
        $this->proofOrderId = $orderId;
        $this->showProof = true;
    }

    // ------------------------------------------------------------ new order

    public function create(): void
    {
        $this->authorize(Module::Orders->managePermission());

        $this->form->resetForm();
        $this->showForm = true;
    }

    public function updatedFormPromo(): void
    {
        $this->form->promo = mb_strtoupper(trim($this->form->promo));
    }

    public function removeProof(): void
    {
        $this->form->proof = null;
    }

    public function save(CreateOrder $create): void
    {
        $this->authorize(Module::Orders->managePermission());

        $this->form->validate();

        if ($this->form->promo !== '' && ! $this->form->promoModel()) {
            $this->addError('form.promo', 'Kod promosi tidak sah, telah tamat atau mencapai had.');

            return;
        }

        try {
            $order = $create->handle(
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
                    'quantity' => $this->form->quantityInt(),
                    'year' => (int) $this->form->year,
                    'implementation_date' => $this->form->implementationDate ?: null,
                    'promo_code' => $this->form->promo ?: null,
                    'payment_method' => $this->form->paymentMethod,
                ],
                [],
                $this->form->proof,
                $this->actor(),
            );
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError('form.'.$field, $messages[0]);
            }

            return;
        }

        if ($this->fromLead && ($lead = Lead::query()->find($this->fromLead))) {
            app(Leads::class)->linkOrder($lead, $order, $this->actor());
            $this->fromLead = null;
        }

        $this->showForm = false;
        $this->form->resetForm();
        $this->resetPage();
        unset($this->orders, $this->stats);

        $this->dispatch('toast', message: "Tempahan {$order->order_no} disimpan.");
    }

    // ------------------------------------------------------------ helpers

    private function afterBulk(string $message): void
    {
        $this->selected = [];
        unset($this->orders, $this->stats);
        $this->dispatch('toast', message: $message);
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $proofOrder = $this->proofOrderId ? Order::query()->with('payment.media')->find($this->proofOrderId) : null;

        return view('livewire.orders.index', [
            'options' => $this->filterOptions(),
            'couriers' => Courier::cases(),
            'promoCodes' => PromoCode::query()->usable()->orderBy('code')->pluck('code'),
            'proofOrder' => $proofOrder,
            'groups' => $this->showGroups ? ParticipantGroups::for($this->selectedOrders) : [],
        ]);
    }
}
