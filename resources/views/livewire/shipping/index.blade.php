@php
    $rows = $this->rows;
    $pendingTab = $tab === 'menunggu';
    $hasFilters = $kind !== '' || $date !== '';
@endphp

<div>
    <x-ui.page-header title="AWB & Postage" subtitle="Jana Airway Bill & jejak penghantaran sijil/dokumen kepada pelanggan." :breadcrumb="['Operasi', 'AWB & Postage']" />

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats() as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.tabs wire-model="tab" :active="$tab" :items="[
            ['key' => 'menunggu', 'label' => 'Menunggu AWB', 'count' => $this->counts['pending']],
            ['key' => 'dijana', 'label' => 'AWB Dijana', 'count' => $this->counts['sent']],
        ]" />
        @if ($canManage && $pendingTab && count($selected) > 0)
            <x-ui.button icon="package" wire:click="bulkPostage" wire:confirm="Jana AWB (Pos Laju · Pos Berdaftar) untuk {{ count($selected) }} tempahan?" class="max-md:w-full">Postage Pukal ({{ count($selected) }})</x-ui.button>
        @endif
        <x-ui.filter-bar plain class="!mb-0 md:ml-auto max-md:w-full" :has-filters="$hasFilters" reset-action="$wire.clearFilters()" :active-count="collect([$kind, $date])->filter()->count()">
            <x-slot:filters>
                <x-ui.mini-select wire:model.live="kind" placeholder="Semua Servis" :options="$this->kindOptions()" aria-label="Servis" />
                <x-ui.mini-date wire:model.live="date" label="Tarikh pelaksanaan" />
            </x-slot:filters>
            <x-slot:search>
                <x-ui.button variant="success" size="sm" icon="microsoft-excel-logo" wire:click="export" class="order-last !px-[14px] !py-[9px] max-md:flex-1">Eksport Excel</x-ui.button>
            </x-slot:search>
        </x-ui.filter-bar>
    </div>

    <x-ui.data-table cols="32px 1.2fr 1.3fr 1.1fr 1.1fr 1fr 0.6fr 1.8fr 0.8fr 1fr 1fr 1.1fr 1.2fr 1fr 1.1fr" min-width="1860px">
        <x-slot:head>
            <x-ui.select-all :checked="$this->allSelected()" />
            <span>No. Tempahan</span><span>Pelanggan</span><span>Servis</span><span>Pakej</span><span>No. Telefon</span><span class="text-center">Kuantiti</span><span>Alamat Penuh</span><span>Poskod</span><span>Bandar</span><span>Negeri</span><span>Kurier</span><span>No. Konsainan</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
        </x-slot:head>
        @foreach ($rows as $o)
            @php $s = $o->shipment; $c = $o->customer; @endphp
            <x-ui.tr wire:key="awb-{{ $o->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    <x-ui.checkbox value="{{ $o->id }}" wire:model.live="selected" aria-label="Pilih {{ $o->order_no }}" />
                    <span class="text-[13px] font-bold text-primary md:hidden">{{ $o->order_no }}</span>
                    <span class="ml-auto md:hidden"><x-ui.badge :tone="$s ? 'success' : 'warning'">{{ $s ? 'AWB Dijana' : 'Menunggu AWB' }}</x-ui.badge></span>
                </x-ui.td>
                <x-ui.td mobile="hide" class="font-semibold text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</x-ui.td>
                <x-ui.td span><span class="block truncate font-semibold text-ink">{{ $s->recipient_name ?? $c->name }}</span></x-ui.td>
                <x-ui.td label="Servis" class="text-ink-3">{{ $o->ibadahLabel() }}</x-ui.td>
                <x-ui.td label="Pakej" class="text-ink-3">{{ $o->package_name }}</x-ui.td>
                <x-ui.td label="No. Telefon" class="text-ink-3">{{ $s->phone ?? $c->phone }}</x-ui.td>
                <x-ui.td label="Kuantiti" align="center" class="font-semibold text-ink">{{ $o->quantity }}</x-ui.td>
                <x-ui.td label="Alamat Penuh" span class="text-[12px] text-ink-3">{{ $s->address ?? ($c->address ?: '-') }}</x-ui.td>
                <x-ui.td label="Poskod" class="text-ink-3">{{ $s->postcode ?? ($c->postcode ?: '-') }}</x-ui.td>
                <x-ui.td label="Bandar" class="text-ink-3">{{ $s->city ?? ($c->city ?: '-') }}</x-ui.td>
                <x-ui.td label="Negeri" class="text-ink-3">{{ $s->state ?? ($c->state ?: '-') }}</x-ui.td>
                <x-ui.td label="Kurier" class="text-ink-3">{{ $s?->courier->label() ?? '—' }}</x-ui.td>
                <x-ui.td label="No. Konsainan" class="font-semibold text-ink-3">{{ $s->consignment_no ?? '—' }}</x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.badge :tone="$s ? 'success' : 'warning'">{{ $s ? 'AWB Dijana' : 'Menunggu AWB' }}</x-ui.badge></x-ui.td>
                <x-ui.td align="right" span>
                    @if ($s)
                        <button type="button" id="awb-view-{{ $o->id }}" wire:click="openAwb({{ $o->id }})" class="inline-flex min-h-10 items-center gap-1.5 text-[12.5px] font-semibold text-primary max-md:w-full max-md:justify-center dark:text-[#c9ce93]"><i class="ph ph-eye text-[15px]"></i> Lihat AWB</button>
                    @elseif ($canManage)
                        <x-ui.button size="sm" icon="plus" id="awb-open-{{ $o->id }}" wire:click="openGenerate({{ $o->id }})" class="max-md:w-full">Jana AWB</x-ui.button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        @if ($rows->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="package" :title="$pendingTab ? 'Tiada tempahan menunggu AWB.' : 'Belum ada AWB dijana.'" />
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    {{-- ===================== Jana Airway Bill ===================== --}}
    <x-ui.modal wire:model="showGenerate" title="Jana Airway Bill" :subtitle="$generateOrder ? $generateOrder->order_no.' · '.$generateOrder->customer->name : null" icon="package" max-width="460px" :footer-border="false">
        <div class="flex flex-col gap-[15px]">
            <x-ui.field label="Kurier" as="select" wire:model.live="courier" error="courier">
                @foreach (\App\Enums\Courier::cases() as $c)
                    <option value="{{ $c->value }}">{{ $c->label() }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Jenis Pos" as="select" wire:model="postType" error="postType">
                @foreach (\App\Enums\PostType::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </x-ui.field>
            <div class="rounded-[10px] bg-bg px-[14px] py-3">
                <div class="text-[11px] text-faint">No. Konsainan (auto-jana)</div>
                <div class="mt-[3px] text-[15px] font-extrabold text-primary dark:text-[#c9ce93]">{{ $consignmentPreview }}</div>
            </div>
            <x-ui.field label="Alamat Penghantaran" as="textarea" rows="3" wire:model="address" error="address" />
            @if ($generateOrder)
                <p class="-mt-2 text-[11.5px] text-faint">{{ collect([$generateOrder->customer->postcode, $generateOrder->customer->city, $generateOrder->customer->state])->filter()->implode(' · ') }}</p>
            @endif
        </div>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button icon="check-circle" wire:click="generate">Jana &amp; Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Airway Bill preview ===================== --}}
    <x-ui.modal wire:model="showAwb" title="Airway Bill" :subtitle="$awbShipment?->consignment_no" icon="package" max-width="840px" body-class="bg-bg p-3 md:p-5">
        @if ($awbShipment)
            <x-ui.doc-a4 padding="44px 52px">
                @include('pdf.partials.airway-bill-body', ['shipment' => $awbShipment])
            </x-ui.doc-a4>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            @if ($awbShipment)
                <x-ui.button variant="gold" icon="download-simple" :href="route('shipping.awb.pdf', ['ids' => [$awbShipment->order_id], 'muat-turun' => 1])">Muat Turun</x-ui.button>
                <x-ui.button icon="printer" target="_blank" :href="route('shipping.awb.pdf', ['ids' => [$awbShipment->order_id]])">Cetak / PDF</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>
</div>
