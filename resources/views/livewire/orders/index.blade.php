@php
    $canManage = $this->canManage();
    $orders = $this->orders;
    $pageIds = $orders->pluck('id')->map(fn ($id) => (string) $id)->all();
    $selectedIds = array_map('strval', $selected);
    $allOnPage = $pageIds !== [] && array_diff($pageIds, $selectedIds) === [];
    $selectionQuery = http_build_query(['ids' => $selected]);
@endphp

<div>
    <x-ui.page-header title="Senarai Tempahan" :breadcrumb="['Operasi', 'Tempahan & Pelanggan']">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download-simple" wire:click="export" class="[&>i]:text-muted">Eksport</x-ui.button>
            @if ($canManage)
                <x-ui.button icon="plus" mobile-block wire:click="create">Tempahan Baharu</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    {{-- Filter card --}}
    <x-ui.filter-bar :has-filters="$this->hasFilters()" reset-action="$wire.clearFilters()"
                     :active-count="collect([$service, $animal, $country, $status])->filter()->count()">
        <x-slot:tabs>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.tabs variant="pill" wire-model="period" :active="$period"
                           :items="collect(\App\Livewire\Orders\Index::PERIODS)->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values()->all()" />
                @if ($period === 'custom')
                    <div class="flex flex-wrap items-center gap-2 text-[12.5px] text-muted">
                        <label class="flex items-center gap-1.5 rounded-[8px] border border-border bg-surface px-[10px] py-1.5">
                            <i class="ph ph-calendar-blank text-[15px] text-primary"></i>
                            <input type="date" wire:model.live="from" class="bg-transparent text-[12.5px] text-ink outline-none" aria-label="Dari tarikh">
                        </label>
                        <span>hingga</span>
                        <label class="flex items-center gap-1.5 rounded-[8px] border border-border bg-surface px-[10px] py-1.5">
                            <i class="ph ph-calendar-blank text-[15px] text-primary"></i>
                            <input type="date" wire:model.live="to" class="bg-transparent text-[12.5px] text-ink outline-none" aria-label="Hingga tarikh">
                        </label>
                    </div>
                @endif
            </div>
        </x-slot:tabs>
        <x-slot:search>
            <x-ui.search-input placeholder="Cari nama, telefon, no. tempahan" wire:model.live.debounce.300ms="search" />
        </x-slot:search>
        <x-slot:filters>
            <x-ui.filter-select label="Servis" :options="$options['service']" wire:model.live="service" />
            <x-ui.filter-select label="Haiwan" :options="$options['animal']" wire:model.live="animal" />
            <x-ui.filter-select label="Negara" :options="$options['country']" wire:model.live="country" />
            <x-ui.filter-select label="Status" :options="$options['status']" wire:model.live="status" />
        </x-slot:filters>
    </x-ui.filter-bar>

    {{-- Bulk bar --}}
    @if (count($selected) > 0)
        <x-ui.bulk-bar :count="count($selected)" noun="tempahan" clear-action="$wire.set('selected', [])">
            <x-ui.button size="sm" variant="on-dark" icon="download-simple" wire:click="export">Eksport</x-ui.button>
            <x-ui.button size="sm" variant="gold" icon="users-three" wire:click="openGroups">Jana Senarai Peserta</x-ui.button>
            <x-ui.button size="sm" variant="on-dark" icon="truck" wire:click="$set('showWaybill', true)">Waybill</x-ui.button>
            <x-ui.button size="sm" variant="gold" icon="certificate" wire:click="generateCertificates">Generate Sijil</x-ui.button>
            @if ($canManage)
                <x-ui.button size="sm" variant="success" icon="fill check-circle" wire:click="markAccepted">Diterima</x-ui.button>
                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                    <x-ui.button size="sm" variant="on-dark" icon="tag" icon-right="caret-down" x-on:click="open = !open">Kemaskini Status</x-ui.button>
                    <div x-cloak x-show="open" x-transition.origin.top.right
                         class="absolute right-0 bottom-11 z-30 flex w-[190px] flex-col rounded-[10px] border border-border bg-surface p-1.5 shadow-pop md:top-[42px] md:bottom-auto">
                        @foreach (\App\Enums\OrderStatus::manual() as $st)
                            <button type="button" wire:click="setStatus('{{ $st->value }}')" x-on:click="open = false"
                                    class="flex min-h-10 items-center gap-[9px] rounded-[7px] px-[11px] py-[9px] text-left text-[13px] text-ink-2 hover:bg-bg">
                                <span class="size-[9px] rounded-full {{ $st->dotClass() }}"></span>{{ $st->label() }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif
        </x-ui.bulk-bar>
    @endif

    {{-- Table --}}
    <x-ui.data-table cols="36px 1.4fr 1.5fr 0.9fr 1.1fr 1fr 0.9fr 0.8fr 1fr 1.1fr 0.8fr 1fr 0.7fr" min-width="1340px">
        <x-slot:head>
            <span class="flex">
                <button type="button" wire:click="toggleAll" aria-label="Pilih semua"
                        @class(['flex size-[18px] items-center justify-center rounded-[5px] border', 'border-primary bg-primary text-white' => $allOnPage, 'border-[#CBD5D0] bg-surface text-transparent' => ! $allOnPage])>
                    <i class="ph-fill ph-check text-[12px]"></i>
                </button>
            </span>
            <span>No. Tempahan</span><span>Pelanggan</span><span>Servis</span><span>Haiwan</span><span>Pakej</span><span>Negara</span>
            <span class="text-center">Kuantiti</span><span class="text-right">Harga</span><span>Bayaran</span><span class="text-center">Tahun</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
        </x-slot:head>

        @foreach ($orders as $o)
            @php $pill = $o->payment?->pill() ?? ['label' => $o->payment_method->label(), 'tone' => 'primary']; @endphp
            <x-ui.tr wire:key="order-{{ $o->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    <x-ui.checkbox :size="18" value="{{ $o->id }}" wire:model.live="selected" aria-label="Pilih {{ $o->order_no }}" />
                    <a href="{{ route('orders.show', $o) }}" wire:navigate class="text-[13px] font-bold text-primary md:hidden">{{ $o->order_no }}</a>
                    <span class="ml-auto md:hidden"><x-ui.status-badge :status="$o->status" /></span>
                </x-ui.td>
                <x-ui.td mobile="hide">
                    <a href="{{ route('orders.show', $o) }}" wire:navigate class="block text-[12.5px] font-bold text-primary hover:text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</a>
                    <div class="mt-0.5 text-[11px] text-faint">{{ $o->tracking_no }}</div>
                    @if ($o->is_instalment)
                        <x-ui.badge tone="purple" variant="label" icon="calendar-check" class="mt-1">ANSURAN</x-ui.badge>
                    @endif
                </x-ui.td>
                <x-ui.td span>
                    <div class="text-[13px] font-semibold text-ink">{{ $o->customer->name }}</div>
                    <div class="mt-0.5 text-[11.5px] text-muted">{{ $o->customer->phone }}</div>
                </x-ui.td>
                <x-ui.td label="Servis" class="text-ink-2">{{ $o->service->label() }}</x-ui.td>
                <x-ui.td label="Haiwan" class="text-ink-2">{{ $o->animal->label() }}</x-ui.td>
                <x-ui.td label="Pakej" class="text-ink-2">{{ $o->package_name }}</x-ui.td>
                <x-ui.td label="Negara" class="text-ink-2">{{ $o->country->name }}</x-ui.td>
                <x-ui.td label="Kuantiti" align="center" class="text-ink-2">{{ $o->quantity }}</x-ui.td>
                <x-ui.td label="Harga" align="right" class="font-semibold text-ink tabular-nums">{{ rm($o->total_sen) }}</x-ui.td>
                <x-ui.td label="Bayaran"><x-ui.badge :tone="$pill['tone']" variant="tag" class="text-[12px] font-semibold">{{ $pill['label'] }}</x-ui.badge></x-ui.td>
                <x-ui.td label="Tahun" align="center" class="text-ink-2">{{ $o->year }}</x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.status-badge :status="$o->status" /></x-ui.td>
                <x-ui.td align="right" span class="flex gap-1.5 md:justify-end">
                    @if ($o->payment?->proof())
                        <x-ui.button variant="gold-outline" size="sm" icon-only icon="receipt" wire:click="viewProof({{ $o->id }})" title="Lihat resit bayaran" aria-label="Lihat resit bayaran {{ $o->order_no }}" />
                    @endif
                    <x-ui.button variant="secondary" size="sm" icon-only icon="eye" :href="route('orders.show', $o)" wire:navigate class="!text-primary" aria-label="Lihat {{ $o->order_no }}" />
                    @if ($canManage)
                        <x-ui.button variant="secondary" size="sm" icon-only icon="pencil-simple" :href="route('orders.show', [$o, 'edit' => 1])" wire:navigate class="!text-muted" aria-label="Edit {{ $o->order_no }}" />
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach

        @if ($orders->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="shopping-cart-simple" title="Tiada tempahan ditemui">
                    Cuba ubah tempoh, carian atau penapis.
                </x-ui.empty-state>
            </x-slot:empty>
        @else
            <x-slot:footer>
                <x-ui.pagination :paginator="$orders" noun="tempahan" />
            </x-slot:footer>
        @endif
    </x-ui.data-table>

    {{-- ===================== Bukti Bayaran ===================== --}}
    @php $proofPayment = $proofOrder?->payment; @endphp
    <x-ui.modal wire:model="showProof"
                title="Bukti Bayaran" :subtitle="$proofPayment?->proof()?->file_name ?? $proofOrder?->order_no" icon="receipt" tone="gold" max-width="560px"
                body-class="flex flex-col items-center bg-bg px-4 py-[18px] md:px-[22px]">
        @if ($proofPayment?->proof())
            <x-proof-preview wire:key="proof-{{ $proofOrderId }}" :url="$proofPayment->proofUrl()" :is-pdf="$proofPayment->proofIsPdf()" :name="$proofPayment->proof()->file_name" />
            <a href="{{ $proofPayment->proofUrl() }}" target="_blank" rel="noopener" class="mt-4 inline-flex min-h-11 items-center gap-[7px] rounded-[8px] bg-primary-soft px-[15px] py-[9px] text-[12.5px] font-semibold text-primary hover:text-primary">
                <i class="ph ph-arrow-square-out text-[15px]"></i> Buka dalam tab baharu
            </a>
        @endif
    </x-ui.modal>

    @include('partials.participant-groups-modal')

    {{-- ===================== Waybill ===================== --}}
    @php $selectedOrders = $showWaybill ? $this->selectedOrders : collect(); $courierEnum = \App\Enums\Courier::tryFrom($courier) ?? \App\Enums\Courier::PosLaju; @endphp
    <x-ui.modal wire:model="showWaybill" title="Waybill / Nota Penghantaran" :subtitle="count($selected).' tempahan dipilih · satu halaman setiap tempahan'" icon="truck" max-width="720px">
        <x-ui.field label="Kurier" as="select" wire:model.live="courier">
            @foreach ($couriers as $c)
                <option value="{{ $c->value }}">{{ $c->label() }}</option>
            @endforeach
        </x-ui.field>
        <div class="mt-4 overflow-hidden rounded-[10px] border border-border">
            @foreach ($selectedOrders as $o)
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-divider px-4 py-3 text-[12.5px] last:border-b-0">
                    <span class="font-bold text-primary">{{ $o->order_no }}</span>
                    <span class="text-ink-2">{{ $o->customer->name }}</span>
                    <span class="ml-auto font-mono text-muted">{{ $courierEnum->consignmentFor($o->order_no) }}</span>
                </div>
            @endforeach
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            <x-ui.button variant="success" icon="microsoft-excel-logo" wire:click="exportCustomers">Eksport Excel</x-ui.button>
            <x-ui.button variant="gold" icon="download-simple" :href="route('orders.waybill.pdf').'?'.$selectionQuery.'&kurier='.$courier.'&muat-turun=1'">Muat Turun</x-ui.button>
            <x-ui.button icon="printer" target="_blank" :href="route('orders.waybill.pdf').'?'.$selectionQuery.'&kurier='.$courier">Cetak / PDF</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Tempahan Baharu ===================== --}}
    @include('livewire.orders.partials.new-order-modal')
</div>
