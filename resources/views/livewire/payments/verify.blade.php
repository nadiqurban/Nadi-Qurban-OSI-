@php $rows = $this->rows; $pendingTab = $tab === 'belum'; @endphp

<div>
    @can('orders.view')
        <a href="{{ route('orders.index') }}" wire:navigate class="mb-[18px] inline-flex min-h-11 items-center gap-[7px] text-[13.5px] font-semibold text-primary"><i class="ph ph-arrow-left text-[17px]"></i> Kembali ke senarai</a>
    @endcan

    <x-ui.page-header title="Pengesahan Bayaran" subtitle="Sahkan bukti bayaran pelanggan sebelum tempahan diproses." class="!mb-[22px]">
        <x-slot:actions>
            <span class="rounded-[20px] bg-warning-soft px-[14px] py-[7px] text-[12.5px] font-semibold text-warning">{{ $this->counts['pending'] }} menunggu</span>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.tabs wire-model="tab" :active="$tab" :items="[
            ['key' => 'belum', 'label' => 'Belum Disahkan', 'count' => $this->counts['pending']],
            ['key' => 'telah', 'label' => 'Telah Disahkan', 'count' => $this->counts['done']],
        ]" />
        <div class="flex flex-wrap items-center gap-2 max-md:w-full md:ml-auto">
            <span class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-faint"><i class="ph ph-calendar-blank text-[15px]"></i> Tapis:</span>
            <label class="inline-flex min-h-10 items-center gap-1.5 rounded-[8px] border border-border bg-surface px-[10px] py-1.5">
                <i class="ph ph-calendar-dots text-[15px] text-primary"></i>
                <input type="date" wire:model.live="date" class="bg-transparent text-[12.5px] text-ink outline-none" aria-label="Tarikh bayaran">
            </label>
            @if ($date !== '')
                <button type="button" wire:click="$set('date', '')" class="inline-flex min-h-10 items-center gap-[5px] text-[12px] font-semibold text-danger"><i class="ph ph-x text-[13px]"></i> Reset</button>
            @endif
            <x-ui.button size="sm" variant="success" icon="microsoft-excel-logo" wire:click="export" class="max-md:flex-1">Eksport Excel</x-ui.button>
        </div>
    </div>

    @if ($canManage && $pendingTab && count($checked) > 0)
        <x-ui.bulk-bar :count="count($checked)" noun="bayaran" clear-action="$wire.set('checked', [])">
            <x-ui.button size="sm" variant="success" icon="fill check-circle" wire:click="confirmChecked" wire:confirm="Sahkan semua bayaran yang dipilih?">Sahkan Dipilih</x-ui.button>
        </x-ui.bulk-bar>
    @endif

    <x-ui.data-table cols="32px 1.2fr 1.2fr 0.7fr 0.8fr 0.9fr 1fr 0.75fr 1.4fr 2fr" min-width="1100px">
        <x-slot:head>
            <span></span><span>No. Tempahan</span><span>Pelanggan</span><span>Pakej</span><span class="text-right">Jumlah</span><span>Kaedah</span><span>Tarikh Bayaran</span><span class="text-center">Resit</span><span class="text-center">Status</span><span class="text-center">Tindakan</span>
        </x-slot:head>

        @foreach ($rows as $o)
            @php $p = $o->payment; $hasProof = (bool) $p?->proof(); @endphp
            <x-ui.tr wire:key="pv-{{ $o->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    @if ($canManage && $pendingTab)
                        <x-ui.checkbox value="{{ $o->id }}" wire:model.live="checked" aria-label="Pilih {{ $o->order_no }}" />
                    @endif
                    <span class="text-[13px] font-bold text-primary md:hidden">{{ $o->order_no }}</span>
                    <span class="ml-auto md:hidden"><x-ui.badge :tone="$p->status->tone()">{{ $p->status->label() }}</x-ui.badge></span>
                </x-ui.td>
                <x-ui.td mobile="hide" class="font-bold text-primary dark:text-[#c9ce93]">
                    @can('orders.view')<a href="{{ route('orders.show', $o) }}" wire:navigate class="text-primary hover:text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</a>@else{{ $o->order_no }}@endcan
                    @if ($o->is_instalment)
                        <x-ui.badge tone="purple" variant="label" icon="calendar-check" class="ml-1.5 align-middle">ANSURAN</x-ui.badge>
                    @endif
                </x-ui.td>
                <x-ui.td span class="text-ink">{{ $o->customer->name }}</x-ui.td>
                <x-ui.td label="Pakej" class="text-ink-2">{{ $o->package_name }}</x-ui.td>
                <x-ui.td label="Jumlah" align="right" class="font-semibold text-ink tabular-nums">{{ rm($o->total_sen) }}</x-ui.td>
                <x-ui.td label="Kaedah" class="flex items-center gap-1.5 text-muted max-md:block"><i class="ph ph-bank text-[15px] max-md:hidden"></i>{{ $p->channel ?: $o->payment_method->label() }}</x-ui.td>
                <x-ui.td label="Tarikh Bayaran" class="text-ink-3">{{ $p->paid_at ? tarikh($p->paid_at, true) : '-' }}</x-ui.td>
                <x-ui.td label="Resit" align="center">
                    @if ($hasProof)
                        <button type="button" wire:click="viewProof({{ $o->id }})" class="inline-flex min-h-9 items-center gap-[5px] rounded-[7px] bg-gold-soft px-[10px] py-1.5 text-[12px] font-semibold text-gold"><i class="ph ph-receipt text-[15px]"></i> Resit</button>
                    @else
                        <span class="text-[11.5px] text-faint">Tiada resit</span>
                    @endif
                </x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.badge :tone="$p->status->tone()">{{ $p->status->label() }}</x-ui.badge></x-ui.td>
                <x-ui.td align="center" span class="flex gap-1.5 md:justify-center">
                    @if ($pendingTab && $canManage)
                        <x-ui.button size="sm" variant="success" icon="fill check-circle" wire:click="confirm({{ $o->id }})" wire:loading.attr="disabled" class="max-md:flex-1">Sahkan</x-ui.button>
                        <x-ui.button size="sm" variant="danger-soft" icon="x-circle" wire:click="openReject({{ $o->id }})" class="max-md:flex-1">Batal</x-ui.button>
                    @elseif (! $pendingTab)
                        <span class="inline-flex items-center gap-[5px] text-[12.5px] font-semibold text-success"><i class="ph-fill ph-check-circle text-[15px]"></i> Disahkan</span>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach

        @if ($rows->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state :title="$pendingTab ? 'Tiada tempahan menunggu' : 'Belum ada bayaran disahkan'">
                    @if ($pendingTab)
                        Hanya tempahan berstatus <b class="text-success">Diterima</b> di portal Tempahan &amp; Pelanggan akan muncul di sini.
                    @endif
                </x-ui.empty-state>
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    {{-- Bukti Bayaran --}}
    @php $proofPayment = $proofOrder?->payment; @endphp
    <x-ui.modal wire:model="showProof" title="Bukti Bayaran" :subtitle="$proofPayment?->proof()?->file_name ?? $proofOrder?->order_no" icon="receipt" tone="gold" max-width="560px"
                body-class="flex flex-col items-center bg-bg px-4 py-[18px] md:px-[22px]">
        @if ($proofPayment?->proof())
            <x-proof-preview wire:key="pv-proof-{{ $proofOrderId }}" :url="$proofPayment->proofUrl()" :is-pdf="$proofPayment->proofIsPdf()" :name="$proofPayment->proof()->file_name" />
            <a href="{{ $proofPayment->proofUrl() }}" target="_blank" rel="noopener" class="mt-4 inline-flex min-h-11 items-center gap-[7px] rounded-[8px] bg-primary-soft px-[15px] py-[9px] text-[12.5px] font-semibold text-primary hover:text-primary"><i class="ph ph-arrow-square-out text-[15px]"></i> Buka dalam tab baharu</a>
        @endif
    </x-ui.modal>

    {{-- Batal (sebab) --}}
    <x-ui.modal wire:model="showReject" title="Batal Pengesahan Bayaran" :subtitle="$rejectOrder ? $rejectOrder->order_no.' · '.$rejectOrder->customer->name : null" icon="x-circle" tone="danger" max-width="460px" :footer-border="false">
        <form id="reject-form" wire:submit="reject" novalidate>
            <p class="mb-3 text-[12.5px] leading-[1.5] text-muted">Tempahan akan dikembalikan ke status <b class="text-warning">Menunggu Bayaran</b>. Nyatakan sebab supaya pasukan jualan boleh menghubungi pelanggan.</p>
            <x-ui.field label="Sebab Pembatalan" as="textarea" rows="3" wire:model="reason" placeholder="cth. Jumlah dalam resit tidak sepadan dengan harga tempahan" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            <x-ui.button type="submit" form="reject-form" variant="danger" icon="x-circle">Batal Bayaran</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
