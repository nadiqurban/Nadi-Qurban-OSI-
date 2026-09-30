<?php

namespace App\Livewire\Vendors\Tabs;

use App\Actions\Vendors\PurchaseOrders as PurchaseOrderActions;
use App\Enums\PoStatus;
use App\Enums\Service;
use App\Livewire\Concerns\SelectsRows;
use App\Models\PurchaseOrder;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Purchase Order tab: list (with checkboxes), "Cipta PO" form, detail with the
 * editable rate table / addresses / notes, status stepper, Vendor Acceptance and
 * the printable PO receipt.
 *
 * @property-read Collection<int, PurchaseOrder> $orders
 */
class PurchaseOrders extends VendorTab
{
    use SelectsRows;

    #[Url(as: 'po', except: null)]
    public ?int $poId = null;

    #[Url(as: 'cipta', except: false)]
    public bool $creating = false;

    public bool $showReceipt = false;

    /** @var array<string, mixed> Cipta PO form */
    public array $create = [];

    /** @var array<string, mixed> Detail editable fields */
    public array $edit = [];

    public function mount(int $vendorId): void
    {
        parent::mount($vendorId);
        $this->resetCreate();

        if ($this->creating && ! $this->canManage()) {
            $this->creating = false;
        }

        if ($this->poId) {
            $this->loadEdit();
        }
    }

    /** @return Collection<int, PurchaseOrder> */
    #[Computed]
    public function orders(): Collection
    {
        return PurchaseOrder::query()->where('vendor_id', $this->vendorId)
            ->when($this->user()->isVendorPic(), fn ($q) => $q->where('status', '!=', PoStatus::Draft))
            ->with('payment')->latest('id')->get();
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->orders->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    private function po(): PurchaseOrder
    {
        /** @var PurchaseOrder */
        return PurchaseOrder::query()->where('vendor_id', $this->vendorId)
            ->when($this->user()->isVendorPic(), fn ($q) => $q->where('status', '!=', PoStatus::Draft))
            ->with(['vendor.country', 'acceptedBy', 'creator', 'payment'])
            ->findOrFail($this->poId);
    }

    // ------------------------------------------------------------ navigation

    public function open(int $poId): void
    {
        $this->poId = $poId;
        $this->creating = false;
        $this->loadEdit();
    }

    public function back(): void
    {
        $this->poId = null;
        $this->creating = false;
        $this->showReceipt = false;
    }

    public function startCreate(): void
    {
        abort_unless($this->canManage(), 403);
        $this->resetCreate();
        $this->poId = null;
        $this->creating = true;
    }

    private function resetCreate(): void
    {
        $this->create = ['service' => Service::Qurban->value, 'animal' => '', 'quantity' => '', 'unit_price' => '', 'currency' => 'RM', 'date' => '', 'notes' => ''];
        $this->resetValidation();
    }

    public function setCreateCurrency(string $currency): void
    {
        $this->create['currency'] = in_array($currency, PurchaseOrder::CURRENCIES, true) ? $currency : 'RM';
    }

    private function loadEdit(): void
    {
        $po = $this->po();
        $this->edit = [
            'service_label' => $po->animal_label,
            'quantity' => (string) $po->quantity,
            'unit_price' => number_format($po->unit_price_minor / 100, 2, '.', ''),
            'billing' => (string) $po->billing_address,
            'shipping' => (string) $po->shipping_address,
            'notes' => (string) $po->notes,
        ];
        $this->resetValidation();
    }

    // ------------------------------------------------------------ actions

    public function saveCreate(PurchaseOrderActions $actions): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate([
            'create.service' => ['required', Rule::enum(Service::class)],
            'create.animal' => ['required', 'string', 'max:60'],
            'create.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'create.unit_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'create.currency' => ['required', Rule::in(PurchaseOrder::CURRENCIES)],
            'create.date' => ['nullable', 'date'],
            'create.notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'create.service' => 'servis', 'create.animal' => 'haiwan', 'create.quantity' => 'kuantiti',
            'create.unit_price' => 'harga seunit', 'create.date' => 'tarikh pelaksanaan', 'create.notes' => 'nota',
        ]);

        try {
            $po = $actions->create($this->vendor(), [
                'service' => $this->create['service'],
                'animal_label' => trim((string) $this->create['animal']),
                'quantity' => (int) $this->create['quantity'],
                'unit_price' => (float) $this->create['unit_price'],
                'currency' => $this->create['currency'],
                'implementation_date' => $this->create['date'] ?: null,
                'notes' => trim((string) $this->create['notes']) ?: null,
            ], $this->user());
        } catch (ValidationException $e) {
            $this->addError('create.animal', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->creating = false;
        unset($this->orders);
        $this->dispatch('vendor-updated');
        $this->dispatch('toast', message: "Draft PO {$po->po_no} berjaya disimpan.");
    }

    public function saveDetail(PurchaseOrderActions $actions): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate([
            'edit.service_label' => ['required', 'string', 'max:60'],
            'edit.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'edit.unit_price' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'edit.billing' => ['nullable', 'string', 'max:1000'],
            'edit.shipping' => ['nullable', 'string', 'max:1000'],
            'edit.notes' => ['nullable', 'string', 'max:2000'],
        ], [], ['edit.service_label' => 'servis', 'edit.quantity' => 'kuantiti', 'edit.unit_price' => 'kadar harga']);

        try {
            $actions->update($this->po(), [
                'service_label' => trim((string) $this->edit['service_label']),
                'quantity' => (int) $this->edit['quantity'],
                'unit_price' => (float) $this->edit['unit_price'],
                'billing_address' => trim((string) $this->edit['billing']) ?: null,
                'shipping_address' => trim((string) $this->edit['shipping']) ?: null,
                'notes' => trim((string) $this->edit['notes']) ?: null,
            ], $this->user());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        unset($this->orders);
        $this->dispatch('toast', message: 'Maklumat PO disimpan.');
    }

    public function send(PurchaseOrderActions $actions): void
    {
        abort_unless($this->canManage(), 403);
        $this->runTransition(fn () => $actions->send($this->po(), $this->user()), 'PO dihantar kepada vendor.');
    }

    public function accept(PurchaseOrderActions $actions): void
    {
        // Vendor PIC of this vendor (guarded in VendorTab + action) or HQ.
        abort_unless($this->user()->isVendorPic() || $this->canManage(), 403);
        $this->runTransition(fn () => $actions->accept($this->po(), $this->user()), 'PO diterima. Terima kasih!');
    }

    public function cancel(int $poId, PurchaseOrderActions $actions): void
    {
        abort_unless($this->canManage(), 403);
        $this->poId ??= $poId;
        $target = PurchaseOrder::query()->where('vendor_id', $this->vendorId)->findOrFail($poId);
        $this->runTransition(fn () => $actions->cancel($target, $this->user()), "PO {$target->po_no} dibatalkan.");
    }

    private function runTransition(callable $action, string $message): void
    {
        try {
            $action();
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        unset($this->orders);
        $this->dispatch('vendor-updated');
        $this->dispatch('toast', message: $message);
    }

    public function render(): mixed
    {
        $year = (int) app(Settings::class)->get('season.year', now()->year);
        $next = (int) (DB::table('sequences')->where('name', 'po-'.$year)->value('next_value') ?? 1);

        return view('livewire.vendors.tabs.purchase-orders', [
            'v' => $this->vendor(),
            'po' => $this->poId ? $this->po() : null,
            'canManage' => $this->canManage(),
            'isPic' => $this->user()->isVendorPic(),
            'nextPo' => sprintf('NQ-PO-%d-%04d', $year, $next),
            'usdRate' => $this->usdRate(),
        ]);
    }
}
