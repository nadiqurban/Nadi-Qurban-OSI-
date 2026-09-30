<?php

namespace App\Livewire\Allocation;

use App\Actions\Pipeline\AssignAllocation;
use App\Actions\Pipeline\CancelAllocation;
use App\Enums\Animal;
use App\Enums\Module;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\Service;
use App\Exports\TableExport;
use App\Livewire\Concerns\SelectsRows;
use App\Models\Country;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ParticipantGroups;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Agihan Negara (Agihan Negara.dc.html): after the akad each order gets an
 * implementation country + an active vendor of that country, then "Hantar".
 *
 * @property-read Collection<int, Order> $rows
 * @property-read array{pending: int, sent: int} $counts
 * @property-read array<int, array<int, string>> $vendorsByCountry
 */
#[Layout('layouts::app')]
#[Title('Agihan Negara')]
class Index extends Component
{
    use SelectsRows;

    #[Url(as: 'tab', except: 'belum')]
    public string $tab = 'belum';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'ibadah', except: '')]
    public string $service = '';

    #[Url(as: 'servis', except: '')]
    public string $animal = '';

    #[Url(as: 'negara', except: '')]
    public string $country = '';

    #[Url(as: 'vendor', except: '')]
    public string $vendor = '';

    public bool $groupByVendor = false;

    /**
     * Per-row choices before "Hantar": [orderId => ['country' => id|'', 'vendor' => id|'']]
     *
     * @var array<int, array{country: string, vendor: string}>
     */
    public array $draft = [];

    public bool $showBulk = false;

    public string $bulkCountry = '';

    public string $bulkVendor = '';

    public bool $showDetail = false;

    public ?int $detailOrderId = null;

    public bool $showGroups = false;

    public string $groupsDate = '';

    /** @return Builder<Order> */
    private function baseQuery(bool $sent): Builder
    {
        return $sent
            ? Order::query()->visibleTo(auth()->user())->whereHas('allocation')->where('status', '!=', OrderStatus::Cancelled)
            : Order::query()->visibleTo(auth()->user())->where('stage', OrderStage::AkadDone);
    }

    /** @return Builder<Order> */
    private function filtered(bool $sent): Builder
    {
        return $this->baseQuery($sent)
            ->when($this->search !== '', fn (Builder $q) => $q->where('order_no', 'like', '%'.trim($this->search).'%'))
            ->when(Service::tryFrom($this->service), fn (Builder $q, Service $s) => $q->where('service', $s))
            ->when(Animal::tryFrom($this->animal), fn (Builder $q, Animal $a) => $q->where('animal', $a))
            ->when($this->country !== '', fn (Builder $q) => $q->where('country_id', (int) $this->country))
            ->when($this->vendor !== '', fn (Builder $q) => $q->whereHas('allocation', fn (Builder $a) => $a->where('vendor_id', (int) $this->vendor)));
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function rows(): Collection
    {
        $sent = $this->tab === 'telah';

        $rows = $this->filtered($sent)
            ->with(['customer', 'country', 'allocation.vendor', 'allocation.country'])
            ->oldest('updated_at')
            ->limit(500)
            ->get();

        if ($sent) {
            return $rows->sortBy(fn (Order $o) => ($o->allocation?->vendor->name ?? '').'|'.$o->country->name)->values();
        }

        return $rows;
    }

    /** @return array{pending: int, sent: int} */
    #[Computed]
    public function counts(): array
    {
        return ['pending' => $this->filtered(false)->count(), 'sent' => $this->filtered(true)->count()];
    }

    /**
     * Active vendors per country: [countryId => [vendorId => "Name — SP 001"]]
     *
     * @return array<int, array<int, string>>
     */
    #[Computed]
    public function vendorsByCountry(): array
    {
        return Vendor::query()->active()->orderBy('code')->get()
            ->groupBy('country_id')
            ->map(fn ($vendors) => $vendors->mapWithKeys(fn (Vendor $v) => [$v->id => $v->name.' — '.$v->code])->all())
            ->all();
    }

    /**
     * Top 4 countries of the orders in this module (akad done + allocated).
     *
     * @return list<array{name: string, count: int, pct: int, tone: string}>
     */
    public function countrySummary(): array
    {
        $scope = fn (Builder $q) => $q->where('stage', OrderStage::AkadDone)->orWhereHas('allocation');
        $total = max(1, Order::query()->where('status', '!=', OrderStatus::Cancelled)->where($scope)->count());
        $tones = ['primary', 'info', 'warning', 'success'];

        return Order::query()->where('status', '!=', OrderStatus::Cancelled)->where($scope)
            ->toBase()->selectRaw('country_id, count(*) as aggregate')->groupBy('country_id')
            ->orderByDesc('aggregate')->limit(4)->get()
            ->values()
            ->map(fn ($row, int $i) => [
                'name' => (string) Country::query()->whereKey($row->country_id)->value('name'),
                'count' => (int) $row->aggregate,
                'pct' => (int) round($row->aggregate / $total * 100),
                'tone' => $tones[$i % 4],
            ])->all();
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'search', 'service', 'animal', 'country', 'vendor'], true)) {
            $this->selected = [];
            unset($this->rows, $this->counts);
        }

        if ($property === 'tab' && $this->tab !== 'telah') {
            $this->groupByVendor = false;
        }

        // Changing the country clears the vendor (design onCountry).
        if (preg_match('/^draft\.(\d+)\.country$/', $property, $m)) {
            $this->draft[(int) $m[1]]['vendor'] = '';
        }

        if ($property === 'bulkCountry') {
            $this->bulkVendor = '';
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'service', 'animal', 'country', 'vendor');
        $this->selected = [];
    }

    /**
     * Current draft choice for a row (defaults to the product's country).
     *
     * @return array{country: string, vendor: string}
     */
    public function choice(Order $order): array
    {
        return [
            'country' => (string) ($this->draft[$order->id]['country'] ?? $order->country_id),
            'vendor' => (string) ($this->draft[$order->id]['vendor'] ?? ''),
        ];
    }

    // ------------------------------------------------------------ send

    public function send(int $orderId, AssignAllocation $assign): void
    {
        $this->authorize(Module::Allocation->managePermission());

        $order = Order::query()->findOrFail($orderId);
        $choice = $this->choice($order);

        try {
            $assign->handle($order, (int) $choice['country'], (int) $choice['vendor'], $this->actor());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        unset($this->draft[$orderId]);
        $this->selected = array_values(array_diff($this->selected, [$orderId]));
        $this->tab = 'telah';
        $this->refresh();
        $this->dispatch('toast', message: "{$order->order_no} dihantar kepada vendor.");
    }

    public function openBulk(): void
    {
        $this->authorize(Module::Allocation->managePermission());
        $this->reset('bulkCountry', 'bulkVendor');
        $this->showBulk = true;
    }

    public function applyBulk(AssignAllocation $assign): void
    {
        $this->authorize(Module::Allocation->managePermission());

        if ($this->bulkCountry === '' || $this->bulkVendor === '') {
            return;
        }

        $done = 0;

        foreach (Order::query()->whereIn('id', $this->selected)->where('stage', OrderStage::AkadDone)->get() as $order) {
            try {
                $assign->handle($order, (int) $this->bulkCountry, (int) $this->bulkVendor, $this->actor());
                $done++;
            } catch (ValidationException $e) {
                $this->addError('bulkVendor', (string) collect($e->errors())->flatten()->first());

                return;
            }
        }

        $this->showBulk = false;
        $this->selected = [];
        $this->tab = 'telah';
        $this->refresh();
        $this->dispatch('toast', message: "{$done} tempahan diagihkan.");
    }

    public function cancel(int $orderId, CancelAllocation $cancel): void
    {
        $this->authorize(Module::Allocation->managePermission());

        $order = Order::query()->findOrFail($orderId);

        try {
            $cancel->handle($order, $this->actor());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->tab = 'belum';
        $this->refresh();
        $this->dispatch('toast', message: "Agihan {$order->order_no} dibatalkan.");
    }

    public function openDetail(int $orderId): void
    {
        $this->detailOrderId = $orderId;
        $this->showDetail = true;
    }

    // ------------------------------------------------------------ groups & export

    /**
     * Selected rows, else the allocated ones, else everything listed (design grpSource).
     *
     * @return list<int>
     */
    private function groupIds(): array
    {
        if ($this->selected !== []) {
            return array_map('intval', $this->selected);
        }

        $sent = $this->baseQuery(true)->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $sent !== [] ? $sent : $this->selectableIds();
    }

    public function openGroups(): void
    {
        $first = Order::query()->whereIn('id', $this->groupIds())->orderBy('order_no')->first();
        $this->groupsDate = (string) ($first?->implementation_date?->toDateString() ?? now()->toDateString());
        $this->showGroups = true;
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Allocation->viewPermission());

        $rows = $this->rows->map(function (Order $o) {
            $choice = $this->choice($o);
            $sent = (bool) $o->allocation;
            $country = $sent ? $o->country->name : (Country::query()->whereKey((int) $choice['country'])->value('name') ?? '-');

            return [
                $o->order_no, $o->customer->name, $o->customer->phone, $o->ibadahLabel(), $o->quantity, $o->package_name,
                $country, $o->allocation?->vendor->name ?? '-',
                $sent ? 'Diagihkan' : ($choice['country'] !== '' ? 'Perlu Vendor' : 'Belum Agih'),
            ];
        });

        return Excel::download(new TableExport('Agihan Negara',
            ['No. Tempahan', 'Pelanggan', 'Telefon', 'Ibadah', 'Kuantiti', 'Pakej', 'Negara', 'Vendor', 'Status'],
            $rows, [20, 24, 15, 18, 9, 12, 16, 22, 14]), 'Agihan-Negara-Nadi-Qurban.xlsx');
    }

    private function refresh(): void
    {
        unset($this->rows, $this->counts);
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $canManage = auth()->user()?->can(Module::Allocation->managePermission()) ?? false;

        // Seed each pending row's selects with the product's country (design default).
        foreach ($this->tab === 'belum' ? $this->rows : [] as $o) {
            $this->draft[$o->id] ??= ['country' => (string) $o->country_id, 'vendor' => ''];
        }

        $sentOrders = $this->tab === 'telah' ? $this->rows : collect();
        $groupIds = $this->showGroups ? $this->groupIds() : [];

        return view('livewire.allocation.index', [
            'canManage' => $canManage,
            'countries' => Country::query()->active()->orderBy('sort')->pluck('name', 'id')->all(),
            'vendorOptions' => Vendor::query()->whereHas('allocations')->orderBy('name')->pluck('name', 'id')->all(),
            'vendorGroups' => $this->groupByVendor ? $sentOrders->groupBy(fn (Order $o) => $o->allocation?->vendor_id) : collect(),
            'detailOrder' => $this->showDetail && $this->detailOrderId ? Order::query()->with(['customer', 'country', 'allocation.vendor', 'allocation.allocatedBy'])->find($this->detailOrderId) : null,
            'groups' => $groupIds !== [] ? ParticipantGroups::for(Order::query()->with(['customer', 'country', 'participants'])->whereIn('id', $groupIds)->orderBy('order_no')->get()) : [],
            'selectionQuery' => http_build_query(['ids' => $groupIds]),
            'selectedPending' => $this->tab === 'belum' ? count($this->selected) : 0,
        ]);
    }
}
