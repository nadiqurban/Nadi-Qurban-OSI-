@php
    $rows = $this->rows;
    $pendingTab = $tab === 'belum';
    $bar = ['primary' => 'bg-primary', 'info' => 'bg-info', 'warning' => 'bg-warning', 'success' => 'bg-success'];
    $hasFilters = $search !== '' || $service !== '' || $animal !== '' || $country !== '' || $vendor !== '';
@endphp

<div>
    <x-ui.page-header title="Agihan Negara Pelaksanaan" subtitle="Tetapkan negara pelaksanaan & vendor bagi tempahan yang telah disahkan." :breadcrumb="['Operasi', 'Agihan Negara']" />

    {{-- Country summary --}}
    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @forelse ($this->countrySummary() as $c)
            <div class="min-w-0 rounded-[12px] border border-border bg-surface px-[18px] py-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="truncate text-[15px] font-bold text-ink md:text-[16px]">{{ $c['name'] }}</span>
                    <span class="rounded-[20px] px-[10px] py-[3px] text-[11px] font-bold whitespace-nowrap {{ \App\Support\Tone::classes($c['tone']) }}">{{ $c['pct'] }}%</span>
                </div>
                <div class="mt-3 text-[26px] leading-none font-extrabold text-ink md:text-[30px]">{{ $c['count'] }}</div>
                <div class="mt-[5px] text-[12px] text-faint">tempahan</div>
                <div class="mt-[10px] h-1.5 overflow-hidden rounded-[20px] bg-divider"><div class="h-full rounded-[20px] {{ $bar[$c['tone']] }}" style="width: {{ $c['pct'] }}%"></div></div>
            </div>
        @empty
            <div class="col-span-full rounded-[12px] border border-border bg-surface px-[18px] py-4 text-[13px] text-faint">Belum ada tempahan untuk diagihkan.</div>
        @endforelse
    </div>

    {{-- Tabs + actions --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.tabs wire-model="tab" :active="$tab" :items="[
            ['key' => 'belum', 'label' => 'Belum Diagih', 'count' => $this->counts['pending']],
            ['key' => 'telah', 'label' => 'Telah Diagih', 'count' => $this->counts['sent']],
        ]" />
        <x-ui.button variant="success" icon="microsoft-excel-logo" wire:click="export" class="max-md:flex-1">Eksport Excel</x-ui.button>
        <div class="flex flex-wrap items-center gap-2 md:ml-auto max-md:w-full">
            @if ($canManage && $selectedPending > 0)
                <x-ui.button icon="stack" wire:click="openBulk" class="max-md:flex-1">Agih Pukal ({{ $selectedPending }})</x-ui.button>
            @endif
            <x-ui.button variant="gold" icon="users-three" wire:click="openGroups" class="max-md:flex-1">Jana Senarai Peserta</x-ui.button>
            @if (! $pendingTab)
                <x-ui.button icon="users-three" wire:click="$toggle('groupByVendor')" class="{{ $groupByVendor ? '!bg-[#2f331a]' : '' }} max-md:flex-1">Kumpul ikut Vendor</x-ui.button>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <x-ui.filter-bar plain :has-filters="$hasFilters" reset-action="$wire.clearFilters()" :active-count="collect([$service, $animal, $country, $vendor])->filter()->count()">
        <x-slot:search>
            <x-ui.mini-search wire:model.live.debounce.300ms="search" />
        </x-slot:search>
        <x-slot:filters>
            <x-ui.mini-select wire:model.live="service" placeholder="Semua Ibadah" :options="collect(\App\Enums\Service::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" aria-label="Ibadah" />
            <x-ui.mini-select wire:model.live="animal" placeholder="Semua Servis" :options="collect(\App\Enums\Animal::cases())->mapWithKeys(fn ($a) => [$a->value => $a->label()])->all()" aria-label="Servis" />
            <x-ui.mini-select wire:model.live="country" placeholder="Semua Negara" :options="$countries" aria-label="Negara" />
            <x-ui.mini-select wire:model.live="vendor" placeholder="Semua Vendor" :options="$vendorOptions" aria-label="Vendor" />
        </x-slot:filters>
    </x-ui.filter-bar>

    @if ($groupByVendor && ! $pendingTab)
        {{-- Grouped by vendor --}}
        <div class="flex flex-col gap-4">
            @forelse ($vendorGroups as $items)
                @php $first = $items->first(); @endphp
                <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
                    <div class="flex items-center justify-between gap-3 border-b border-border bg-bg px-5 py-[14px]">
                        <div class="flex min-w-0 items-center gap-[11px]">
                            <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[9px] bg-primary-soft text-primary"><i class="ph ph-truck text-[17px]"></i></span>
                            <div class="min-w-0"><div class="truncate text-[14px] font-bold text-primary dark:text-[#c9ce93]">{{ $first->allocation?->vendor->name }} — {{ $first->allocation?->vendor->code }}</div><div class="text-[11.5px] text-faint">{{ $first->allocation?->country->name }}</div></div>
                        </div>
                        <span class="shrink-0 rounded-[20px] bg-success-soft px-3 py-1 text-[12px] font-bold text-success">{{ $items->count() }} tempahan</span>
                    </div>
                    @foreach ($items as $o)
                        <div class="grid grid-cols-1 items-center gap-1 border-b border-divider px-5 py-[13px] text-[13px] last:border-b-0 md:grid-cols-[1.3fr_1.6fr_1.2fr] md:gap-[14px]">
                            <span class="font-semibold text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</span>
                            <span class="min-w-0"><span class="block font-semibold text-ink">{{ $o->customer->name }}</span><span class="text-[11.5px] text-faint">{{ $o->customer->phone }}</span></span>
                            <span class="text-ink-3">{{ $o->ibadahLabel() }}</span>
                        </div>
                    @endforeach
                </section>
            @empty
                <div class="rounded-[12px] border border-border bg-surface"><x-ui.empty-state icon="truck" title="Belum ada tempahan yang diagihkan." /></div>
            @endforelse
        </div>
    @else
        <x-ui.data-table cols="32px 1.3fr 1.4fr 1.1fr 0.7fr 1fr 1.2fr 1.2fr 1fr" min-width="1060px">
            <x-slot:head>
                <x-ui.select-all :checked="$this->allSelected()" />
                <span>No. Tempahan</span><span>Pelanggan</span><span>Ibadah</span><span class="text-center">Kuantiti</span><span>Pakej</span><span>Negara</span><span>Vendor</span><span class="text-center">Status</span>
            </x-slot:head>
            @foreach ($rows as $o)
                @php
                    $sent = (bool) $o->allocation;
                    $choice = $this->choice($o);
                    $vendorsFor = $this->vendorsByCountry[(int) $choice['country']] ?? [];
                    $ready = $choice['country'] !== '' && $choice['vendor'] !== '';
                @endphp
                <x-ui.tr wire:key="agih-{{ $o->id }}">
                    <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                        <x-ui.checkbox value="{{ $o->id }}" wire:model.live="selected" aria-label="Pilih {{ $o->order_no }}" />
                        <span class="text-[13px] font-bold text-primary md:hidden">{{ $o->order_no }}</span>
                    </x-ui.td>
                    <x-ui.td mobile="hide" class="font-semibold text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</x-ui.td>
                    <x-ui.td span>
                        <span class="block truncate font-semibold text-ink">{{ $o->customer->name }}</span>
                        <span class="text-[11.5px] text-faint">{{ $o->customer->phone }}</span>
                    </x-ui.td>
                    <x-ui.td label="Ibadah" class="text-ink-3">{{ $o->ibadahLabel() }}</x-ui.td>
                    <x-ui.td label="Kuantiti" align="center" class="font-semibold text-ink">{{ $o->quantity }}</x-ui.td>
                    <x-ui.td label="Pakej" class="text-ink-3">{{ $o->package_name }}</x-ui.td>
                    <x-ui.td label="Negara">
                        @if ($sent)
                            <span class="inline-flex items-center gap-1.5 text-[12.5px] font-semibold text-ink"><i class="ph ph-lock-simple text-[13px] text-faint"></i> {{ $o->country->name }}</span>
                        @else
                            <x-ui.mini-select block wire:model.live="draft.{{ $o->id }}.country" placeholder="— Pilih Negara —" :options="$countries" aria-label="Negara {{ $o->order_no }}" :disabled="! $canManage" class="!px-[9px] !text-ink">
                            </x-ui.mini-select>
                        @endif
                    </x-ui.td>
                    <x-ui.td label="Vendor">
                        @if ($sent)
                            <span class="text-[12.5px] font-semibold text-ink">{{ $o->allocation->vendor->name }} — {{ $o->allocation->vendor->code }}</span>
                        @else
                            <x-ui.mini-select block wire:model.live="draft.{{ $o->id }}.vendor" placeholder="— Pilih Vendor —" :options="$vendorsFor" aria-label="Vendor {{ $o->order_no }}" :disabled="! $canManage" class="!px-[9px] !text-ink" />
                            @if ($choice['country'] !== '' && $vendorsFor === [])
                                <span class="mt-[3px] block text-[10.5px] text-warning">Tiada vendor berdaftar untuk negara ini</span>
                            @endif
                        @endif
                    </x-ui.td>
                    <x-ui.td align="center" span class="max-md:flex max-md:justify-end">
                        @if ($sent)
                            <span class="inline-flex items-center gap-1.5 max-md:w-full">
                                <button type="button" wire:click="openDetail({{ $o->id }})" class="inline-flex min-h-8 items-center gap-1.5 rounded-[8px] bg-success-soft px-[11px] py-[7px] text-[12.5px] font-semibold text-success max-md:min-h-11 max-md:flex-1 max-md:justify-center"><i class="ph ph-eye text-[15px]"></i> Butiran</button>
                                @if ($canManage)
                                    <button type="button" wire:click="cancel({{ $o->id }})" wire:confirm="Batalkan agihan {{ $o->order_no }}?" class="inline-flex min-h-8 items-center gap-1.5 rounded-[8px] bg-danger-soft px-[11px] py-[7px] text-[12.5px] font-semibold text-danger max-md:min-h-11 max-md:flex-1 max-md:justify-center"><i class="ph ph-x-circle text-[15px]"></i> Batal</button>
                                @endif
                            </span>
                        @elseif ($ready && $canManage)
                            <x-ui.button size="sm" icon="paper-plane-tilt" id="agih-send-{{ $o->id }}" wire:click="send({{ $o->id }})" class="!py-[7px] max-md:w-full">Hantar</x-ui.button>
                        @else
                            <x-ui.badge :tone="$choice['country'] !== '' ? 'warning' : 'neutral'">{{ $choice['country'] !== '' ? 'Perlu Vendor' : 'Belum Agih' }}</x-ui.badge>
                        @endif
                    </x-ui.td>
                </x-ui.tr>
            @endforeach
            @if ($rows->isEmpty())
                <x-slot:empty>
                    <div class="px-6 py-11 text-center">
                        <div class="mx-auto mb-3 flex size-[52px] items-center justify-center rounded-[13px] bg-neutral-soft text-faint"><i class="ph ph-globe-hemisphere-west text-[26px]"></i></div>
                        <div class="text-[14px] font-semibold text-ink-2">{{ $pendingTab ? 'Semua tempahan telah diagihkan.' : 'Belum ada tempahan yang diagihkan.' }}</div>
                        <p class="mt-[5px] text-[12.5px] text-faint">Tempahan yang bayarannya disahkan akan muncul di sini untuk diagihkan.</p>
                    </div>
                </x-slot:empty>
            @endif
        </x-ui.data-table>
    @endif

    {{-- ===================== Agih Pukal ===================== --}}
    @php $bulkVendors = $this->vendorsByCountry[(int) $bulkCountry] ?? []; $bulkReady = $bulkCountry !== '' && $bulkVendor !== ''; @endphp
    <x-ui.modal wire:model="showBulk" title="Agih Pukal Terpilih" :subtitle="$selectedPending.' tempahan terpilih'" icon="stack" max-width="460px" :footer-border="false">
        <div class="flex flex-col gap-4">
            <x-ui.field label="Negara Pelaksanaan" as="select" wire:model.live="bulkCountry">
                <option value="">— Pilih Negara —</option>
                @foreach ($countries as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Vendor" as="select" wire:model.live="bulkVendor" error="bulkVendor">
                <option value="">— Pilih Vendor —</option>
                @foreach ($bulkVendors as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.field>
            @if ($bulkCountry !== '' && $bulkVendors === [])
                <span class="-mt-2 text-[11.5px] text-warning">Tiada vendor berdaftar untuk negara ini</span>
            @endif
            <p class="rounded-[9px] bg-bg px-3 py-[10px] text-[12px] leading-normal text-muted"><i class="ph ph-info align-[-2px] text-[14px] text-primary"></i> {{ $selectedPending }} tempahan terpilih akan ditetapkan ke negara &amp; vendor di atas dan terus dihantar.</p>
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button icon="check-circle" wire:click="applyBulk" :disabled="! $bulkReady" class="{{ $bulkReady ? '!bg-success' : '!bg-[#9CA3AF]' }}">Agihkan Semua</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Butiran Agihan ===================== --}}
    <x-ui.modal wire:model="showDetail" title="Butiran Agihan" :subtitle="$detailOrder?->order_no" icon="fill check-circle" tone="success" max-width="460px" :footer-border="false">
        @if ($detailOrder)
            <div class="flex flex-col">
                @foreach ([
                    'Pelanggan' => $detailOrder->customer->name,
                    'No. Telefon' => $detailOrder->customer->phone,
                    'Ibadah' => $detailOrder->ibadahLabel(),
                    'Negara Pelaksanaan' => $detailOrder->country->name,
                    'Vendor' => $detailOrder->allocation ? $detailOrder->allocation->vendor->name.' — '.$detailOrder->allocation->vendor->code : '-',
                    'Dihantar' => $detailOrder->allocation ? tarikh($detailOrder->allocation->sent_at, true).($detailOrder->allocation->allocatedBy ? ' · '.$detailOrder->allocation->allocatedBy->name : '') : '-',
                    'Status' => $detailOrder->allocation ? 'Diagihkan' : 'Belum Agih',
                ] as $k => $v)
                    <div class="flex items-center justify-between gap-3 border-b border-divider py-[11px]"><span class="text-[12.5px] text-faint">{{ $k }}</span><span class="text-right text-[13px] font-semibold text-ink">{{ $v }}</span></div>
                @endforeach
            </div>
        @endif
        <x-slot:footer>
            <x-ui.button x-on:click="open = false">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    @include('partials.participant-groups-modal')
</div>
