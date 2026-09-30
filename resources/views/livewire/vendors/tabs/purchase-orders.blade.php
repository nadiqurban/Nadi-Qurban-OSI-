@php
    $label = 'text-[11px] tracking-[.4px] text-faint uppercase';
    $editable = $po && $canManage && ! in_array($po->status, [\App\Enums\PoStatus::Completed, \App\Enums\PoStatus::Cancelled], true);
@endphp

<div>
    @if ($creating && $canManage)
        {{-- ===================== Cipta PO ===================== --}}
        <button type="button" wire:click="back" class="mb-4 inline-flex min-h-10 items-center gap-[7px] text-[13px] font-semibold text-primary dark:text-[#c9ce93]"><i class="ph ph-arrow-left text-[16px]"></i> Kembali ke senarai PO</button>
        <section class="max-w-[760px] overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="border-b border-divider px-5 py-5 md:px-6"><h2 class="text-[16px] font-bold text-ink">Cipta Purchase Order Baharu</h2><p class="mt-[3px] text-[12px] text-faint">No. auto-generate: <b class="text-primary dark:text-[#c9ce93]">{{ $nextPo }}</b></p></div>
            <form wire:submit="saveCreate" id="po-create" class="grid grid-cols-1 gap-4 px-5 py-[22px] md:grid-cols-2 md:px-6" novalidate>
                <x-ui.field label="Vendor" :value="$v->name" readonly class="bg-bg text-ink-2" />
                <x-ui.field label="Country" :value="$v->country->name" readonly class="bg-bg text-ink-2" />
                <x-ui.field label="Servis" as="select" wire:model="create.service">
                    @foreach (\App\Enums\Service::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </x-ui.field>
                <x-ui.field label="Haiwan" wire:model="create.animal" placeholder="cth. Lembu (1 bhg)" />
                <x-ui.field label="Kuantiti" type="number" min="1" wire:model="create.quantity" placeholder="0" inputmode="numeric" />
                <x-ui.field label="Unit Price ({{ $create['currency'] }})" type="number" step="0.01" min="0" wire:model="create.unit_price" placeholder="0.00" inputmode="decimal" />
                <div>
                    <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Currency</span>
                    <x-ui.currency-toggle :value="$create['currency']" action="setCreateCurrency" class="!rounded-[9px]" />
                    @if ($create['currency'] === 'USD')<p class="mt-1 text-[11px] text-faint">Kadar semasa: USD 1 = RM {{ number_format($usdRate, 2) }} (disimpan pada PO)</p>@endif
                </div>
                <x-ui.field label="Implementation Date" type="date" wire:model="create.date" />
                <x-ui.field label="Notes" as="textarea" rows="3" wire:model="create.notes" placeholder="Arahan khas untuk vendor..." span />
            </form>
            <div class="flex flex-wrap justify-end gap-[10px] px-5 pb-[22px] md:px-6 max-md:[&>*]:flex-1">
                <x-ui.button variant="secondary" wire:click="back">Batal</x-ui.button>
                <x-ui.button type="submit" form="po-create" icon="check">Simpan sebagai Draft</x-ui.button>
            </div>
        </section>
    @elseif ($po)
        {{-- ===================== PO detail ===================== --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <button type="button" wire:click="back" class="inline-flex min-h-10 items-center gap-[7px] text-[13px] font-semibold text-primary dark:text-[#c9ce93]"><i class="ph ph-arrow-left text-[16px]"></i> Kembali ke senarai PO</button>
            <x-ui.button icon="printer" wire:click="$set('showReceipt', true)" class="max-md:w-full">Cetak / Jana Resit PO</x-ui.button>
        </div>
        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
            <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
                <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-divider px-5 py-5 md:px-6">
                    <div><h2 class="text-[16px] font-bold text-primary dark:text-[#c9ce93]">{{ $po->po_no }}</h2><p class="mt-[3px] text-[12px] text-faint">Dikeluarkan {{ tarikh($po->created_at) }}</p></div>
                    <span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold {{ $po->status->badgeClasses() }}">{{ $po->status->label() }}</span>
                </div>
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 px-5 py-5 sm:grid-cols-2 md:px-6">
                    @foreach ([
                        'Vendor' => $v->name,
                        'Supplier' => $v->supplier ?: '-',
                        'Country' => $v->country->name,
                        'Animal' => $po->animal_label,
                        'Quantity' => $po->quantity,
                        'Unit Price' => $po->money($po->unit_price_minor, $display, $usdRate),
                        'Total Amount' => $po->money($po->total_minor, $display, $usdRate),
                        'Currency' => $po->currency,
                        'Servis' => $po->service->label(),
                        'Implementation Date' => $po->implementation_date ? tarikh($po->implementation_date) : '-',
                        'Payment Terms' => $po->payment_terms ?: '-',
                        'PIC Vendor' => $v->pic_name ?: '-',
                        'Tarikh Dikeluarkan' => tarikh($po->created_at),
                        'Approved By' => $po->creator?->name ?? '-',
                        'PO Reference' => $po->reference ?: '-',
                    ] as $k => $val)
                        <div><div class="{{ $label }}">{{ $k }}</div><div class="mt-1 text-[14px] font-semibold break-words text-ink">{{ $val }}</div></div>
                    @endforeach
                </div>

                {{-- Editable rate table --}}
                <div class="px-5 pb-5 md:px-6">
                    <div class="mb-2 {{ $label }}">Kadar Harga @if ($editable)<span class="tracking-normal text-primary normal-case dark:text-[#c9ce93]">&middot; boleh edit jika ada perubahan harga vendor</span>@endif</div>
                    <div class="overflow-x-auto rounded-[10px] border border-border">
                        <div class="min-w-[460px]">
                            <div class="grid grid-cols-[1.6fr_0.7fr_1.1fr_1.1fr] border-b border-border bg-bg px-[14px] py-[10px] text-[11px] font-bold tracking-[.4px] text-faint uppercase"><span>Servis</span><span class="text-center">Kuantiti</span><span class="text-right">Rate (Unit Price)</span><span class="text-right">Jumlah</span></div>
                            <div class="grid grid-cols-[1.6fr_0.7fr_1.1fr_1.1fr] items-center gap-2 px-[14px] py-3">
                                <input type="text" wire:model="edit.service_label" @disabled(! $editable) aria-label="Servis" class="w-full rounded-[7px] border border-border px-[9px] py-[7px] text-[13px] font-semibold text-ink outline-none focus:border-primary disabled:bg-bg max-md:text-[16px]">
                                <span class="flex justify-center"><input type="number" min="1" wire:model="edit.quantity" @disabled(! $editable) aria-label="Kuantiti" class="w-16 rounded-[7px] border border-border px-2 py-[7px] text-center text-[13px] font-semibold text-ink tabular-nums outline-none focus:border-primary disabled:bg-bg max-md:text-[16px]"></span>
                                <span class="flex items-center justify-end gap-1.5"><span class="text-[12px] text-faint">{{ $po->currency }}</span><input type="number" step="0.01" min="0" wire:model="edit.unit_price" @disabled(! $editable) aria-label="Kadar" class="w-24 rounded-[7px] border border-border px-[9px] py-[7px] text-right text-[13px] font-semibold text-primary tabular-nums outline-none focus:border-primary disabled:bg-bg max-md:text-[16px]"></span>
                                <span class="text-right text-[14px] font-bold text-ink tabular-nums">{{ $po->money($po->total_minor, $po->currency, $usdRate) }}</span>
                            </div>
                        </div>
                    </div>
                    @error('edit.*')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 gap-4 px-5 pb-5 sm:grid-cols-2 sm:gap-6 md:px-6">
                    @foreach (['billing' => 'Billing Address', 'shipping' => 'Shipping Address'] as $key => $title)
                        <div>
                            <div class="mb-[5px] {{ $label }}">{{ $title }} @if ($editable)<span class="tracking-normal text-primary normal-case dark:text-[#c9ce93]">&middot; boleh edit</span>@endif</div>
                            <textarea rows="4" wire:model="edit.{{ $key }}" @disabled(! $editable) aria-label="{{ $title }}" class="w-full resize-y rounded-[9px] border border-border bg-bg px-[13px] py-[11px] text-[13px] leading-[1.55] text-ink-2 outline-none focus:border-primary max-md:text-[16px]"></textarea>
                        </div>
                    @endforeach
                </div>
                <div class="px-5 pb-5 md:px-6">
                    <div class="mb-1.5 {{ $label }}">Notes @if ($editable)<span class="tracking-normal text-primary normal-case dark:text-[#c9ce93]">&middot; boleh edit</span>@endif</div>
                    <textarea rows="3" wire:model="edit.notes" @disabled(! $editable) aria-label="Notes" class="w-full resize-y rounded-[9px] border border-border bg-bg px-[14px] py-3 text-[13px] leading-[1.6] text-ink-3 outline-none focus:border-primary max-md:text-[16px]"></textarea>
                </div>
                @if ($editable)
                    <div class="flex justify-end px-5 pb-5 md:px-6">
                        <x-ui.button icon="floppy-disk" wire:click="saveDetail" class="max-md:w-full">Simpan Maklumat</x-ui.button>
                    </div>
                @endif
            </section>

            <div class="flex flex-col gap-5">
                {{-- Status PO --}}
                <section class="rounded-[12px] border border-border bg-surface px-5 py-5 md:px-6">
                    <h2 class="mb-4 text-[15px] font-bold text-ink">Status PO</h2>
                    @php
                        $cancelled = $po->status === \App\Enums\PoStatus::Cancelled;
                        $current = $cancelled ? 1 : $po->status->step();
                        $times = [$po->created_at, $po->sent_at, $po->accepted_at, $po->in_progress_at, $po->completed_at];
                    @endphp
                    <div class="flex flex-col">
                        @foreach (\App\Enums\PoStatus::flow() as $k => $step)
                            @php $isDone = $k < $current || ($k === $current && $step === \App\Enums\PoStatus::Completed); $isCur = $k === $current && ! $cancelled && ! $isDone; @endphp
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <span @class(['flex size-[26px] shrink-0 items-center justify-center rounded-full', 'bg-primary text-white' => $isDone, 'bg-gold text-white' => $isCur, 'bg-divider text-faint' => ! $isDone && ! $isCur])><i class="{{ $isDone ? 'ph-fill ph-check' : ($isCur ? 'ph ph-dot-outline' : 'ph ph-circle') }} text-[13px]"></i></span>
                                    <span @class(['my-[3px] min-h-3 w-0.5 flex-1', 'bg-primary' => $isDone && ! $loop->last, 'bg-border' => ! $isDone && ! $loop->last])></span>
                                </div>
                                <div class="pb-[14px]">
                                    <div @class(['text-[13px] font-semibold', 'text-ink' => $isDone, 'text-gold-ink' => $isCur, 'text-faint' => ! $isDone && ! $isCur])>{{ $step->label() }}</div>
                                    @if (($isDone || $isCur) && $times[$k])<div class="mt-0.5 text-[11.5px] text-faint">{{ tarikh($times[$k], true) }}</div>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($cancelled)
                        <div class="mt-1 flex items-center gap-2 rounded-[9px] bg-danger-soft px-3 py-2.5 text-[12.5px] font-semibold text-danger"><i class="ph-fill ph-x-circle text-[16px]"></i> Dibatalkan {{ $po->cancelled_at ? tarikh($po->cancelled_at) : '' }}</div>
                    @endif
                    @if ($canManage)
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($po->status === \App\Enums\PoStatus::Draft)
                                <x-ui.button size="sm" icon="paper-plane-tilt" wire:click="send" class="flex-1">Hantar kepada Vendor</x-ui.button>
                            @endif
                            @if (! in_array($po->status, [\App\Enums\PoStatus::Completed, \App\Enums\PoStatus::Cancelled], true))
                                <x-ui.button size="sm" variant="danger-soft" icon="x" wire:click="cancel({{ $po->id }})" wire:confirm="Batalkan {{ $po->po_no }}?" class="flex-1">Batal PO</x-ui.button>
                            @endif
                        </div>
                    @endif
                </section>

                {{-- Vendor Acceptance --}}
                @php $accepted = (bool) $po->accepted_at; @endphp
                <section @class(['rounded-[12px] border bg-surface px-5 py-5 md:px-6', 'border-[#BBE5C9]' => $accepted, 'border-border' => ! $accepted])>
                    <div class="mb-3 flex items-center gap-[10px]"><i @class(['text-[22px]', 'ph-fill ph-seal-check text-success' => $accepted, 'ph ph-hourglass-medium text-warning' => ! $accepted])></i><h2 class="text-[15px] font-bold text-ink">Vendor Acceptance</h2></div>
                    @if ($accepted)
                        <div class="flex flex-col gap-[11px]">
                            <div class="flex items-center justify-between"><span class="text-[12.5px] text-muted">Tarikh</span><span class="text-[13.5px] font-semibold text-ink">{{ tarikh($po->accepted_at) }}</span></div>
                            <div class="flex items-center justify-between"><span class="text-[12.5px] text-muted">Masa</span><span class="text-[13.5px] font-semibold text-ink">{{ $po->accepted_at->format('H:i') }}</span></div>
                            <div class="flex items-center justify-between"><span class="text-[12.5px] text-muted">PIC Vendor</span><span class="text-[13.5px] font-semibold text-ink">{{ $po->acceptedBy?->name ?? ($v->pic_name ?: '-') }}</span></div>
                        </div>
                    @elseif ($po->status === \App\Enums\PoStatus::Sent)
                        <p class="mb-[14px] text-[12.5px] leading-normal text-muted">Menunggu pengesahan vendor. Vendor perlu klik "Accept PO" untuk meneruskan.</p>
                        @if ($isPic || $canManage)
                            <x-ui.button variant="success" icon="check-circle" wire:click="accept" wire:confirm="Terima {{ $po->po_no }}?" class="w-full !py-3 !font-bold" id="po-accept">Accept PO</x-ui.button>
                        @endif
                    @else
                        <p class="text-[12.5px] leading-normal text-muted">{{ $po->status === \App\Enums\PoStatus::Draft ? 'PO masih draf — hantar kepada vendor untuk pengesahan.' : 'Tiada pengesahan vendor.' }}</p>
                    @endif
                </section>
            </div>
        </div>

        <x-ui.modal wire:model="showReceipt" title="Pratonton Resit Purchase Order" :subtitle="$po->po_no" icon="receipt" max-width="840px" body-class="bg-[#EEF1EC] p-3 md:p-5">
            <x-ui.doc-a4 padding="36px 40px">
                @include('pdf.partials.purchase-order-body', ['po' => $po])
            </x-ui.doc-a4>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
                <x-ui.button variant="gold" icon="download-simple" :href="route('vendors.po.pdf', ['po' => $po, 'muat-turun' => 1])">Muat Turun</x-ui.button>
                <x-ui.button icon="printer" target="_blank" :href="route('vendors.po.pdf', $po)">Cetak</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @else
        {{-- ===================== PO list ===================== --}}
        <div class="overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-[18px] md:px-[22px]">
                <div><h2 class="text-[15.5px] font-bold text-ink">Purchase Order</h2>@if ($canManage)<p class="mt-[3px] text-[12px] text-faint">No. seterusnya: <b class="text-primary dark:text-[#c9ce93]">{{ $nextPo }}</b> (auto-generate)</p>@endif</div>
                <div class="flex items-center gap-[10px] max-md:w-full">
                    <x-ui.currency-toggle :value="$display" />
                    @if ($canManage)
                        <x-ui.button size="sm" icon="plus" wire:click="startCreate" class="!px-[15px] !py-[10px] !text-[13px] max-md:flex-1">Cipta PO</x-ui.button>
                    @endif
                </div>
            </div>
            <div class="overflow-x-auto">
                <div class="min-w-[840px]">
                    <div class="grid grid-cols-[32px_1.3fr_1.1fr_1fr_0.7fr_1fr_1fr_1.1fr_0.6fr] items-center border-b border-border bg-bg px-[22px] py-3 text-[11px] font-bold tracking-[.4px] text-faint uppercase">
                        <x-ui.select-all :checked="$this->allSelected()" /><span>No. PO</span><span>Servis</span><span>Haiwan</span><span class="text-center">Kuantiti</span><span class="text-right">Jumlah</span><span class="pl-4">Tarikh Laksana</span><span class="text-center">Status</span><span></span>
                    </div>
                    @forelse ($this->orders as $row)
                        <div class="grid grid-cols-[32px_1.3fr_1.1fr_1fr_0.7fr_1fr_1fr_1.1fr_0.6fr] items-center border-b border-divider px-[22px] py-[14px] last:border-b-0" wire:key="porow-{{ $row->id }}">
                            <x-ui.checkbox value="{{ $row->id }}" wire:model.live="selected" aria-label="Pilih {{ $row->po_no }}" />
                            <button type="button" wire:click="open({{ $row->id }})" id="po-open-{{ $row->id }}" class="text-left text-[12.5px] font-bold text-primary dark:text-[#c9ce93]">{{ $row->po_no }}</button>
                            <button type="button" wire:click="open({{ $row->id }})" class="text-left text-[13px] text-ink-2">{{ $row->service->label() }}</button>
                            <span class="text-[13px] text-ink-2">{{ $row->animal_label }}</span>
                            <span class="text-center text-[13px] text-ink-2">{{ $row->quantity }}</span>
                            <span class="text-right text-[13px] font-semibold text-ink tabular-nums">{{ $row->money($row->total_minor, $display, $usdRate) }}</span>
                            <span class="pl-4 text-[12.5px] text-muted">{{ $row->implementation_date ? tarikh($row->implementation_date) : '-' }}</span>
                            <span class="flex justify-center"><span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold whitespace-nowrap {{ $row->status->badgeClasses() }}">{{ $row->status->label() }}</span></span>
                            <span class="flex items-center justify-end gap-1.5">
                                <button type="button" wire:click="open({{ $row->id }})" class="flex size-7 items-center justify-center rounded-[7px] border border-border text-primary" aria-label="Lihat {{ $row->po_no }}"><i class="ph ph-{{ $canManage ? 'pencil-simple' : 'eye' }} text-[14px]"></i></button>
                                @if ($canManage && ! in_array($row->status, [\App\Enums\PoStatus::Completed, \App\Enums\PoStatus::Cancelled], true))
                                    <button type="button" wire:click="cancel({{ $row->id }})" wire:confirm="Batalkan {{ $row->po_no }}?" class="flex size-7 items-center justify-center rounded-[7px] border border-[#F7CFCF] bg-danger-soft text-danger" aria-label="Batal {{ $row->po_no }}"><i class="ph ph-x text-[14px]"></i></button>
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-[13px] text-faint">Belum ada Purchase Order untuk vendor ini.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
