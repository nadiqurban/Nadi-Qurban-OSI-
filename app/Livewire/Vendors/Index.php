<?php

namespace App\Livewire\Vendors;

use App\Actions\Vendors\SaveVendor;
use App\Enums\Module;
use App\Enums\PoStatus;
use App\Enums\VendorStatus;
use App\Exports\TableExport;
use App\Livewire\Concerns\ManagesVendorForm;
use App\Livewire\Concerns\SelectsRows;
use App\Livewire\Forms\VendorForm;
use App\Models\Country;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Settings;
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
 * Pengurusan Vendor (Vendor.dc.html list view): vendor cards + "PO Dicipta" table.
 * Vendor PIC users are sent straight to their own vendor profile.
 *
 * @property-read Collection<int, Vendor> $vendors
 * @property-read Collection<int, PurchaseOrder> $orders
 */
#[Layout('layouts::app')]
#[Title('Vendor')]
class Index extends Component
{
    use ManagesVendorForm;
    use SelectsRows;

    #[Url(as: 'tab', except: 'vendor')]
    public string $tab = 'vendor';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'negara', except: '')]
    public string $country = '';

    #[Url(as: 'haiwan', except: '')]
    public string $animal = '';

    #[Url(as: 'status', except: '')]
    public string $status = '';

    public VendorForm $vendorForm;

    public function mount(): mixed
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isVendorPic() && $user->vendor_id) {
            return $this->redirectRoute('vendors.show', ['vendor' => $user->vendor_id, 'tab' => 'po'], navigate: false);
        }

        return null;
    }

    /** @return Collection<int, Vendor> */
    #[Computed]
    public function vendors(): Collection
    {
        $term = trim($this->search);

        return Vendor::query()
            ->withPoStats()
            ->with(['country', 'purchaseOrders' => fn ($q) => $q->where('status', '!=', PoStatus::Cancelled)->limit(50)])
            ->when($term !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('name', 'like', "%{$term}%")->orWhere('company', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")))
            ->when($this->country !== '', fn (Builder $q) => $q->where('country_id', (int) $this->country))
            ->when($this->animal !== '', fn (Builder $q) => $q->whereJsonContains('animals', $this->animal))
            ->when(VendorStatus::tryFrom($this->status), fn (Builder $q, VendorStatus $s) => $q->where('status', $s))
            ->orderBy('code')
            ->get();
    }

    /** @return Collection<int, PurchaseOrder> */
    #[Computed]
    public function orders(): Collection
    {
        return PurchaseOrder::query()->with(['vendor', 'payment'])->latest('id')->limit(500)->get();
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    public function stats(): array
    {
        $active = array_values(array_filter(PoStatus::cases(), fn (PoStatus $s) => $s->isActive()));

        return [
            ['icon' => 'truck', 'tone' => 'primary', 'value' => (string) Vendor::query()->count(), 'label' => 'Jumlah Vendor'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => (string) Vendor::query()->where('status', VendorStatus::Active)->count(), 'label' => 'Aktif'],
            ['icon' => 'clipboard-text', 'tone' => 'gold', 'value' => (string) PurchaseOrder::query()->whereIn('status', $active)->count(), 'label' => 'PO Aktif'],
            ['icon' => 'globe-hemisphere-west', 'tone' => 'info', 'value' => (string) Vendor::query()->distinct()->count('country_id'), 'label' => 'Negara'],
        ];
    }

    /** @return list<int> */
    protected function selectableIds(): array
    {
        return $this->orders->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'country', 'animal', 'status'], true)) {
            unset($this->vendors);
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'country', 'animal', 'status');
    }

    protected function vendorSaved(Vendor $vendor, bool $created): void
    {
        unset($this->vendors);
        $this->dispatch('toast', message: $created ? "Vendor {$vendor->name} didaftarkan ({$vendor->code})." : 'Maklumat vendor disimpan.');
    }

    public function suspend(int $vendorId, SaveVendor $save): void
    {
        $this->authorize(Module::Vendors->managePermission());

        $vendor = Vendor::query()->findOrFail($vendorId);
        $save->suspend($vendor, $this->actor());
        unset($this->vendors);
        $this->dispatch('toast', message: "{$vendor->name} dinyahaktifkan.", tone: 'info');
    }

    public function delete(int $vendorId, SaveVendor $save): void
    {
        $this->authorize(Module::Vendors->managePermission());

        $vendor = Vendor::query()->withCount(['purchaseOrders as open_po' => fn ($q) => $q->whereIn('status', [PoStatus::Sent, PoStatus::Accepted, PoStatus::InProgress])])->findOrFail($vendorId);

        if ($vendor->open_po > 0) {
            $this->dispatch('toast', message: "{$vendor->name} masih ada PO aktif — batalkan atau selesaikan PO dahulu.", tone: 'danger');

            return;
        }

        $save->delete($vendor, $this->actor());
        unset($this->vendors);
        $this->dispatch('toast', message: "{$vendor->name} dibuang.", tone: 'info');
    }

    public function export(): BinaryFileResponse
    {
        $this->authorize(Module::Vendors->viewPermission());

        $rows = $this->vendors->map(fn (Vendor $v) => [
            $v->code, $v->name, $v->country->name, $v->status->label(), implode(', ', $v->animals ?? []),
            (int) $v->active_po_count, $v->completionLabel(), (string) $v->rating, $v->phone, $v->email,
        ]);

        return Excel::download(new TableExport('Vendor',
            ['Kod', 'Nama Vendor', 'Negara', 'Status', 'Jenis Haiwan', 'PO Aktif', 'Siap %', 'Rating', 'Telefon', 'Emel'],
            $rows, [9, 24, 14, 12, 18, 10, 9, 8, 18, 24]), 'Senarai-Vendor-Nadi-Qurban.xlsx');
    }

    private function actor(): User
    {
        /** @var User */
        return auth()->user();
    }

    public function render(): mixed
    {
        return view('livewire.vendors.index', [
            'canManage' => auth()->user()?->can(Module::Vendors->managePermission()) ?? false,
            'countries' => Country::query()->whereIn('id', Vendor::query()->select('country_id'))->orderBy('sort')->pluck('name', 'id')->all(),
            'usdRate' => (float) app(Settings::class)->get('finance.usd_rate', '4.70'),
        ]);
    }
}
