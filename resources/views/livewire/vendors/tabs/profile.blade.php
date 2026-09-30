@php
    $info = [
        ['Identiti', 'Vendor ID', $v->vendor_no ?: '-'],
        [null, 'Kod Vendor', $v->code],
        [null, 'Nama Vendor', $v->name],
        [null, 'Nama Syarikat', $v->companyName()],
        [null, 'Nama Supplier', $v->supplier ?: '-'],
        [null, 'Vendor Level', $v->level?->label() ?? '-'],
        [null, 'Status', $v->status->label()],
        ['Hubungan', 'No. Telefon', $v->phone ?: '-'],
        [null, 'Emel', $v->email ?: '-'],
        [null, 'PIC Vendor', $v->pic_name ?: '-'],
        [null, 'Negara', $v->country->name],
        [null, 'Jenis Haiwan', implode(', ', $v->animals ?? []) ?: '-'],
        ['Maklumat Bank', 'Nama Bank', $v->bank_name ?: '-'],
        [null, 'Nama Pemegang Akaun', $v->bank_holder ?: '-'],
        [null, 'No. Akaun', $v->bank_account ?: '-'],
        [null, 'Kod Swift', $v->swift ?: '-'],
        [null, 'Alamat Bank', $v->bank_address ?: '-'],
    ];
@endphp

<div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
    <section class="rounded-[12px] border border-border bg-surface px-5 py-[22px] md:px-6">
        <div class="mb-4 flex items-center justify-between gap-2">
            <h2 class="text-[15px] font-bold text-ink">Maklumat Vendor</h2>
            @if ($canManage)
                <x-ui.button size="sm" variant="secondary" icon="pencil-simple" wire:click="openEditVendor({{ $v->id }})" class="!border-primary !text-primary">Edit Maklumat</x-ui.button>
            @endif
        </div>
        <div class="flex flex-col gap-[13px]">
            @foreach ($info as [$section, $k, $val])
                @if ($section)
                    <div class="border-t border-divider pt-1.5 text-[10.5px] font-bold tracking-[.8px] text-primary uppercase first:border-t-0 first:pt-0 dark:text-[#c9ce93]">{{ $section }}</div>
                @endif
                <div class="flex items-center justify-between gap-3"><span class="shrink-0 text-[12.5px] text-faint">{{ $k }}</span><span class="text-right text-[13.5px] font-semibold break-words text-ink">{{ $val }}</span></div>
            @endforeach
        </div>
    </section>

    <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-5 pb-[14px] md:px-6">
            <h2 class="text-[15px] font-bold text-ink">Purchase Order Terkini</h2>
            <x-ui.currency-toggle :value="$display" />
        </div>
        <div class="overflow-x-auto">
            <div class="min-w-[480px]">
                <div class="grid grid-cols-[1.2fr_1.1fr_0.6fr_1fr_1fr] border-b border-border px-5 pb-[10px] text-[11px] font-bold tracking-[.4px] text-faint uppercase md:px-6">
                    <span>No. PO</span><span>Servis</span><span class="text-center">Unit</span><span class="text-right">Jumlah</span><span class="text-center">Status</span>
                </div>
                @forelse ($orders as $po)
                    <div class="grid grid-cols-[1.2fr_1.1fr_0.6fr_1fr_1fr] items-center border-b border-divider px-5 py-[14px] last:border-b-0 md:px-6">
                        <a href="{{ route('vendors.show', ['vendor' => $v->id, 'tab' => 'po', 'po' => $po->id]) }}" wire:navigate class="text-[12.5px] font-bold text-primary dark:text-[#c9ce93]">{{ $po->po_no }}</a>
                        <span class="text-[13px] text-ink-2">{{ $po->service->label() }} {{ \Illuminate\Support\Str::before($po->animal_label, ' (') }}</span>
                        <span class="text-center text-[13px] text-ink-2">{{ $po->quantity }}</span>
                        <span class="text-right text-[13px] font-semibold text-ink tabular-nums">{{ $po->money($po->total_minor, $display, $usdRate) }}</span>
                        <span class="flex justify-center"><span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold whitespace-nowrap {{ $po->status->badgeClasses() }}">{{ $po->status->label() }}</span></span>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-[13px] text-faint">Belum ada Purchase Order.</div>
                @endforelse
            </div>
        </div>
    </section>

    @include('livewire.vendors.partials.vendor-form-modal')
</div>
