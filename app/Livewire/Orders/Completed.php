<?php

namespace App\Livewire\Orders;

use App\Enums\Animal;
use App\Enums\Module;
use App\Enums\OrderStage;
use App\Enums\OrderStatus;
use App\Enums\Service;
use App\Exports\TableExport;
use App\Livewire\Concerns\SelectsRows;
use App\Models\Certificate;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Tempahan Selesai (Tempahan Selesai.dc.html): orders that went all the way from
 * payment to certificate postage. "Tarikh" = implementation date (as in the design),
 * falling back to the AWB date.
 *
 * @property-read Collection<int, Order> $rows
 */
#[Layout('layouts::app')]
#[Title('Tempahan Selesai')]
class Completed extends Component
{
    use SelectsRows;

    #[Url(as: 'servis', except: '')]
    public string $kind = '';

    #[Url(as: 'negara', except: '')]
    public string $country = '';

    #[Url(as: 'tarikh', except: '')]
    public string $date = '';

    public bool $showDetail = false;

    public ?int $detailOrderId = null;

    /** @return Builder<Order> */
    private function base(): Builder
    {
        return Order::query()->where('status', OrderStatus::Completed)->where('stage', OrderStage::Completed);
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function rows(): Collection
    {
        [$service, $animal] = array_pad(explode(':', $this->kind), 2, '');

        return $this->base()
            ->when(Service::tryFrom($service), fn (Builder $q, Service $s) => $q->where('service', $s))
            ->when(Animal::tryFrom($animal), fn (Builder $q, Animal $a) => $q->where('animal', $a))
            ->when($this->country !== '', fn (Builder $q) => $q->where('country_id', (int) $this->country))
            ->when($this->date !== '', fn (Builder $q) => $q->whereDate('implementation_date', $this->date))
            ->with(['customer', 'country', 'shipment'])
            ->latest('updated_at')
            ->limit(500)
            ->get();
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function stats(): array
    {
        $total = $this->base()->count();

        return [
            ['icon' => 'check-square-offset', 'tone' => 'success', 'value' => number_format($total), 'label' => 'Jumlah Selesai'],
            ['icon' => 'cow', 'tone' => 'primary', 'value' => number_format($this->base()->where('service', Service::Qurban)->where('animal', Animal::Cow)->count()), 'label' => 'Qurban Lembu'],
            ['icon' => 'certificate', 'tone' => 'warning', 'value' => number_format(Certificate::query()->whereIn('order_id', $this->base()->select('id'))->count()), 'label' => 'Sijil Dihantar'],
            ['icon' => 'globe-hemisphere-west', 'tone' => 'info', 'value' => (string) $this->base()->distinct()->count('country_id'), 'label' => 'Negara Pelaksanaan'],
        ];
    }

    /** @return array<string, string> */
    public function kindOptions(): array
    {
        return $this->base()->select('service', 'animal')->distinct()->get()
            ->mapWithKeys(fn (Order $o) => [$o->service->value.':'.$o->animal->value => $o->ibadahLabel()])
            ->sort()->all();
    }

    /** @return array<int, string> */
    public function countryOptions(): array
    {
        return $this->base()->with('country')->get()->pluck('country.name', 'country_id')->sort()->all();
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['kind', 'country', 'date'], true)) {
            $this->selected = [];
            unset($this->rows);
        }
    }

    public function clearFilters(): void
    {
        $this->reset('kind', 'country', 'date');
    }

    public function openDetail(int $orderId): void
    {
        $this->detailOrderId = $orderId;
        $this->showDetail = true;
    }

    public static function doneDate(Order $order): string
    {
        $date = $order->implementation_date ?? $order->shipment?->generated_at;

        return $date ? tarikh($date) : '-';
    }

    public static function address(Order $order): string
    {
        $c = $order->customer;

        return collect([$c->address, trim($c->postcode.' '.$c->city), $c->state])->filter()->implode(', ') ?: '-';
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Completed->viewPermission());

        $rows = $this->rows->map(fn (Order $o) => [
            $o->order_no, $o->customer->name, $o->customer->phone, $o->ibadahLabel(), $o->package_name, $o->quantity,
            $o->country->name, $o->year, self::doneDate($o), rm($o->total_sen),
            $o->customer->email ?: '-', self::address($o), 'Selesai',
        ]);

        return Excel::download(new TableExport('Tempahan Selesai',
            ['No. Tempahan', 'Pelanggan', 'No. Telefon', 'Servis', 'Pakej', 'Kuantiti', 'Negara Pelaksanaan', 'Tahun', 'Tarikh Selesai', 'Jumlah Bayaran', 'Emel', 'Alamat Penuh', 'Status'],
            $rows, [18, 24, 15, 18, 12, 9, 18, 8, 15, 14, 26, 40, 10]), 'tempahan-selesai.xlsx');
    }

    public function render(): mixed
    {
        $order = $this->showDetail && $this->detailOrderId
            ? $this->base()->with(['customer', 'country', 'shipment', 'stageHistories'])->find($this->detailOrderId)
            : null;

        $timeline = [];

        if ($order) {
            $at = fn (OrderStage $s) => $order->stageHistories->firstWhere('stage', $s)?->created_at;
            $timeline = [
                ['Bayaran Disahkan', 'Bayaran diterima & disahkan HQ', $at(OrderStage::PaymentVerified)],
                ['Lafaz Akad', 'Akad sempurna direkodkan', $at(OrderStage::AkadDone)],
                ['Agihan Negara', 'Diagihkan ke vendor pelaksana', $at(OrderStage::VendorAssigned)],
                ['Pelaksanaan & Laporan', 'Ibadah dilaksana, laporan disahkan', $at(OrderStage::ReportVerified)],
                ['AWB & Postage', 'Sijil dihantar kepada pelanggan', $at(OrderStage::AwbGenerated)],
            ];
        }

        return view('livewire.orders.completed', ['detailOrder' => $order, 'timeline' => $timeline]);
    }
}
