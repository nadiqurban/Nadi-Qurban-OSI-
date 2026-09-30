@php
    $rows = $this->rows;
    $hasFilters = $kind !== '' || $country !== '' || $date !== '';
@endphp

<div>
    <x-ui.page-header title="Tempahan Selesai" subtitle="Tempahan yang telah lengkap keseluruhan proses — dari bayaran hingga penghantaran sijil." :breadcrumb="['Operasi', 'Tempahan Selesai']" />

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats() as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <x-ui.filter-bar plain :has-filters="$hasFilters" reset-action="$wire.clearFilters()" :active-count="collect([$kind, $country, $date])->filter()->count()">
        <x-slot:filters>
            <x-ui.mini-select wire:model.live="kind" placeholder="Semua Servis" :options="$this->kindOptions()" aria-label="Servis" />
            <x-ui.mini-select wire:model.live="country" placeholder="Semua Negara" :options="$this->countryOptions()" aria-label="Negara" />
            <x-ui.mini-date wire:model.live="date" label="Tarikh pelaksanaan" />
        </x-slot:filters>
        <x-slot:search>
            <x-ui.button variant="success" icon="microsoft-excel-logo" wire:click="export" class="order-last md:ml-auto max-md:flex-1">Eksport Excel</x-ui.button>
        </x-slot:search>
    </x-ui.filter-bar>

    <x-ui.data-table cols="32px 1.1fr 1.2fr 1fr 1fr 0.9fr 0.55fr 0.9fr 0.9fr 1.4fr 1.8fr 0.8fr 0.8fr" min-width="1400px">
        <x-slot:head>
            <x-ui.select-all :checked="$this->allSelected()" />
            <span>No. Tempahan</span><span>Pelanggan</span><span>Servis</span><span>Pakej</span><span>Negara</span><span class="text-center">Kuantiti</span><span>Tarikh</span><span>Jumlah Bayaran</span><span>Emel</span><span>Alamat Penuh</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
        </x-slot:head>
        @foreach ($rows as $o)
            <x-ui.tr wire:key="done-{{ $o->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    <x-ui.checkbox value="{{ $o->id }}" wire:model.live="selected" aria-label="Pilih {{ $o->order_no }}" />
                    <span class="text-[13px] font-bold text-primary md:hidden">{{ $o->order_no }}</span>
                    <span class="ml-auto md:hidden"><x-ui.badge tone="success" icon="fill check-circle">Selesai</x-ui.badge></span>
                </x-ui.td>
                <x-ui.td mobile="hide" class="font-semibold text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</x-ui.td>
                <x-ui.td span>
                    <span class="block truncate font-semibold text-ink">{{ $o->customer->name }}</span>
                    <span class="text-[11.5px] text-faint">{{ $o->customer->phone }}</span>
                </x-ui.td>
                <x-ui.td label="Servis" class="text-ink-3">{{ $o->ibadahLabel() }}</x-ui.td>
                <x-ui.td label="Pakej" class="text-ink-3">{{ $o->package_name }}</x-ui.td>
                <x-ui.td label="Negara" class="text-ink-3">{{ $o->country->name }}</x-ui.td>
                <x-ui.td label="Kuantiti" align="center" class="font-semibold text-ink">{{ $o->quantity }}</x-ui.td>
                <x-ui.td label="Tarikh" class="text-ink-3">{{ \App\Livewire\Orders\Completed::doneDate($o) }}</x-ui.td>
                <x-ui.td label="Jumlah Bayaran" class="font-semibold text-ink">{{ rm($o->total_sen) }}</x-ui.td>
                <x-ui.td label="Emel" class="truncate text-ink-3">{{ $o->customer->email ?: '-' }}</x-ui.td>
                <x-ui.td label="Alamat Penuh" span class="text-ink-3">{{ \App\Livewire\Orders\Completed::address($o) }}</x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.badge tone="success" icon="fill check-circle" class="!px-[11px]">Selesai</x-ui.badge></x-ui.td>
                <x-ui.td align="right" span>
                    <button type="button" id="done-view-{{ $o->id }}" wire:click="openDetail({{ $o->id }})" class="inline-flex min-h-10 items-center gap-1.5 text-[12.5px] font-semibold text-primary max-md:w-full max-md:justify-center dark:text-[#c9ce93]"><i class="ph ph-eye text-[15px]"></i> Butiran</button>
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        @if ($rows->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="check-square-offset" title="Tiada tempahan selesai untuk tapisan ini." />
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    <x-ui.modal wire:model="showDetail" title="Tempahan Selesai" :subtitle="$detailOrder?->order_no" icon="fill check-circle" tone="success" max-width="520px" :footer-border="false">
        @if ($detailOrder)
            <div class="mb-[18px] flex flex-col">
                @foreach ([
                    'Pelanggan' => $detailOrder->customer->name,
                    'No. Telefon' => $detailOrder->customer->phone,
                    'Emel' => $detailOrder->customer->email ?: '-',
                    'Alamat Penuh' => \App\Livewire\Orders\Completed::address($detailOrder),
                    'Servis' => $detailOrder->ibadahLabel(),
                    'Kuantiti' => $detailOrder->quantity,
                    'Negara Pelaksanaan' => $detailOrder->country->name,
                    'Tahun' => $detailOrder->year,
                    'Jumlah Bayaran' => rm($detailOrder->total_sen),
                    'Tarikh Selesai' => \App\Livewire\Orders\Completed::doneDate($detailOrder),
                    'Status' => 'Selesai',
                ] as $k => $v)
                    <div class="flex items-center justify-between gap-3 border-b border-divider py-[11px]"><span class="shrink-0 text-[12.5px] text-faint">{{ $k }}</span><span class="text-right text-[13px] font-semibold text-ink">{{ $v }}</span></div>
                @endforeach
            </div>
            <div class="mb-[10px] text-[11px] font-bold tracking-[.5px] text-faint uppercase">Garis Masa Proses</div>
            <div class="flex flex-col">
                @foreach ($timeline as [$label, $desc, $at])
                    <div class="flex gap-3">
                        <div class="flex shrink-0 flex-col items-center">
                            <span class="flex size-[26px] items-center justify-center rounded-full bg-success-soft text-success"><i class="ph-fill ph-check text-[13px]"></i></span>
                            <span @class(['mt-0.5 min-h-[14px] w-0.5 flex-1', 'bg-success-soft' => ! $loop->last])></span>
                        </div>
                        <div class="pb-[14px]">
                            <div class="text-[13px] font-semibold text-ink">{{ $label }}</div>
                            <div class="mt-0.5 text-[11.5px] text-faint">{{ $desc }}@if ($at) · {{ tarikh($at) }}@endif</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <x-slot:footer>
            <x-ui.button x-on:click="open = false">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
