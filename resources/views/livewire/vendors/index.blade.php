@php
    $vendorTab = $tab !== 'po';
    $hasFilters = $search !== '' || $country !== '' || $animal !== '' || $status !== '';
@endphp

<div>
    <x-ui.page-header title="Pengurusan Vendor" :breadcrumb="['Operasi', 'Vendor']">
        <x-slot:actions>
            <x-ui.button icon="microsoft-excel-logo" wire:click="export" class="!px-[14px] max-md:flex-1">Eksport</x-ui.button>
            @if ($canManage)
                <x-ui.button icon="plus" wire:click="openRegister" class="max-md:flex-1">Daftar Vendor</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5">
        <x-ui.tabs wire-model="tab" :active="$vendorTab ? 'vendor' : 'po'" :items="[
            ['key' => 'vendor', 'label' => 'Vendor', 'count' => $this->vendors->count()],
            ['key' => 'po', 'label' => 'PO Dicipta', 'count' => $this->orders->count()],
        ]" />
    </div>

    @if ($vendorTab)
        <div class="mb-5 grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
            @foreach ($this->stats() as $s)
                <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
            @endforeach
        </div>

        <x-ui.filter-bar :has-filters="$hasFilters" reset-action="$wire.clearFilters()" :active-count="collect([$country, $animal, $status])->filter()->count()">
            <x-slot:search>
                <label class="flex min-w-[220px] flex-1 items-center gap-[10px] rounded-[9px] border border-border bg-bg px-[14px] py-[10px] max-md:min-h-11">
                    <i class="ph ph-magnifying-glass text-[17px] text-faint"></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama vendor atau syarikat" aria-label="Cari vendor" class="flex-1 bg-transparent text-[13.5px] text-ink outline-none max-md:text-[16px]">
                </label>
            </x-slot:search>
            <x-slot:filters>
                <x-ui.filter-select label="Negara" wire:model.live="country" :options="$countries" />
                <x-ui.filter-select label="Jenis Haiwan" wire:model.live="animal" :options="array_combine(\App\Models\Vendor::ANIMALS, \App\Models\Vendor::ANIMALS)" />
                <x-ui.filter-select label="Status" wire:model.live="status" :options="collect(\App\Enums\VendorStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
            </x-slot:filters>
        </x-ui.filter-bar>

        {{-- Vendor cards --}}
        <div class="grid grid-cols-1 gap-[18px] sm:grid-cols-2 xl:grid-cols-[repeat(auto-fill,minmax(340px,1fr))]">
            @forelse ($this->vendors as $v)
                @php $current = $v->purchaseOrders->first(fn ($po) => $po->status->isActive()) ?? $v->purchaseOrders->first(); @endphp
                <div class="flex min-w-0 flex-col gap-4 rounded-[14px] border border-border bg-surface p-5" wire:key="vendor-{{ $v->id }}">
                    <div class="flex items-center gap-[14px]">
                        <div class="flex size-[52px] shrink-0 items-center justify-center rounded-[12px] text-[17px] font-extrabold {{ $v->avatarClasses() }}">{{ $v->initials() }}</div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[15px] font-bold text-ink">{{ $v->name }}</div>
                            <div class="mt-[3px] flex items-center gap-[5px] text-[12.5px] text-muted"><i class="ph ph-map-pin text-[14px]"></i>{{ $v->country->name }}</div>
                        </div>
                        <div class="flex flex-col items-end gap-1.5">
                            <x-ui.badge :tone="$v->statusTone()" class="!text-[11px] !px-[11px]">{{ $v->status->label() }}</x-ui.badge>
                            <span class="rounded-[5px] border border-[#dfe1cd] bg-primary-soft px-[7px] py-0.5 text-[10.5px] font-bold tracking-[.5px] text-primary-hover">{{ $v->code }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($v->animals ?? [] as $an)
                            <span class="rounded-[6px] bg-primary-soft px-[9px] py-1 text-[11.5px] font-semibold text-primary-hover">{{ $an }}</span>
                        @endforeach
                    </div>
                    <div class="grid grid-cols-3 gap-[10px] border-y border-divider py-[14px]">
                        <div><div class="text-[16px] font-bold text-ink">{{ $v->active_po_count }}</div><div class="mt-0.5 text-[11px] text-faint">PO Aktif</div></div>
                        <div><div class="text-[16px] font-bold text-primary dark:text-[#c9ce93]">{{ $v->completionLabel() }}</div><div class="mt-0.5 text-[11px] text-faint">Siap</div></div>
                        <div><div class="flex items-center gap-[3px] text-[16px] font-bold text-gold"><i class="ph-fill ph-star text-[14px]"></i>{{ $v->rating }}</div><div class="mt-0.5 text-[11px] text-faint">Rating</div></div>
                    </div>
                    <div class="flex items-center gap-[10px] rounded-[10px] bg-bg px-[13px] py-[11px]">
                        <i class="ph ph-truck shrink-0 text-[18px] text-primary"></i>
                        <div class="min-w-0 flex-1"><div class="text-[9.5px] font-bold tracking-[.4px] text-faint uppercase">PO Semasa</div><div class="truncate text-[12.5px] font-bold text-ink">{{ $current?->po_no ?? 'Tiada PO' }}</div></div>
                        @if ($current)
                            <span class="rounded-[20px] px-[9px] py-[3px] text-[10px] font-bold whitespace-nowrap {{ $current->status->cardBadgeClasses() }}">{{ $current->status->label() }}</span>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('vendors.show', $v) }}" wire:navigate class="flex min-h-10 flex-1 items-center justify-center rounded-[8px] bg-primary-soft p-[9px] text-center text-[13px] font-semibold text-primary hover:text-primary max-md:min-h-11">Lihat Profil</a>
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
                            <button type="button" @click="open = !open" :class="open ? 'border-primary text-primary' : 'border-border text-muted'" class="flex size-[38px] items-center justify-center rounded-[8px] border max-md:size-11" aria-label="Menu {{ $v->name }}" :aria-expanded="open"><i class="ph ph-dots-three text-[18px]"></i></button>
                            <div x-cloak x-show="open" x-transition.origin.top.right class="absolute top-11 right-0 z-30 flex w-[180px] flex-col rounded-[10px] border border-border bg-surface p-1.5 shadow-pop">
                                <a href="{{ route('vendors.show', $v) }}" wire:navigate class="flex min-h-10 items-center gap-[10px] rounded-[7px] px-[11px] py-[9px] text-[13px] text-ink-2 hover:bg-bg hover:text-ink-2"><i class="ph ph-eye text-[16px]"></i>Lihat Profil</a>
                                @if ($canManage)
                                    <button type="button" wire:click="openEditVendor({{ $v->id }})" @click="open = false" class="flex min-h-10 items-center gap-[10px] rounded-[7px] px-[11px] py-[9px] text-left text-[13px] text-ink-2 hover:bg-bg"><i class="ph ph-pencil-simple text-[16px]"></i>Edit Vendor</button>
                                    <a href="{{ route('vendors.show', ['vendor' => $v, 'tab' => 'po', 'cipta' => 1]) }}" wire:navigate class="flex min-h-10 items-center gap-[10px] rounded-[7px] px-[11px] py-[9px] text-[13px] text-ink-2 hover:bg-bg hover:text-ink-2"><i class="ph ph-clipboard-text text-[16px]"></i>Cipta PO</a>
                                    @if ($v->status !== \App\Enums\VendorStatus::Suspended)
                                        <button type="button" wire:click="suspend({{ $v->id }})" wire:confirm="Nyahaktifkan {{ $v->name }}?" @click="open = false" class="flex min-h-10 items-center gap-[10px] rounded-[7px] px-[11px] py-[9px] text-left text-[13px] text-danger hover:bg-bg"><i class="ph ph-prohibit text-[16px]"></i>Nyahaktif</button>
                                    @endif
                                    <button type="button" wire:click="delete({{ $v->id }})" wire:confirm="Buang vendor {{ $v->name }}?" @click="open = false" class="flex min-h-10 items-center gap-[10px] rounded-[7px] px-[11px] py-[9px] text-left text-[13px] text-danger hover:bg-bg"><i class="ph ph-trash text-[16px]"></i>Buang</button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-[12px] border border-border bg-surface"><x-ui.empty-state icon="truck" title="Tiada vendor untuk tapisan ini." /></div>
            @endforelse
        </div>
    @else
        {{-- PO Dicipta --}}
        <x-ui.data-table cols="32px 1fr 1fr 0.8fr 0.9fr 0.85fr 1fr 1fr 1fr 0.85fr 0.8fr" min-width="1180px">
            <x-slot:head>
                <x-ui.select-all :checked="$this->allSelected()" />
                <span>No. PO</span><span>Vendor</span><span class="text-right">Jumlah</span><span>Status Bayaran</span><span>Tarikh Bayar</span><span>Diluluskan Oleh</span><span>Bank</span><span>No. Ruj. Transfer</span><span class="text-center">Status PO</span><span class="text-right">Tindakan</span>
            </x-slot:head>
            @foreach ($this->orders as $po)
                @php $pay = $po->payment; @endphp
                <x-ui.tr wire:key="po-{{ $po->id }}">
                    <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                        <x-ui.checkbox value="{{ $po->id }}" wire:model.live="selected" aria-label="Pilih {{ $po->po_no }}" />
                        <a href="{{ route('vendors.show', ['vendor' => $po->vendor_id, 'tab' => 'po', 'po' => $po->id]) }}" wire:navigate class="text-[13px] font-bold text-primary md:hidden">{{ $po->po_no }}</a>
                        <span class="ml-auto md:hidden"><span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold {{ $po->status->badgeClasses() }}">{{ $po->status->label() }}</span></span>
                    </x-ui.td>
                    <x-ui.td mobile="hide"><a href="{{ route('vendors.show', ['vendor' => $po->vendor_id, 'tab' => 'po', 'po' => $po->id]) }}" wire:navigate class="font-bold text-primary hover:text-primary-hover dark:text-[#c9ce93]">{{ $po->po_no }}</a></x-ui.td>
                    <x-ui.td span class="truncate font-semibold text-ink">{{ $po->vendor->name }}</x-ui.td>
                    <x-ui.td label="Jumlah" align="right" class="font-bold text-ink">{{ $po->money($po->total_minor, $po->currency, $usdRate) }}</x-ui.td>
                    <x-ui.td label="Status Bayaran">@if ($pay)<x-ui.badge :tone="$pay->status->tone()" class="!text-[11px] !px-[11px]">{{ $pay->status->label() }}</x-ui.badge>@endif</x-ui.td>
                    <x-ui.td label="Tarikh Bayar" class="text-ink-3">{{ $pay?->payment_date ? tarikh($pay->payment_date) : '-' }}</x-ui.td>
                    <x-ui.td label="Diluluskan Oleh" class="truncate text-ink-3">{{ $pay?->approved_by_name ?: '-' }}</x-ui.td>
                    <x-ui.td label="Bank" class="text-ink-3">{{ $pay?->bank ?: '-' }}</x-ui.td>
                    <x-ui.td label="No. Ruj. Transfer" class="font-mono text-[12px] text-ink-3">{{ $pay?->reference ?: '-' }}</x-ui.td>
                    <x-ui.td align="center" mobile="hide"><span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold whitespace-nowrap {{ $po->status->badgeClasses() }}">{{ $po->status->label() }}</span></x-ui.td>
                    <x-ui.td align="right" span>
                        <a href="{{ route('vendors.po.pdf', $po) }}" target="_blank" class="inline-flex min-h-10 items-center gap-[5px] text-[12.5px] font-semibold text-primary max-md:w-full max-md:justify-center"><i class="ph ph-receipt text-[15px]"></i> Resit</a>
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            @if ($this->orders->isEmpty())
                <x-slot:empty>
                    <x-ui.empty-state icon="clipboard-text" title="Belum ada PO dicipta. Buka profil vendor untuk cipta PO baharu." />
                </x-slot:empty>
            @endif
        </x-ui.data-table>
    @endif

    @include('livewire.vendors.partials.vendor-form-modal')
</div>
