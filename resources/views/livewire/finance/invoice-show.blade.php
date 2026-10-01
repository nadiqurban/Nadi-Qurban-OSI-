@php
    $inv = $invoice;
    $paid = $inv->status === \App\Enums\InvoiceStatus::Paid;
    $sub = collect([$inv->order_no, $inv->customer_name])->filter()->implode(' · ');
@endphp

<div>
    <a href="{{ route('finance.index') }}" wire:navigate class="mb-[18px] inline-flex items-center gap-[7px] text-[13.5px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">
        <i class="ph ph-arrow-left text-[17px]"></i> Kembali ke senarai
    </a>

    <div class="mb-[22px] flex flex-wrap items-end gap-3 md:gap-4">
        <div class="mr-auto min-w-0 max-md:w-full">
            <h1 class="text-[20px] font-bold text-ink md:text-[23px]">Invois {{ $inv->invoice_no }}</h1>
            <p class="mt-1 text-[13.5px] text-muted">{{ $sub }}</p>
        </div>
        <x-ui.badge :tone="$inv->status->tone()">{{ $inv->status->label() }}</x-ui.badge>
        <x-ui.button variant="secondary" icon="printer" target="_blank" :href="route('finance.invoice.pdf', $inv)" class="!px-[15px] [&>i]:!text-[16px] [&>i]:text-muted max-md:flex-1">Cetak</x-ui.button>
        @if ($canManage && ! $paid)
            <x-ui.button variant="success" icon="check-circle" id="btn-confirm-pay" wire:click="openPay" class="[&>i]:!text-[16px] max-md:flex-1">Sahkan Bayaran</x-ui.button>
        @endif
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-divider px-4 py-5 md:px-6 md:py-[22px]">
                <div class="min-w-0">
                    <div class="text-[11.5px] tracking-[.4px] text-faint uppercase">Bil Kepada</div>
                    <div class="mt-[5px] text-[15px] font-bold text-ink">{{ $inv->customer_name }}</div>
                    @if ($inv->customer_address)<div class="mt-[3px] text-[12.5px] text-muted">{{ $inv->customer_address }}</div>@endif
                    @if ($inv->customer_phone || $inv->customer_email)<div class="mt-[3px] text-[12.5px] text-muted">{{ collect([$inv->customer_phone, $inv->customer_email])->filter()->implode(' · ') }}</div>@endif
                </div>
                <div class="text-right">
                    <div class="text-[11.5px] tracking-[.4px] text-faint uppercase">Tarikh Invois</div>
                    <div class="mt-[5px] text-[14px] font-semibold text-ink">{{ tarikh($inv->issue_date) }}</div>
                    <div class="mt-[3px] text-[12.5px] text-muted">Tempoh: {{ $termDays }} hari</div>
                </div>
            </div>
            <div class="hidden grid-cols-[2fr_0.7fr_1fr_1fr] border-b border-divider px-6 py-3 text-[11px] font-bold tracking-[.4px] text-faint uppercase md:grid">
                <span>Item</span><span class="text-center">Kuantiti</span><span class="text-right">Harga</span><span class="text-right">Jumlah</span>
            </div>
            @foreach ($inv->items as $li)
                <div class="grid grid-cols-[1fr_auto] items-center gap-x-3 gap-y-1 border-b border-divider px-4 py-[14px] md:grid-cols-[2fr_0.7fr_1fr_1fr] md:px-6">
                    <div class="min-w-0">
                        <div class="text-[13.5px] font-semibold text-ink">{{ $li->description }}</div>
                        @if ($li->detail)<div class="mt-0.5 text-[12px] text-faint">{{ $li->detail }}</div>@endif
                        <div class="mt-0.5 text-[12px] text-muted md:hidden">{{ $li->quantity }} × {{ rm($li->unit_price_sen) }}</div>
                    </div>
                    <span class="hidden text-center text-[13px] text-ink-2 md:block">{{ $li->quantity }}</span>
                    <span class="hidden text-right text-[13px] text-ink-2 tabular-nums md:block">{{ rm($li->unit_price_sen) }}</span>
                    <span class="text-right text-[13px] font-semibold text-ink tabular-nums">{{ rm($li->line_total_sen) }}</span>
                </div>
            @endforeach
            <div class="flex flex-col items-end gap-2 px-4 py-4 md:px-6">
                <div class="flex w-[230px] justify-between text-[13.5px]"><span class="text-muted">Subjumlah</span><span class="tabular-nums text-ink">{{ rm($inv->subtotal_sen) }}</span></div>
                <div class="flex w-[230px] justify-between text-[13.5px]"><span class="text-muted">SST (0%)</span><span class="tabular-nums text-ink">{{ rm($inv->tax_sen, true) }}</span></div>
                <div class="mt-1 flex w-[230px] justify-between border-t border-border pt-2 text-[16px] font-extrabold text-primary dark:text-[#c9ce93]"><span class="text-muted">Jumlah</span><span class="tabular-nums">{{ rm($inv->total_sen) }}</span></div>
            </div>
        </section>

        <div class="flex flex-col gap-5">
            <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
                <h2 class="mb-4 text-[15px] font-bold text-ink">Ringkasan Bayaran</h2>
                <div class="flex flex-col gap-[14px]">
                    @foreach ($payInfo as $k => $val)
                        <div class="flex items-center justify-between gap-3"><span class="text-[13px] text-muted">{{ $k }}</span><span class="text-[13.5px] font-semibold text-ink">{{ $val }}</span></div>
                    @endforeach
                </div>
            </section>
            <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
                <h2 class="mb-4 text-[15px] font-bold text-ink">Rekod Transaksi</h2>
                <div class="flex flex-col">
                    @foreach ($this->timeline() as $t)
                        <div class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span class="flex size-[26px] shrink-0 items-center justify-center rounded-full {{ \App\Support\Tone::classes($t['tone']) }}"><i class="{{ str_starts_with($t['icon'], 'fill ') ? 'ph-fill ph-'.substr($t['icon'], 5) : 'ph ph-'.$t['icon'] }} text-[14px]"></i></span>
                                <span @class(['my-[3px] min-h-3 w-0.5 flex-1', 'bg-border' => ! $loop->last, 'bg-transparent' => $loop->last])></span>
                            </div>
                            <div class="pb-[14px]">
                                <div class="text-[13px] font-semibold text-ink">{{ $t['title'] }}</div>
                                <div class="mt-0.5 text-[11.5px] text-faint">{{ $t['time'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    @if ($canManage)
        <x-ui.modal wire:model="showPay" title="Sahkan Bayaran" :subtitle="$inv->invoice_no.' · Baki '.rm($inv->balanceSen())" icon="check-circle" tone="success" max-width="480px" :footer-border="false">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field label="Jumlah (RM)" id="pay-amount" wire:model="payAmount" inputmode="decimal" />
                <x-ui.field label="Tarikh Bayar" type="date" wire:model="payDate" />
                <x-ui.field label="Kaedah" as="select" wire:model="payMethod" span>
                    @foreach (\App\Models\InvoicePayment::METHODS as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </x-ui.field>
                <x-ui.field label="No. Rujukan" hint="(pilihan)" wire:model="payReference" placeholder="cth. FPX20270612001" span />
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button variant="success" icon="check-circle" id="btn-pay-save" wire:click="confirmPayment" wire:loading.attr="disabled" wire:target="confirmPayment">Sahkan</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
