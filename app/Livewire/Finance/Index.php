<?php

namespace App\Livewire\Finance;

use App\Actions\Finance\CreateQuotation;
use App\Actions\Finance\Invoices;
use App\Enums\InvoiceStatus;
use App\Enums\Module;
use App\Enums\VendorPaymentStatus;
use App\Exports\TableExport;
use App\Livewire\Concerns\SelectsRows;
use App\Livewire\Forms\FinanceDocForm;
use App\Models\Invoice;
use App\Models\User;
use App\Models\VendorPayment;
use App\Support\FinanceStats;
use App\Support\Sequence;
use App\Support\Settings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pengurusan Kewangan (Kewangan.dc.html list view): KPIs, Aliran Tunai,
 * Kaedah Bayaran, invoice table, "Rekod PO — Payment Completed", and the
 * Cipta Invois / Quotation modals with A4 preview.
 *
 * @property-read LengthAwarePaginator<int, Invoice> $invoices
 * @property-read Collection<int, VendorPayment> $poRecords
 */
#[Layout('layouts::app')]
#[Title('Kewangan')]
class Index extends Component
{
    use SelectsRows, WithPagination;

    #[Url(as: 'tab', except: 'semua')]
    public string $tab = 'semua';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    /** @var list<int> */
    public array $selectedPo = [];

    public FinanceDocForm $draft;

    public bool $showDraft = false;

    public bool $showPreview = false;

    public ?int $poId = null;

    public bool $showPo = false;

    public bool $showReceipt = false;

    public function updated(string $property): void
    {
        if (in_array($property, ['tab', 'search', 'status'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    /** @return Builder<Invoice> */
    private function query(): Builder
    {
        $term = trim($this->search);

        return Invoice::query()
            ->when($this->tab === 'dibayar', fn (Builder $q) => $q->where('status', InvoiceStatus::Paid))
            ->when($this->tab === 'tertunggak', fn (Builder $q) => $q->where('status', '!=', InvoiceStatus::Paid))
            ->when(InvoiceStatus::tryFrom($this->status), fn (Builder $q, InvoiceStatus $s) => $q->where('status', $s))
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('invoice_no', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('order_no', 'like', "%{$term}%")));
    }

    /** @return LengthAwarePaginator<int, Invoice> */
    #[Computed]
    public function invoices(): LengthAwarePaginator
    {
        return $this->query()->orderByDesc('issue_date')->orderByDesc('invoice_no')->paginate(10);
    }

    /** @return Collection<int, VendorPayment> */
    #[Computed]
    public function poRecords(): Collection
    {
        return VendorPayment::query()
            ->where('status', VendorPaymentStatus::Completed)
            ->with(['purchaseOrder', 'vendor', 'media'])
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->limit(200)->get();
    }

    #[Computed]
    public function activePo(): ?VendorPayment
    {
        return $this->poId ? $this->poRecords->firstWhere('id', $this->poId) : null;
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return collect($this->invoices->items())->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function toggleAllPo(): void
    {
        $ids = $this->poRecords->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->selectedPo = $this->allPoSelected() ? [] : $ids;
    }

    public function allPoSelected(): bool
    {
        $ids = $this->poRecords->pluck('id')->map(fn ($id) => (int) $id)->all();

        return $ids !== [] && array_diff($ids, array_map('intval', $this->selectedPo)) === [];
    }

    // ---------------------------------------------------------------- PO view

    public function viewPo(int $paymentId, bool $receipt = false): void
    {
        $this->authorize(Module::Finance->viewPermission());
        $this->poId = $paymentId;
        unset($this->activePo);
        $this->showPo = ! $receipt;
        $this->showReceipt = $receipt;
    }

    public function openReceipt(): void
    {
        $this->showPo = false;
        $this->showReceipt = true;
    }

    // ---------------------------------------------------------- draft modals

    public function openDraft(string $type): void
    {
        $this->authorize(Module::Finance->managePermission());
        $this->draft->start($type);
        $this->showPreview = false;
        $this->showDraft = true;
    }

    public function addItem(): void
    {
        $this->draft->addItem();
    }

    public function removeItem(int $index): void
    {
        $this->draft->removeItem($index);
    }

    public function preview(): void
    {
        $this->authorize(Module::Finance->managePermission());
        $this->showPreview = true;
    }

    public function nextNumber(): string
    {
        $year = (int) app(Settings::class)->get('season.year', now()->year);

        return $this->draft->type === 'quote'
            ? sprintf('QUO-%d-%04d', $year, Sequence::peek("quotation-{$year}", 76))
            : sprintf('INV-%d-%04d', $year, Sequence::peek("invoice-{$year}", 149));
    }

    public function save(Invoices $invoices, CreateQuotation $quotations): mixed
    {
        $this->authorize(Module::Finance->managePermission());
        $this->validate($this->draft->validationRules(), [], [
            'draft.name' => 'Nama Pelanggan',
            'draft.due' => 'Tarikh Tempoh',
            'draft.company.name' => 'Nama Syarikat',
            'draft.items.*.description' => 'Perkara',
            'draft.items.*.qty' => 'Kuantiti',
            'draft.items.*.price' => 'Harga',
        ]);

        if ($this->draft->type === 'quote') {
            $quote = $quotations->handle($this->draft->payload(), $this->actor());
            $this->showDraft = $this->showPreview = false;
            $this->dispatch('toast', message: "Quotation {$quote->quotation_no} dicipta.");

            return redirect()->route('finance.quotation', ['quotation' => $quote, 'muat-turun' => 1]);
        }

        $invoice = $invoices->create($this->draft->payload(), $this->actor());
        $this->showDraft = $this->showPreview = false;
        unset($this->invoices);
        $this->dispatch('toast', message: "Invois {$invoice->invoice_no} dicipta.");

        return null;
    }

    /** "Muat Turun PDF" from the draft preview (nothing is saved). */
    public function downloadDraft(): BinaryFileResponse
    {
        $this->authorize(Module::Finance->managePermission());
        $doc = $this->draft->document($this->nextNumber());
        $path = storage_path('app/private/tmp/'.uniqid('draft-', true).'.pdf');
        @mkdir(dirname($path), 0775, true);
        Pdf::view('pdf.finance-doc', ['doc' => $doc])->format('a4')->save($path);

        return response()->download($path, $doc->number.'.pdf')->deleteFileAfterSend();
    }

    // --------------------------------------------------------------- exports

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Finance->viewPermission());

        $ids = array_map('intval', $this->selected);
        $rows = $this->query()->when($ids !== [], fn (Builder $q) => $q->whereIn('id', $ids))
            ->orderByDesc('issue_date')->cursor()
            ->map(fn (Invoice $v) => [$v->invoice_no, $v->customer_name, $v->order_no ?? '-', rm($v->total_sen), tarikh($v->issue_date), tarikh($v->due_date), $v->status->label()]);

        return Excel::download(new TableExport('Invois',
            ['No. Invois', 'Pelanggan', 'No. Tempahan', 'Jumlah', 'Tarikh', 'Tarikh Tempoh', 'Status'],
            $rows, [16, 28, 18, 12, 14, 14, 12]), 'Senarai-Invois-Nadi-Qurban.xlsx');
    }

    public function exportPo(): BinaryFileResponse
    {
        $this->authorize(Module::Finance->viewPermission());

        $ids = array_map('intval', $this->selectedPo);
        $rows = $this->poRecords
            ->when($ids !== [], fn ($c) => $c->whereIn('id', $ids))
            ->map(fn (VendorPayment $p) => [
                $p->purchaseOrder->po_no, $p->vendor->name, rm($p->amount_sen), tarikh($p->purchaseOrder->created_at),
                tarikh($p->payment_date), $p->bank ?? '-', $p->reference ?? '-', $p->approved_by_name ?? '-', 'Payment Completed',
            ]);

        return Excel::download(new TableExport('Rekod PO',
            ['No. PO', 'Vendor', 'Jumlah', 'Tarikh PO', 'Tarikh Bayar', 'Bank', 'No. Rujukan', 'Diluluskan Oleh', 'Status'],
            $rows, [18, 26, 12, 14, 14, 14, 14, 20, 18]), 'Rekod-PO-Payment-Completed.xlsx');
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        $stats = app(FinanceStats::class);
        $total = $stats->totalCollected();

        return view('livewire.finance.index', [
            'canManage' => auth()->user()?->can(Module::Finance->managePermission()) ?? false,
            'kpis' => $stats->kpis(),
            'cashflow' => $stats->cashflow(),
            'methods' => $stats->methods(),
            'donutTitle' => $total >= 100_000_000 ? 'RM '.number_format($total / 100 / 1_000_000, 1).'j' : rm_short($total),
            'counts' => [
                'semua' => Invoice::query()->count(),
                'dibayar' => Invoice::query()->where('status', InvoiceStatus::Paid)->count(),
                'tertunggak' => Invoice::query()->where('status', '!=', InvoiceStatus::Paid)->count(),
            ],
        ]);
    }
}
