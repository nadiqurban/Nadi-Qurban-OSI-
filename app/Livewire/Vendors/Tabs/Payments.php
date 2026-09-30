<?php

namespace App\Livewire\Vendors\Tabs;

use App\Actions\Vendors\VendorPayments;
use App\Enums\PoStatus;
use App\Models\VendorPayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Bayaran tab (HQ only; read-only for the Vendor PIC): payment records per PO,
 * stepper, payment information, receipt/advice upload and "Sah Bayaran".
 */
class Payments extends VendorTab
{
    use WithFileUploads;

    #[Url(as: 'bayaran', except: null)]
    public ?int $paymentId = null;

    public string $paymentDate = '';

    public string $approvedBy = '';

    public string $bank = '';

    public string $reference = '';

    /** @var TemporaryUploadedFile|null */
    public $receipt = null;

    /** @var TemporaryUploadedFile|null */
    public $advice = null;

    /** @return Collection<int, VendorPayment> */
    private function payments(): Collection
    {
        return VendorPayment::query()->where('vendor_id', $this->vendorId)
            ->whereHas('purchaseOrder', fn ($q) => $q->where('status', '!=', PoStatus::Cancelled)
                ->when($this->user()->isVendorPic(), fn ($w) => $w->where('status', '!=', PoStatus::Draft)))
            ->with('purchaseOrder')->latest('id')->get();
    }

    public function select(int $paymentId): void
    {
        $this->paymentId = $paymentId;
        $this->loadForm();
    }

    private function current(): ?VendorPayment
    {
        $all = $this->payments();

        return $all->firstWhere('id', $this->paymentId) ?? $all->first();
    }

    private function loadForm(): void
    {
        $p = $this->current();
        $this->paymentDate = $p?->payment_date?->toDateString() ?? '';
        $this->approvedBy = (string) ($p?->approved_by_name ?: $this->user()->name);
        $this->bank = (string) $p?->bank;
        $this->reference = (string) $p?->reference;
        $this->reset('receipt', 'advice');
        $this->resetValidation();
    }

    public function mount(int $vendorId): void
    {
        parent::mount($vendorId);
        $this->loadForm();
    }

    public function setBank(string $bank): void
    {
        $this->bank = $bank;
    }

    public function save(VendorPayments $payments): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate([
            'paymentDate' => ['nullable', 'date'],
            'approvedBy' => ['nullable', 'string', 'max:120'],
            'bank' => ['nullable', 'string', 'max:60'],
            'reference' => ['nullable', 'string', 'max:60'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'advice' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [], ['paymentDate' => 'tarikh bayaran', 'reference' => 'no. rujukan', 'receipt' => 'gambar resit', 'advice' => 'payment advice']);

        $payment = $this->current() ?? abort(404);

        try {
            $payments->saveInfo($payment, [
                'payment_date' => $this->paymentDate ?: null,
                'approved_by_name' => trim($this->approvedBy) ?: null,
                'bank' => trim($this->bank) ?: null,
                'reference' => trim($this->reference) ?: null,
            ], $this->receipt, $this->advice, $this->user());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->reset('receipt', 'advice');
        $this->dispatch('toast', message: 'Maklumat bayaran disimpan.');
    }

    public function removeFile(string $collection, VendorPayments $payments): void
    {
        abort_unless($this->canManage(), 403);

        try {
            $payments->removeFile($this->current() ?? abort(404), $collection, $this->user());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->dispatch('toast', message: 'Resit dibuang. Sila muat naik semula.', tone: 'info');
    }

    public function confirm(VendorPayments $payments): void
    {
        abort_unless($this->canManage() && VendorPayments::canConfirm($this->user()), 403);

        try {
            $payments->confirm($this->current() ?? abort(404), $this->user());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->dispatch('toast', message: 'Bayaran disahkan — Payment Completed.');
    }

    public function reopen(VendorPayments $payments): void
    {
        abort_unless($this->canManage() && VendorPayments::canConfirm($this->user()), 403);

        $payments->reopen($this->current() ?? abort(404), $this->user());
        $this->dispatch('toast', message: 'Bayaran dibuka semula untuk diedit.', tone: 'info');
    }

    public function render(): mixed
    {
        $payment = $this->current();

        return view('livewire.vendors.tabs.payments', [
            'payments' => $this->payments(),
            'payment' => $payment,
            'canManage' => $this->canManage(),
            'canConfirm' => $this->canManage() && VendorPayments::canConfirm($this->user()),
            'receiptMedia' => $payment?->getFirstMedia('receipt'),
            'adviceMedia' => $payment?->getFirstMedia('advice'),
            'banks' => VendorPayment::BANKS,
        ]);
    }
}
