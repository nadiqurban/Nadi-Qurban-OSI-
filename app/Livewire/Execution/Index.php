<?php

namespace App\Livewire\Execution;

use App\Actions\Pipeline\SubmitExecutionReport;
use App\Actions\Pipeline\VerifyExecutionReport;
use App\Enums\Animal;
use App\Enums\Module;
use App\Enums\OrderStage;
use App\Exports\TableExport;
use App\Livewire\Concerns\SelectsRows;
use App\Models\Country;
use App\Models\ExecutionReport;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pelaksanaan & Laporan (Pelaksanaan & Laporan.dc.html): the vendor (or HQ) uploads
 * photo/video evidence, HQ reviews and "Sahkan Selesai" → ready for AWB.
 *
 * @property-read Collection<int, Order> $rows
 * @property-read array{pending: int, review: int, done: int} $counts
 */
#[Layout('layouts::app')]
#[Title('Pelaksanaan & Laporan')]
class Index extends Component
{
    use SelectsRows;
    use WithFileUploads;

    private const TABS = [
        'menunggu' => [OrderStage::Executing],
        'semakan' => [OrderStage::ReportUploaded],
        'selesai' => [OrderStage::ReportVerified, OrderStage::FinalReport, OrderStage::AwbGenerated, OrderStage::CertificatePosted, OrderStage::Completed],
    ];

    #[Url(as: 'tab', except: 'menunggu')]
    public string $tab = 'menunggu';

    #[Url(as: 'servis', except: '')]
    public string $animal = '';

    #[Url(as: 'negara', except: '')]
    public string $country = '';

    #[Url(as: 'vendor', except: '')]
    public string $vendor = '';

    public bool $showReport = false;

    public ?int $reportOrderId = null;

    /** @var list<UploadedFile> */
    public array $images = [];

    /** @var list<UploadedFile> */
    public array $videos = [];

    public string $notes = '';

    /** @return Builder<Order> */
    private function query(string $tab): Builder
    {
        return Order::query()->visibleTo($this->user())
            ->whereHas('allocation')
            ->whereIn('stage', self::TABS[$tab] ?? self::TABS['menunggu'])
            ->when(Animal::tryFrom($this->animal), fn (Builder $q, Animal $a) => $q->where('animal', $a))
            ->when($this->country !== '', fn (Builder $q) => $q->where('country_id', (int) $this->country))
            ->when($this->vendor !== '', fn (Builder $q) => $q->whereHas('allocation', fn (Builder $a) => $a->where('vendor_id', (int) $this->vendor)));
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function rows(): Collection
    {
        return $this->query($this->tab)
            ->with(['customer', 'country', 'allocation.vendor', 'executionReport'])
            ->latest('updated_at')
            ->limit(500)
            ->get();
    }

    /** @return array{pending: int, review: int, done: int} */
    #[Computed]
    public function counts(): array
    {
        return [
            'pending' => $this->query('menunggu')->count(),
            'review' => $this->query('semakan')->count(),
            'done' => $this->query('selesai')->count(),
        ];
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function stats(): array
    {
        $media = Media::query()->where('model_type', (new ExecutionReport)->getMorphClass())
            ->when($this->user()->isVendorPic(), fn (Builder $q) => $q->whereIn('model_id', ExecutionReport::query()->where('vendor_id', $this->user()->vendor_id)->select('id')))
            ->count();

        return [
            ['icon' => 'shopping-bag', 'tone' => 'neutral', 'value' => (string) $this->counts['pending'], 'label' => 'Menunggu Pelaksanaan'],
            ['icon' => 'magnifying-glass', 'tone' => 'warning', 'value' => (string) $this->counts['review'], 'label' => 'Menunggu Semakan'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => (string) $this->counts['done'], 'label' => 'Selesai Disahkan'],
            ['icon' => 'images', 'tone' => 'info', 'value' => number_format($media), 'label' => 'Jumlah Bukti Dimuat Naik'],
        ];
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->rows->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'animal', 'country', 'vendor'], true)) {
            $this->selected = [];
            unset($this->rows, $this->counts);
        }
    }

    public function clearFilters(): void
    {
        $this->reset('animal', 'country', 'vendor');
    }

    // ------------------------------------------------------------ report modal

    public function openReport(int $orderId): void
    {
        $order = $this->findOrder($orderId);

        $this->reportOrderId = $order->id;
        $this->reset('images', 'videos');
        $this->notes = (string) $order->executionReport?->notes;
        $this->resetValidation();
        $this->showReport = true;
    }

    public function removeUpload(string $collection, int $index): void
    {
        if (in_array($collection, ['images', 'videos'], true)) {
            array_splice($this->{$collection}, $index, 1);
        }
    }

    public function submitReport(SubmitExecutionReport $submit): void
    {
        $this->authorize(Module::Execution->managePermission());

        $this->validate([
            'images' => ['array', 'max:20'],
            'images.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
            'videos' => ['array', 'max:5'],
            'videos.*' => ['file', 'mimes:webm,mp4,mov,webp', 'max:102400'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['images.*' => 'gambar', 'videos.*' => 'video', 'notes' => 'nota']);

        $order = $this->findOrder((int) $this->reportOrderId);

        try {
            $submit->handle($order, $this->images, $this->videos, trim($this->notes) ?: null, $this->user());
        } catch (ValidationException $e) {
            $this->addError('images', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->showReport = false;
        $this->reset('images', 'videos', 'notes');
        $this->tab = 'semakan';
        $this->refresh();
        $this->dispatch('toast', message: "Laporan {$order->order_no} dihantar untuk semakan HQ.");
    }

    public function verifyReport(VerifyExecutionReport $verify): void
    {
        $this->authorize(Module::Execution->managePermission());

        $order = $this->findOrder((int) $this->reportOrderId);

        try {
            $verify->handle($order, $this->user());
        } catch (ValidationException $e) {
            $this->addError('images', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->showReport = false;
        $this->tab = 'selesai';
        $this->refresh();
        $this->dispatch('toast', message: "Laporan {$order->order_no} disahkan — sedia untuk AWB.");
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Execution->viewPermission());

        $status = fn (Order $o) => match (true) {
            $o->stage === OrderStage::Executing => 'Menunggu Pelaksanaan',
            $o->stage === OrderStage::ReportUploaded => 'Menunggu Semakan',
            default => 'Selesai',
        };

        $rows = $this->rows->map(fn (Order $o) => [
            $o->order_no, $o->customer->name, $o->ibadahLabel(), $o->package_name, $o->country->name,
            $o->allocation ? $o->allocation->vendor->name.' — '.$o->allocation->vendor->code : '-', $status($o),
        ]);

        return Excel::download(new TableExport('Pelaksanaan',
            ['No. Tempahan', 'Pelanggan', 'Ibadah', 'Pakej', 'Negara', 'Vendor', 'Status'],
            $rows, [20, 24, 18, 12, 14, 26, 20]), 'Pelaksanaan-Laporan-Nadi-Qurban.xlsx');
    }

    private function findOrder(int $id): Order
    {
        /** @var Order */
        return Order::query()->visibleTo($this->user())
            ->with(['customer', 'country', 'allocation.vendor', 'executionReport.media'])
            ->findOrFail($id);
    }

    private function refresh(): void
    {
        unset($this->rows, $this->counts);
    }

    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $user = $this->user();
        $order = $this->showReport && $this->reportOrderId ? $this->findOrder($this->reportOrderId) : null;

        return view('livewire.execution.index', [
            'canManage' => $user->can(Module::Execution->managePermission()),
            'canVerify' => $user->can(Module::Execution->managePermission()) && ! $user->isVendorPic(),
            'countries' => Country::query()->active()->orderBy('sort')->pluck('name', 'id')->all(),
            'vendorOptions' => Vendor::query()->whereHas('allocations')->orderBy('name')->get()->mapWithKeys(fn (Vendor $v) => [$v->id => $v->name.' — '.$v->code])->all(),
            'reportOrder' => $order,
            'gallery' => $order?->executionReport ? $order->executionReport->media->sortBy('order_column')->values() : collect(),
        ]);
    }
}
