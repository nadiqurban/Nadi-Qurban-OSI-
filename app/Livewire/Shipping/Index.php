<?php

namespace App\Livewire\Shipping;

use App\Actions\Pipeline\GenerateAwb;
use App\Enums\Animal;
use App\Enums\Courier;
use App\Enums\Module;
use App\Enums\OrderStage;
use App\Enums\PostType;
use App\Enums\Service;
use App\Exports\TableExport;
use App\Livewire\Concerns\SelectsRows;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
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
 * AWB & Postage (AWB & Postage.dc.html): generate the Airway Bill for the
 * certificate once the final report is out — closes the order (Selesai).
 *
 * @property-read Collection<int, Order> $rows
 * @property-read array{pending: int, sent: int} $counts
 */
#[Layout('layouts::app')]
#[Title('AWB & Postage')]
class Index extends Component
{
    use SelectsRows;

    #[Url(as: 'tab', except: 'menunggu')]
    public string $tab = 'menunggu';

    /** "qurban:lembu" (Servis = ibadah — haiwan, as in the design). */
    #[Url(as: 'servis', except: '')]
    public string $kind = '';

    #[Url(as: 'tarikh', except: '')]
    public string $date = '';

    public bool $showGenerate = false;

    public ?int $generateOrderId = null;

    public string $courier = 'pos_laju';

    public string $postType = 'berdaftar';

    public string $address = '';

    public bool $showAwb = false;

    public ?int $awbOrderId = null;

    /** @return Builder<Order> */
    private function query(bool $sent): Builder
    {
        [$service, $animal] = array_pad(explode(':', $this->kind), 2, '');

        return ($sent ? Order::query()->whereHas('shipment') : Order::query()->where('stage', OrderStage::FinalReport))
            ->when(Service::tryFrom($service), fn (Builder $q, Service $s) => $q->where('service', $s))
            ->when(Animal::tryFrom($animal), fn (Builder $q, Animal $a) => $q->where('animal', $a))
            ->when($this->date !== '', fn (Builder $q) => $q->whereDate('implementation_date', $this->date));
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function rows(): Collection
    {
        return $this->query($this->tab === 'dijana')
            ->with(['customer', 'country', 'shipment'])
            ->latest('updated_at')
            ->limit(500)
            ->get();
    }

    /** @return array{pending: int, sent: int} */
    #[Computed]
    public function counts(): array
    {
        return ['pending' => $this->query(false)->count(), 'sent' => $this->query(true)->count()];
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function stats(): array
    {
        return [
            ['icon' => 'package', 'tone' => 'warning', 'value' => (string) $this->counts['pending'], 'label' => 'Menunggu AWB'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => (string) $this->counts['sent'], 'label' => 'AWB Dijana'],
            ['icon' => 'truck', 'tone' => 'info', 'value' => number_format(Shipment::query()->whereNull('delivered_at')->count()), 'label' => 'Dalam Penghantaran'],
            ['icon' => 'map-pin', 'tone' => 'primary', 'value' => number_format(Shipment::query()->whereNotNull('delivered_at')->count()), 'label' => 'Berjaya Dihantar'],
        ];
    }

    /**
     * Servis options present in this module: ["qurban:lembu" => "Qurban — Lembu"]
     *
     * @return array<string, string>
     */
    public function kindOptions(): array
    {
        return Order::query()->where(fn (Builder $q) => $q->where('stage', OrderStage::FinalReport)->orWhereHas('shipment'))
            ->select('service', 'animal')->distinct()->get()
            ->mapWithKeys(fn (Order $o) => [$o->service->value.':'.$o->animal->value => $o->ibadahLabel()])
            ->sort()->all();
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'kind', 'date'], true)) {
            $this->selected = [];
            unset($this->rows, $this->counts);
        }
    }

    public function clearFilters(): void
    {
        $this->reset('kind', 'date');
    }

    // ------------------------------------------------------------ generate

    public function openGenerate(int $orderId): void
    {
        $this->authorize(Module::Shipping->managePermission());

        $order = Order::query()->with('customer')->findOrFail($orderId);

        $this->generateOrderId = $order->id;
        $this->courier = Courier::PosLaju->value;
        $this->postType = PostType::Registered->value;
        $this->address = (string) $order->customer->address;
        $this->resetValidation();
        $this->showGenerate = true;
    }

    public function generate(GenerateAwb $generate): void
    {
        $this->authorize(Module::Shipping->managePermission());

        $this->validate([
            'courier' => ['required', 'in:'.implode(',', array_column(Courier::cases(), 'value'))],
            'postType' => ['required', 'in:'.implode(',', array_column(PostType::cases(), 'value'))],
            'address' => ['required', 'string', 'max:500'],
        ], [], ['courier' => 'kurier', 'postType' => 'jenis pos', 'address' => 'alamat penghantaran']);

        $order = Order::query()->findOrFail($this->generateOrderId);

        try {
            $shipment = $generate->handle($order, Courier::from($this->courier), PostType::from($this->postType), ['address' => trim($this->address)], $this->actor());
        } catch (ValidationException $e) {
            $this->addError('address', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->showGenerate = false;
        $this->tab = 'dijana';
        $this->refresh();
        $this->dispatch('toast', message: "AWB {$shipment->consignment_no} dijana — tempahan {$order->order_no} selesai.");
    }

    /** "Postage Pukal": Pos Laju + Pos Berdaftar to the customer's address. */
    public function bulkPostage(GenerateAwb $generate): void
    {
        $this->authorize(Module::Shipping->managePermission());

        $done = 0;
        $failed = [];

        foreach (Order::query()->whereIn('id', $this->selected)->where('stage', OrderStage::FinalReport)->get() as $order) {
            try {
                $generate->handle($order, Courier::PosLaju, PostType::Registered, [], $this->actor());
                $done++;
            } catch (ValidationException) {
                $failed[] = $order->order_no;
            }
        }

        $this->selected = [];
        $this->tab = 'dijana';
        $this->refresh();
        $this->dispatch('toast', message: "{$done} AWB dijana.");

        if ($failed !== []) {
            $this->dispatch('toast', message: 'Alamat tiada untuk: '.implode(', ', $failed), tone: 'danger');
        }
    }

    public function openAwb(int $orderId): void
    {
        $this->awbOrderId = $orderId;
        $this->showAwb = true;
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Shipping->viewPermission());

        $rows = $this->rows->map(fn (Order $o) => [
            $o->order_no, $o->customer->name, $o->ibadahLabel(), $o->package_name, $o->customer->phone, $o->quantity,
            $o->shipment->address ?? $o->customer->address, $o->shipment->postcode ?? $o->customer->postcode,
            $o->shipment->city ?? $o->customer->city, $o->shipment->state ?? $o->customer->state,
            $o->shipment?->courier->label() ?? '—', $o->shipment->consignment_no ?? '—',
            $o->shipment ? 'AWB Dijana' : 'Menunggu AWB',
        ]);

        return Excel::download(new TableExport('AWB',
            ['No. Tempahan', 'Pelanggan', 'Servis', 'Pakej', 'No. Telefon', 'Kuantiti', 'Alamat Penuh', 'Poskod', 'Bandar', 'Negeri', 'Kurier', 'No. Konsainan', 'Status'],
            $rows, [20, 24, 18, 12, 14, 9, 40, 8, 16, 16, 16, 18, 14]), 'AWB-Postage-Nadi-Qurban.xlsx');
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
        $generateOrder = $this->showGenerate && $this->generateOrderId ? Order::query()->with('customer')->find($this->generateOrderId) : null;

        return view('livewire.shipping.index', [
            'canManage' => auth()->user()?->can(Module::Shipping->managePermission()) ?? false,
            'generateOrder' => $generateOrder,
            'consignmentPreview' => $generateOrder ? (Courier::tryFrom($this->courier) ?? Courier::PosLaju)->consignmentFor($generateOrder->order_no) : '',
            'awbShipment' => $this->showAwb && $this->awbOrderId
                ? Shipment::query()->with(['order.customer', 'order.country'])->where('order_id', $this->awbOrderId)->first()
                : null,
        ]);
    }
}
