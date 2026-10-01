@php
    $hasFilters = $search !== '' || $status !== '';
    $statusOptions = collect(\App\Enums\InvoiceStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    $po = $this->activePo;
    $poMedia = $po?->getFirstMedia('receipt') ?? $po?->getFirstMedia('advice');
    $poRows = $po ? [
        'No. PO' => $po->purchaseOrder->po_no,
        'Vendor' => $po->vendor->name,
        'Jumlah Bayaran' => rm($po->amount_sen),
        'Tarikh Bayar' => tarikh($po->payment_date),
        'Bank' => $po->bank ?: '-',
        'No. Rujukan' => $po->reference ?: '-',
        'Diluluskan Oleh' => $po->approved_by_name ?: '-',
        'Status' => 'Payment Completed',
    ] : [];
    $isQuote = $draft->type === 'quote';
    $mini = 'w-full rounded-[8px] border border-border bg-surface px-[11px] py-[9px] text-[12.5px] text-ink outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]';
    $miniLabel = 'mb-[5px] block text-[11px] font-semibold text-muted';
@endphp

<div>
    <x-ui.page-header title="Pengurusan Kewangan" :breadcrumb="['Jualan & Kewangan', 'Kewangan']">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="download-simple" wire:click="export" class="!px-[14px] [&>i]:text-muted max-md:flex-1">Eksport</x-ui.button>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="file-text" id="btn-quotation" wire:click="openDraft('quote')" class="!border-primary !text-primary max-md:flex-1 dark:!text-[#c9ce93]">Quotation</x-ui.button>
                <x-ui.button icon="plus" id="btn-invoice" wire:click="openDraft('invoice')" mobile-block>Cipta Invois</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    {{-- KPI --}}
    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-[18px] lg:grid-cols-4">
        @foreach ($kpis as $k)
            <x-ui.kpi-card :icon="$k['icon']" :tone="$k['tone']" :value="$k['value']" :label="$k['label']" :delta="$k['delta']" :trend="$k['trend']" value-class="md:text-[24px]" class="!shadow-none" />
        @endforeach
    </div>

    {{-- Aliran Tunai + Kaedah Bayaran --}}
    <div class="mb-[22px] grid grid-cols-1 gap-[18px] lg:grid-cols-[minmax(0,1.9fr)_minmax(0,1fr)]">
        <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
            <div class="mb-1.5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-[15.5px] font-bold text-ink">Aliran Tunai</h2>
                    <p class="mt-[3px] text-[12.5px] text-muted">Kutipan vs bayaran vendor (RM '000)</p>
                </div>
                <div class="flex items-center gap-4 text-[12.5px] text-muted">
                    <span class="flex items-center gap-1.5"><span class="size-[10px] rounded-[3px] bg-primary"></span>Kutipan</span>
                    <span class="flex items-center gap-1.5"><span class="size-[10px] rounded-[3px] bg-gold"></span>Bayaran</span>
                </div>
            </div>
            <x-chart.grouped-bars :data="$cashflow" />
        </section>
        <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
            <h2 class="mb-1 text-[15.5px] font-bold text-ink">Kaedah Bayaran</h2>
            <p class="mb-4 text-[12.5px] text-muted">Agihan transaksi masuk</p>
            <div class="flex items-center gap-[18px]">
                <div class="shrink-0"><x-chart.donut :slices="$methods" :title="$donutTitle" caption="kutipan" /></div>
                <div class="flex flex-1 flex-col gap-[11px]">
                    @foreach ($methods as $m)
                        <div class="flex items-center gap-[10px] text-[13px]">
                            <span class="size-[10px] shrink-0 rounded-[3px]" style="background: {{ $m['color'] }}"></span>
                            <span class="flex-1 text-ink-2">{{ $m['name'] }}</span>
                            <span class="font-bold text-ink">{{ $m['pct'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>

    {{-- Tabs + filter --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.tabs wire-model="tab" :active="$tab" class="max-md:w-[calc(100%+2rem)]" :items="[
            ['key' => 'semua', 'label' => 'Semua', 'count' => $counts['semua']],
            ['key' => 'dibayar', 'label' => 'Dibayar', 'count' => $counts['dibayar']],
            ['key' => 'tertunggak', 'label' => 'Tertunggak', 'count' => $counts['tertunggak']],
        ]" />
        <div class="flex flex-wrap gap-[10px] max-md:w-full md:ml-auto">
            <label class="flex min-w-[180px] items-center gap-[9px] rounded-[9px] border border-border bg-surface px-[13px] py-[9px] text-[13px] text-faint max-md:min-h-11 max-md:flex-1">
                <i class="ph ph-magnifying-glass text-[16px]"></i>
                <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari invois / pelanggan" aria-label="Cari invois / pelanggan" class="min-w-0 flex-1 bg-transparent text-[13px] text-ink outline-none max-md:text-[16px]">
            </label>
            <x-ui.filter-select label="Status" icon="funnel" wire:model.live="status" :options="$statusOptions" class="[&>button]:!min-w-0 [&>button]:!py-[9px] [&>button]:!text-[13px]" />
            <x-ui.button variant="success" size="sm" icon="microsoft-excel-logo" wire:click="export" class="!px-[15px] !py-[9px] !text-[13px]">Eksport Excel</x-ui.button>
        </div>
    </div>

    {{-- Invoice table --}}
    <x-ui.data-table cols="32px 1.1fr 1.4fr 1.2fr 1fr 1fr 1fr 1.1fr 0.6fr" min-width="960px">
        <x-slot:head>
            <x-ui.select-all :checked="$this->allSelected()" />
            <span>No. Invois</span><span>Pelanggan</span><span>No. Tempahan</span><span class="text-right">Jumlah</span><span>Tarikh</span><span>Tarikh Tempoh</span><span class="text-center">Status</span><span class="text-right">Lihat</span>
        </x-slot:head>
        @forelse ($this->invoices as $v)
            <x-ui.tr wire:key="inv-{{ $v->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    <x-ui.checkbox value="{{ $v->id }}" wire:model.live="selected" aria-label="Pilih {{ $v->invoice_no }}" />
                    <span class="font-bold text-primary md:hidden dark:text-[#c9ce93]">{{ $v->invoice_no }}</span>
                    <span class="ml-auto md:hidden"><x-ui.badge :tone="$v->status->tone()">{{ $v->status->label() }}</x-ui.badge></span>
                </x-ui.td>
                <x-ui.td mobile="hide" class="text-[12.5px] font-bold text-primary dark:text-[#c9ce93]">{{ $v->invoice_no }}</x-ui.td>
                <x-ui.td span class="truncate text-ink max-md:font-semibold">{{ $v->customer_name }}</x-ui.td>
                <x-ui.td label="No. Tempahan" class="truncate text-[12.5px] text-muted">{{ $v->order_no ?: '-' }}</x-ui.td>
                <x-ui.td label="Jumlah" align="right" class="font-semibold text-ink tabular-nums">{{ rm($v->total_sen) }}</x-ui.td>
                <x-ui.td label="Tarikh" class="text-[12.5px] text-muted">{{ tarikh($v->issue_date) }}</x-ui.td>
                <x-ui.td label="Tarikh Tempoh" class="text-[12.5px] text-muted">{{ tarikh($v->due_date) }}</x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.badge :tone="$v->status->tone()">{{ $v->status->label() }}</x-ui.badge></x-ui.td>
                <x-ui.td align="right" span>
                    <a href="{{ route('finance.invoice', $v) }}" wire:navigate aria-label="Lihat {{ $v->invoice_no }}"
                       class="flex size-[30px] items-center justify-center rounded-[8px] border border-border text-primary max-md:h-11 max-md:w-full max-md:gap-2 max-md:text-[13px] max-md:font-semibold dark:text-[#c9ce93]">
                        <i class="ph ph-eye text-[16px]"></i><span class="md:hidden">Lihat Invois</span>
                    </a>
                </x-ui.td>
            </x-ui.tr>
        @empty
            <x-slot:empty>
                <x-ui.empty-state icon="receipt" title="Tiada invois untuk tapisan ini." />
            </x-slot:empty>
        @endforelse
        @if ($this->invoices->hasPages())
            <x-slot:footer>
                <x-ui.pagination :paginator="$this->invoices" noun="invois" />
            </x-slot:footer>
        @endif
    </x-ui.data-table>

    {{-- Rekod PO — Payment Completed --}}
    <div class="mt-[22px] overflow-hidden rounded-[12px] border border-border bg-surface">
        <div class="flex flex-wrap items-center gap-[10px] border-b border-divider px-4 py-4 md:px-5">
            <span class="flex size-[34px] items-center justify-center rounded-[9px] bg-success-soft text-success"><i class="ph ph-file-text text-[18px]"></i></span>
            <div class="min-w-0">
                <h2 class="text-[15px] font-bold text-ink">Rekod PO — Payment Completed</h2>
                <p class="mt-0.5 text-[12px] text-faint">Purchase Order vendor yang telah dijelaskan (rujukan finance)</p>
            </div>
            <span class="flex items-center gap-[10px] max-md:w-full md:ml-auto">
                <span class="rounded-[20px] bg-success-soft px-3 py-[5px] text-[12px] font-semibold text-success">{{ $this->poRecords->count() }} PO</span>
                <x-ui.button variant="success" size="sm" icon="microsoft-excel-logo" wire:click="exportPo" class="!px-[14px] max-md:ml-auto">Eksport Excel</x-ui.button>
            </span>
        </div>
        <x-ui.data-table cols="32px 1.1fr 1.4fr 1fr 1fr 1fr 1fr 0.8fr 0.8fr" min-width="920px" class="!rounded-none !border-0">
            <x-slot:head>
                <x-ui.select-all :checked="$this->allPoSelected()" wire:click="toggleAllPo" />
                <span>No. PO</span><span>Vendor</span><span class="text-right">Jumlah</span><span>Tarikh PO</span><span>Tarikh Bayar</span><span>Rujukan Bank</span><span class="text-center">Status</span><span class="text-right">Resit</span>
            </x-slot:head>
            @forelse ($this->poRecords as $p)
                <x-ui.tr wire:key="pop-{{ $p->id }}">
                    <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                        <x-ui.checkbox value="{{ $p->id }}" wire:model.live="selectedPo" aria-label="Pilih {{ $p->purchaseOrder->po_no }}" />
                        <button type="button" wire:click="viewPo({{ $p->id }})" class="font-bold text-primary md:hidden dark:text-[#c9ce93]">{{ $p->purchaseOrder->po_no }}</button>
                    </x-ui.td>
                    <x-ui.td mobile="hide"><button type="button" id="po-view-{{ $p->id }}" wire:click="viewPo({{ $p->id }})" class="text-left text-[12.5px] font-bold text-primary dark:text-[#c9ce93]">{{ $p->purchaseOrder->po_no }}</button></x-ui.td>
                    <x-ui.td span class="truncate text-ink max-md:font-semibold">{{ $p->vendor->name }}</x-ui.td>
                    <x-ui.td label="Jumlah" align="right" class="font-semibold text-ink tabular-nums">{{ rm($p->amount_sen) }}</x-ui.td>
                    <x-ui.td label="Tarikh PO" class="text-[12.5px] text-muted">{{ tarikh($p->purchaseOrder->created_at) }}</x-ui.td>
                    <x-ui.td label="Tarikh Bayar" class="text-[12.5px] text-muted">{{ tarikh($p->payment_date) }}</x-ui.td>
                    <x-ui.td label="Rujukan Bank" class="truncate font-mono text-[12.5px] text-muted">{{ $p->reference ?: '-' }}</x-ui.td>
                    <x-ui.td align="center" mobile="hide"><x-ui.badge tone="success" icon="fill check-circle" class="!px-[11px] !text-[11.5px]">Completed</x-ui.badge></x-ui.td>
                    <x-ui.td align="right" span>
                        <button type="button" wire:click="viewPo({{ $p->id }}, true)" class="inline-flex items-center gap-[5px] text-[12px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]"><i class="ph ph-eye text-[15px]"></i> Resit</button>
                    </x-ui.td>
                </x-ui.tr>
            @empty
                <x-slot:empty>
                    <x-ui.empty-state icon="file-text" title="Belum ada PO yang selesai dibayar." />
                </x-slot:empty>
            @endforelse
        </x-ui.data-table>
    </div>

    {{-- ===================== Rekod Bayaran PO ===================== --}}
    <x-ui.modal wire:model="showPo" title="Rekod Bayaran PO" subtitle="Payment Completed" icon="fill check-circle" tone="success" max-width="480px" body-class="px-4 py-[14px] md:px-6" :footer-border="false">
        @if ($po)
            @foreach ($poRows as $k => $val)
                <div class="flex items-center justify-between gap-3 border-b border-divider py-[11px]"><span class="text-[12.5px] text-faint">{{ $k }}</span><span class="text-right text-[13px] font-semibold text-ink">{{ $val }}</span></div>
            @endforeach
            <button type="button" wire:click="openReceipt" class="mt-4 flex w-full items-center gap-3 rounded-[10px] bg-bg p-[14px] text-left">
                <span class="flex size-[42px] shrink-0 items-center justify-center rounded-[9px] bg-danger-soft text-danger"><i class="ph ph-file-pdf text-[22px]"></i></span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[12.5px] font-semibold text-ink">{{ $poMedia?->file_name ?? 'Resit-'.$po->purchaseOrder->po_no.'.pdf' }}</span>
                    <span class="mt-0.5 block text-[11px] text-faint">Bukti bayaran dimuat naik HQ · klik untuk lihat</span>
                </span>
                <span class="inline-flex shrink-0 items-center gap-[5px] text-[12.5px] font-semibold text-primary dark:text-[#c9ce93]"><i class="ph ph-eye text-[16px]"></i> Lihat</span>
            </button>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            @if ($po)
                <x-ui.button icon="download-simple" target="_blank" :href="$poMedia ? \App\Models\VendorPayment::mediaUrl($poMedia) : route('vendors.po.pdf', ['po' => $po->purchase_order_id, 'muat-turun' => 1])">Muat Turun Resit</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Resit viewer ===================== --}}
    <x-ui.modal wire:model="showReceipt" :title="$poMedia?->file_name ?? ($po ? 'Resit-'.$po->purchaseOrder->po_no : 'Resit')" icon="file-text" max-width="620px" body-class="bg-[#EEF1EC] p-3 md:p-6 md:max-h-[72vh] md:overflow-y-auto">
        @if ($po)
            <div class="rounded-[10px] bg-white p-5 text-[#1A1D21] shadow-[0_4px_16px_rgba(0,0,0,.1)] md:p-8">
                @if ($poMedia && str_starts_with((string) $poMedia->mime_type, 'image/'))
                    <img src="{{ \App\Models\VendorPayment::mediaUrl($poMedia) }}" alt="Resit bank {{ $po->reference }}" class="mb-4 w-full rounded-[10px] border border-[#E2E8F0]">
                @elseif ($poMedia)
                    <a href="{{ \App\Models\VendorPayment::mediaUrl($poMedia) }}" target="_blank" rel="noopener" class="mb-4 block rounded-[10px] border border-dashed border-[#CBD5E1] bg-[#FBFCFA] p-[26px] text-center">
                        <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-[#FDECEC] text-[#DC2626]"><i class="ph ph-file-pdf text-[28px]"></i></span>
                        <span class="mt-3 block text-[13px] font-bold">Dokumen Bukti Bayaran</span>
                        <span class="mt-[3px] block text-[12px] text-[#94A3AC]">{{ $poMedia->file_name }} · klik untuk buka</span>
                    </a>
                @endif
                <div class="mt-1 flex justify-between border-b-2 border-[#42481c] pb-[14px]">
                    <div class="text-[15px] font-extrabold text-[#42481c]">{{ app(\App\Support\Settings::class)->get('company.name', 'Nadi Qurban Sdn Bhd') }}<div class="text-[10px] font-semibold text-[#94A3AC]">{{ app(\App\Support\Settings::class)->get('company.ssm', '1677511-A') }}</div></div>
                    <div class="text-right"><div class="text-[16px] font-extrabold">RESIT BAYARAN</div><div class="text-[12px] text-[#64748B]">{{ $po->reference ?: '-' }}</div></div>
                </div>
                <div class="mt-4">
                    @foreach ($poRows as $k => $val)
                        <div class="flex justify-between gap-3 border-b border-[#F1F5F4] py-[9px] text-[13px]"><span class="text-[#64748B]">{{ $k }}</span><span class="text-right font-semibold">{{ $val }}</span></div>
                    @endforeach
                </div>
                <div class="mt-[18px] text-center text-[11px] text-[#94A3AC]">Dijana oleh {{ app(\App\Support\Settings::class)->get('company.name', 'Nadi Qurban Sdn Bhd') }}</div>
            </div>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            @if ($po)
                <x-ui.button variant="secondary" icon="printer" target="_blank" :href="route('vendors.po.pdf', $po->purchase_order_id)" class="!border-primary !text-primary">Cetak</x-ui.button>
                <x-ui.button icon="download-simple" target="_blank" :href="$poMedia ? \App\Models\VendorPayment::mediaUrl($poMedia) : route('vendors.po.pdf', ['po' => $po->purchase_order_id, 'muat-turun' => 1])">Muat Turun</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Cipta Invois / Quotation Baharu ===================== --}}
    @if ($canManage)
        <x-ui.modal wire:model="showDraft" :title="$isQuote ? 'Quotation Baharu' : 'Cipta Invois'" :subtitle="$isQuote ? 'Sebut harga untuk pelanggan' : 'Invois baharu untuk pelanggan'" :icon="$isQuote ? 'file-text' : 'receipt'" max-width="800px" :footer-border="false">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="col-span-full grid grid-cols-1 gap-4 rounded-[12px] bg-bg px-4 py-5 md:grid-cols-2 md:px-[22px]">
                    <div class="col-span-full mb-0.5 text-[13px] font-bold tracking-[.3px] text-primary dark:text-[#c9ce93]">MAKLUMAT SYARIKAT (boleh edit)</div>
                    <div><label class="{{ $miniLabel }}" for="co-name">Nama Syarikat</label><input id="co-name" wire:model="draft.company.name" class="{{ $mini }}">@error('draft.company.name')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror</div>
                    <div><label class="{{ $miniLabel }}" for="co-ssm">No. SSM</label><input id="co-ssm" wire:model="draft.company.ssm" class="{{ $mini }}"></div>
                    <div class="col-span-full"><label class="{{ $miniLabel }}" for="co-addr">Alamat Syarikat</label><input id="co-addr" wire:model="draft.company.address" class="{{ $mini }}"></div>
                    <div><label class="{{ $miniLabel }}" for="co-phone">Telefon</label><input id="co-phone" wire:model="draft.company.phone" class="{{ $mini }}"></div>
                    <div><label class="{{ $miniLabel }}" for="co-email">Emel</label><input id="co-email" wire:model="draft.company.email" class="{{ $mini }}">@error('draft.company.email')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror</div>
                </div>

                <x-ui.field label="Nama Pelanggan" id="draft-name" wire:model="draft.name" placeholder="Nama penuh" span />
                <x-ui.field label="No. Telefon" wire:model="draft.phone" placeholder="0123345671" inputmode="tel" />
                @if ($isQuote)
                    <x-ui.field label="Emel Pelanggan" type="email" wire:model="draft.email" placeholder="emel@contoh.com" />
                    <x-ui.field label="Alamat Pelanggan" wire:model="draft.address" placeholder="No., Jalan, Poskod, Bandar" />
                @else
                    <x-ui.field label="No. Tempahan" wire:model="draft.order" placeholder="NQ-..." />
                    <x-ui.field label="Emel Pelanggan" type="email" wire:model="draft.email" placeholder="emel@contoh.com" span />
                    <x-ui.field label="Alamat Pelanggan" wire:model="draft.address" placeholder="No., Jalan, Poskod, Bandar, Negeri" span />
                @endif

                {{-- Items --}}
                <div class="col-span-full rounded-[11px] border border-border bg-[#F9FAF7] p-3 md:p-4 dark:bg-bg">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <span class="text-[12px] font-bold tracking-[.4px] text-primary uppercase dark:text-[#c9ce93]">{{ $isQuote ? 'Item Sebut Harga' : 'Item Invois' }}</span>
                        <x-ui.button size="sm" icon="plus" wire:click="addItem" class="!px-[13px] !py-[7px] !text-[12px]">Tambah Item</x-ui.button>
                    </div>
                    <div class="hidden grid-cols-[2.4fr_0.8fr_1.2fr_30px] gap-[10px] px-1 pb-2 text-[10.5px] font-bold tracking-[.3px] text-faint uppercase md:grid">
                        <span>{{ $isQuote ? 'Servis / Pakej' : 'Perkara' }}</span><span>Kuantiti</span><span class="text-right">{{ $isQuote ? 'Harga Seunit (RM)' : 'Harga (RM)' }}</span><span></span>
                    </div>
                    @foreach ($draft->items as $i => $it)
                        <div wire:key="item-{{ $i }}" class="mb-[9px] grid grid-cols-[1fr_1fr_44px] items-center gap-[10px] max-md:rounded-[10px] max-md:border max-md:border-border max-md:bg-surface max-md:p-3 md:grid-cols-[2.4fr_0.8fr_1.2fr_30px]">
                            <input wire:model.live.debounce.400ms="draft.items.{{ $i }}.description" placeholder="cth. Qurban Lembu — Delima" aria-label="Perkara" class="{{ $mini }} max-md:col-span-3">
                            <input wire:model.live.debounce.400ms="draft.items.{{ $i }}.qty" type="number" min="1" aria-label="Kuantiti" class="{{ $mini }} text-center">
                            <input wire:model.live.debounce.400ms="draft.items.{{ $i }}.price" inputmode="decimal" placeholder="3500" aria-label="Harga" class="{{ $mini }} text-right">
                            <button type="button" wire:click="removeItem({{ $i }})" aria-label="Buang item" class="flex h-9 w-[30px] items-center justify-center rounded-[8px] border border-[#F0DADA] bg-surface text-danger max-md:size-11"><i class="ph ph-trash text-[15px]"></i></button>
                            @if ($errors->has("draft.items.$i.description") || $errors->has("draft.items.$i.qty") || $errors->has("draft.items.$i.price"))
                                <p class="col-span-full text-[11.5px] text-danger">{{ $errors->first("draft.items.$i.description") ?: ($errors->first("draft.items.$i.qty") ?: $errors->first("draft.items.$i.price")) }}</p>
                            @endif
                        </div>
                    @endforeach
                    @error('draft.items')<p class="text-[11.5px] text-danger">{{ $message }}</p>@enderror
                    <div class="mt-3 flex items-center justify-between border-t border-border pt-3">
                        <span class="text-[12.5px] font-semibold text-muted">Jumlah Keseluruhan</span>
                        <span class="text-[16px] font-extrabold text-primary dark:text-[#c9ce93]">RM {{ number_format($draft->totalSen() / 100, 2) }}</span>
                    </div>
                </div>

                @unless ($isQuote)
                    <x-ui.field label="Tarikh Tempoh" type="date" wire:model="draft.due" />
                @endunless
                <x-ui.field label="Nota Tambahan" as="textarea" rows="3" wire:model="draft.note" :placeholder="$isQuote ? 'Terma sebut harga, syarat, dsb.' : 'Terma bayaran, arahan khas, dsb.'" span />
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button variant="secondary" icon="file-pdf" wire:click="preview" class="!border-primary !text-primary">Pratonton PDF</x-ui.button>
                <x-ui.button icon="check" id="btn-save-draft" wire:click="save" wire:loading.attr="disabled" wire:target="save">{{ $isQuote ? 'Cipta Quotation' : 'Cipta Invois' }}</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        {{-- A4 preview of the draft --}}
        <x-ui.modal wire:model="showPreview" :title="($isQuote ? 'Quotation' : 'Invois').' · A4'" :subtitle="$showPreview ? $this->nextNumber() : null" icon="file-pdf" max-width="820px" body-class="bg-[#EEF1EC] p-3 md:p-6">
            @if ($showPreview)
                <div x-ref="doc">
                    <x-ui.doc-a4 padding="22mm 20mm">
                        @include('pdf.partials.finance-doc-a4', ['doc' => $draft->document($this->nextNumber())])
                    </x-ui.doc-a4>
                </div>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" icon="x" x-on:click="open = false">Tutup</x-ui.button>
                <x-ui.button variant="secondary" icon="printer" x-on:click="window.nqPrint($root.querySelector('[x-ref=doc] [style*=min-height]'))" class="!border-primary !text-primary">Cetak</x-ui.button>
                <x-ui.button variant="gold" icon="download-simple" wire:click="downloadDraft" wire:loading.attr="disabled" wire:target="downloadDraft">Muat Turun PDF</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
