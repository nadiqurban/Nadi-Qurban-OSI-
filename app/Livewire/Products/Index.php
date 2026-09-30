<?php

namespace App\Livewire\Products;

use App\Actions\Catalog\DeleteCatalogItem;
use App\Actions\Catalog\SaveProduct;
use App\Enums\Animal;
use App\Enums\Module;
use App\Enums\Service;
use App\Models\Country;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Produk (Produk.dc.html): catalogue cards, service tabs, create/edit modal, soft delete.
 *
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Package> $packages
 * @property-read Collection<int, Country> $countries
 * @property-read list<array{icon: string, tone: string, value: string, label: string}> $stats
 */
#[Layout('layouts::app')]
#[Title('Produk')]
class Index extends Component
{
    #[Url(as: 'servis', except: '')]
    public string $service = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $serviceField = 'qurban';

    public string $animal = 'lembu';

    public ?int $packageId = null;

    public ?int $countryId = null;

    public string $price = '';

    public string $stock = '';

    public string $description = '';

    public bool $isActive = true;

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->with(['package', 'country'])
            ->when($this->service !== '', fn ($q) => $q->where('service', $this->service))
            ->orderByRaw("CASE service WHEN 'qurban' THEN 1 WHEN 'aqiqah' THEN 2 WHEN 'dam' THEN 3 ELSE 4 END")
            ->orderByRaw("CASE animal WHEN 'lembu' THEN 1 WHEN 'kambing' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();
    }

    /** @return list<array{icon: string, tone: string, value: string, label: string}> */
    #[Computed]
    public function stats(): array
    {
        $all = Product::query();

        return [
            ['icon' => 'package', 'tone' => 'primary', 'value' => (string) (clone $all)->count(), 'label' => 'Jumlah Produk'],
            ['icon' => 'check-circle', 'tone' => 'success', 'value' => (string) (clone $all)->where('is_active', true)->count(), 'label' => 'Produk Aktif'],
            ['icon' => 'cow', 'tone' => 'gold-ink', 'value' => (string) (clone $all)->distinct()->count('service'), 'label' => 'Jenis Servis'],
            ['icon' => 'globe-hemisphere-west', 'tone' => 'info', 'value' => (string) (clone $all)->distinct()->count('country_id'), 'label' => 'Negara'],
        ];
    }

    /** @return Collection<int, Package> */
    #[Computed]
    public function packages(): Collection
    {
        return Package::query()->active()->get();
    }

    /** @return Collection<int, Country> */
    #[Computed]
    public function countries(): Collection
    {
        return Country::query()->active()->get();
    }

    /** Live code preview "QB-LE-DEL" (auto). */
    public function previewCode(): string
    {
        $package = $this->packages->firstWhere('id', $this->packageId);
        $service = Service::tryFrom($this->serviceField);
        $animal = Animal::tryFrom($this->animal);

        return $package && $service && $animal ? Product::makeCode($service, $animal, $package) : '—';
    }

    public function canManage(): bool
    {
        return auth()->user()?->can(Module::Products->managePermission()) ?? false;
    }

    public function create(): void
    {
        $this->authorize(Module::Products->managePermission());

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize(Module::Products->managePermission());

        $p = Product::query()->findOrFail($id);
        $this->resetForm();
        $this->editingId = $p->id;
        $this->name = $p->name;
        $this->serviceField = $p->service->value;
        $this->animal = $p->animal->value;
        $this->packageId = $p->package_id;
        $this->countryId = $p->country_id;
        $this->price = number_format($p->price_sen / 100, $p->price_sen % 100 ? 2 : 0, '.', '');
        $this->stock = (string) $p->stock;
        $this->description = (string) $p->description;
        $this->isActive = $p->is_active;
        $this->showForm = true;
    }

    public function save(SaveProduct $save): void
    {
        $this->authorize(Module::Products->managePermission());

        $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'serviceField' => ['required', Rule::enum(Service::class)],
            'animal' => ['required', Rule::enum(Animal::class)],
            'packageId' => ['required', 'integer', Rule::exists('packages', 'id')],
            'countryId' => ['required', 'integer', Rule::exists('countries', 'id')],
            'price' => ['required', function (string $attr, mixed $value, \Closure $fail) {
                $sen = parse_rm(is_scalar($value) ? (string) $value : null);
                if ($sen === null || $sen <= 0) {
                    $fail('Harga mesti jumlah RM yang sah (cth. 3500 atau 3500.00).');
                }
            }],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:500'],
        ], attributes: [
            'name' => 'nama produk', 'serviceField' => 'servis', 'animal' => 'haiwan', 'packageId' => 'pakej',
            'countryId' => 'negara', 'price' => 'harga', 'stock' => 'stok', 'description' => 'keterangan',
        ]);

        /** @var User $actor */
        $actor = auth()->user();

        $save->handle(
            $this->editingId ? Product::query()->findOrFail($this->editingId) : null,
            [
                'name' => trim($this->name),
                'service' => $this->serviceField,
                'animal' => $this->animal,
                'package_id' => (int) $this->packageId,
                'country_id' => (int) $this->countryId,
                'price_sen' => (int) parse_rm($this->price),
                'stock' => (int) $this->stock,
                'description' => trim($this->description) ?: null,
                'is_active' => $this->isActive,
            ],
            $actor,
        );

        $this->showForm = false;
        $this->resetForm();
        unset($this->products, $this->stats);
    }

    public function delete(int $id, DeleteCatalogItem $delete): void
    {
        $this->authorize(Module::Products->managePermission());

        /** @var User $actor */
        $actor = auth()->user();
        $delete->handle(Product::query()->findOrFail($id), $actor);

        unset($this->products, $this->stats);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'price', 'stock', 'description');
        $this->serviceField = Service::Qurban->value;
        $this->animal = Animal::Cow->value;
        $this->packageId = $this->packages->first()?->id;
        $this->countryId = null;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render(): mixed
    {
        return view('livewire.products.index', [
            'tabs' => collect([['key' => '', 'label' => 'Semua']])
                ->merge(collect(Service::cases())->map(fn (Service $s) => ['key' => $s->value, 'label' => $s->label()]))
                ->all(),
        ]);
    }
}
