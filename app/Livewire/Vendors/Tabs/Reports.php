<?php

namespace App\Livewire\Vendors\Tabs;

use App\Actions\Vendors\VendorReports;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;

/**
 * Laporan tab: pick a PO, upload images/videos per animal with notes
 * ("Simpan Rekod Sembelihan"), files grouped by animal with preview, and the
 * HQ Verify card (checklist, Sahkan & Tandakan Completed / Minta Semakan Semula).
 */
class Reports extends VendorTab
{
    use WithFileUploads;

    #[Url(as: 'lpo', except: null)]
    public ?int $poId = null;

    public string $animal = 'Lembu';

    /** @var list<UploadedFile> */
    public array $files = [];

    public string $notes = '';

    public bool $showRevision = false;

    public string $revisionNote = '';

    /** @return Collection<int, PurchaseOrder> */
    private function orders(): Collection
    {
        return PurchaseOrder::query()->where('vendor_id', $this->vendorId)
            ->whereIn('status', [PoStatus::Accepted, PoStatus::InProgress, PoStatus::Completed])
            ->with('report.media')->latest('id')->get();
    }

    private function current(): ?PurchaseOrder
    {
        $orders = $this->orders();

        return $orders->firstWhere('id', $this->poId) ?? $orders->first();
    }

    public function mount(int $vendorId): void
    {
        parent::mount($vendorId);
        $this->animal = $this->vendor()->animals[0] ?? 'Lembu';
        $this->notes = (string) $this->current()?->report?->notes;
    }

    public function updatedPoId(): void
    {
        $this->reset('files');
        $this->notes = (string) $this->current()?->report?->notes;
        $this->resetValidation();
    }

    public function setAnimal(string $animal): void
    {
        if (in_array($animal, Vendor::ANIMALS, true)) {
            $this->animal = $animal;
        }
    }

    public function removeUpload(int $index): void
    {
        array_splice($this->files, $index, 1);
    }

    private function canUpload(): bool
    {
        return $this->user()->isVendorPic() || $this->canManage();
    }

    public function submit(VendorReports $reports): void
    {
        abort_unless($this->canUpload(), 403);

        $this->validate([
            'files' => ['array', 'max:30'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov,pdf', 'max:204800'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ], ['files.*.max' => 'Setiap fail maksimum 200MB.'], ['files.*' => 'fail', 'notes' => 'nota']);

        $po = $this->current() ?? abort(404);

        try {
            $reports->submit($po, $this->files, $this->animal, trim($this->notes) ?: null, $this->user());
        } catch (ValidationException $e) {
            $this->addError('files', (string) collect($e->errors())->flatten()->first());

            return;
        }

        $this->reset('files');
        $this->dispatch('vendor-updated');
        $this->dispatch('toast', message: 'Rekod sembelihan disimpan — menunggu semakan HQ.');
    }

    public function verify(VendorReports $reports): void
    {
        abort_unless($this->canManage(), 403);

        $report = $this->current()->report ?? abort(404);

        try {
            $reports->verify($report, $this->user());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->dispatch('vendor-updated');
        $this->dispatch('toast', message: 'Laporan disahkan — PO Completed.');
    }

    public function requestRevision(VendorReports $reports): void
    {
        abort_unless($this->canManage(), 403);

        $this->validate(['revisionNote' => ['required', 'string', 'min:5', 'max:300']], [], ['revisionNote' => 'catatan semakan']);

        try {
            $reports->requestRevision($this->current()->report ?? abort(404), trim($this->revisionNote), $this->user());
        } catch (ValidationException $e) {
            $this->dispatch('toast', message: (string) collect($e->errors())->flatten()->first(), tone: 'danger');

            return;
        }

        $this->showRevision = false;
        $this->revisionNote = '';
        $this->dispatch('toast', message: 'Semakan semula diminta daripada vendor.', tone: 'info');
    }

    public function render(): mixed
    {
        $po = $this->current();
        $report = $po?->report;
        $groups = $report
            ? $report->getMedia('files')->groupBy(fn ($m) => (string) $m->getCustomProperty('animal', 'Lain-lain'))
            : collect();

        return view('livewire.vendors.tabs.reports', [
            'orders' => $this->orders(),
            'po' => $po,
            'report' => $report,
            'groups' => $groups,
            'checklist' => $report?->checklist() ?? ['images' => false, 'videos' => false, 'pdf' => false],
            'canUpload' => $this->canUpload(),
            'canVerify' => $this->canManage(),
            'animals' => $this->vendor()->animals ?: Vendor::ANIMALS,
        ]);
    }
}
