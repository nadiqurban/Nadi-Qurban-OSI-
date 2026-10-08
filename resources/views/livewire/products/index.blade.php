@php $canManage = $this->canManage(); @endphp

<div>
    <x-ui.page-header title="Produk" subtitle="Urus katalog produk ibadah & harga mengikut negara pelaksanaan." :breadcrumb="['Operasi', 'Produk']">
        @if ($canManage)
            <x-slot:actions>
                <x-ui.button icon="plus" mobile-block wire:click="create">Produk Baharu</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <x-ui.tabs :items="$tabs" :active="$service" wire-model="service" class="mb-4" />

    @if ($this->products->isEmpty())
        <x-ui.card padding="p-0">
            <x-ui.empty-state icon="package" title="Tiada produk">
                @if ($canManage) Klik <b class="text-ink-2">Produk Baharu</b> untuk menambah produk ke katalog. @endif
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-[18px] xl:grid-cols-[repeat(auto-fill,minmax(280px,1fr))]">
            @foreach ($this->products as $p)
                <article wire:key="product-{{ $p->id }}" class="flex min-w-0 flex-col overflow-hidden rounded-[14px] border border-border bg-surface">
                    <div class="relative flex h-[120px] items-center justify-center {{ $p->animal->cardClasses() }}">
                        <span class="{{ $p->iconClass() }} size-[52px]" role="img" aria-label="{{ $p->iconLabel() }}"></span>
                        <span class="absolute top-3 right-3 rounded-[20px] bg-white px-[11px] py-1 text-[11px] font-bold {{ $p->animal->inkClass() }}">{{ $p->package->name }}</span>
                    </div>
                    <div class="flex flex-1 flex-col gap-1 px-[18px] py-4">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-[15px] font-bold text-ink">{{ $p->name }}</h3>
                            <span @class([
                                'shrink-0 rounded-[20px] px-[10px] py-[3px] text-[11px] font-semibold',
                                'bg-success-soft text-success' => $p->is_active,
                                'bg-neutral-soft text-neutral' => ! $p->is_active,
                            ])>{{ $p->is_active ? 'Aktif' : 'Tidak Aktif' }}</span>
                        </div>
                        <div class="font-mono text-[11px] font-semibold text-primary dark:text-[#c9ce93]">{{ $p->code }}</div>
                        <div class="text-[12.5px] text-faint">{{ $p->animal->label() }} &middot; {{ $p->package->name }} &middot; {{ $p->country->name }}</div>
                        @if ($p->description)
                            <div class="mt-1 text-[12px] leading-[1.5] text-muted">{{ $p->description }}</div>
                        @endif
                        <div class="mt-auto flex items-end justify-between pt-[14px]">
                            <div>
                                <div class="text-[11px] text-faint">Harga</div>
                                <div class="text-[19px] font-extrabold text-primary dark:text-[#c9ce93]">{{ rm($p->price_sen) }}</div>
                            </div>
                            <div class="flex items-end gap-3">
                                <div class="text-right">
                                    <div class="text-[11px] text-faint">Komisen</div>
                                    <div class="text-[14px] font-bold text-gold-ink">{{ $p->commission_sen ? rm($p->commission_sen) : '-' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] text-faint">Stok</div>
                                    <div class="text-[14px] font-bold {{ $p->stockClass() }}">{{ $p->stock }} unit</div>
                                </div>
                                @if ($canManage)
                                    <div class="flex gap-[7px]">
                                        <button type="button" wire:click="edit({{ $p->id }})" title="Edit" aria-label="Edit {{ $p->name }}"
                                                class="flex size-11 items-center justify-center rounded-[8px] border border-border text-primary md:size-8"><i class="ph ph-pencil-simple text-[15px]"></i></button>
                                        <button type="button" wire:click="delete({{ $p->id }})" wire:confirm="Buang produk {{ $p->name }}?" title="Buang" aria-label="Buang {{ $p->name }}"
                                                class="flex size-11 items-center justify-center rounded-[8px] border border-[#F7CFCF] text-danger md:size-8"><i class="ph ph-trash text-[15px]"></i></button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- Produk Baharu / Edit --}}
    <x-ui.modal wire:model="showForm" :title="$editingId ? 'Edit Produk' : 'Produk Baharu'"
                :subtitle="$editingId ? $name : 'Tambah produk ke katalog'" icon="package" max-width="520px" :footer-border="false">
        <form id="product-form" wire:submit="save" class="grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
            <x-ui.field label="Nama Produk" wire:model="name" placeholder="cth. Qurban Lembu Uganda" span />
            <x-ui.field label="Kod Produk" hint="(auto)" :value="$this->previewCode()" readonly
                        class="bg-bg font-mono font-bold !text-primary" />
            <x-ui.field label="Servis" as="select" wire:model.live="serviceField">
                @foreach (\App\Enums\Service::cases() as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Haiwan" as="select" wire:model.live="animal">
                @foreach (\App\Enums\Animal::cases() as $a)
                    <option value="{{ $a->value }}">{{ $a->label() }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Pakej" as="select" wire:model.live="packageId">
                @foreach ($this->packages as $pkg)
                    <option value="{{ $pkg->id }}">{{ $pkg->name }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Negara" as="select" wire:model="countryId" span>
                <option value="">Pilih negara pelaksanaan</option>
                @foreach ($this->countries as $c)
                    <option value="{{ $c->id }}">{{ $c->flag }} {{ $c->name }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Harga (RM)" wire:model="price" placeholder="cth. 3500" inputmode="decimal" />
            <x-ui.field label="Stok" type="number" min="0" wire:model="stock" placeholder="cth. 50" inputmode="numeric" />
            <x-ui.field label="Komisen Ejen (RM)" wire:model="commission" placeholder="cth. 50" inputmode="decimal" />
            <x-ui.field label="Jenis Kuantiti" as="select" wire:model="unit">
                @foreach (\App\Models\Product::UNITS as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Keterangan" as="textarea" rows="2" wire:model="description" placeholder="Butiran produk..." span />
            <div class="col-span-full">
                <x-ui.checkbox wire:model="isActive" label="Produk aktif (boleh dipilih dalam tempahan)" />
            </div>
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button type="submit" form="product-form" icon="check">Simpan Produk</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
