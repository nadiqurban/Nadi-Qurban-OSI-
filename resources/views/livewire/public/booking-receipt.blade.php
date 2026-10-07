@php [$bTitle, $bMsg, $bTone] = $banner; @endphp

<div class="min-h-screen bg-bg">
    <header class="bg-primary text-white print:hidden">
        <div class="mx-auto flex max-w-[880px] items-center gap-3 px-4 py-[18px] md:px-5">
            <div class="size-10 shrink-0 overflow-hidden rounded-[10px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover"></div>
            <div class="min-w-0 flex-1">
                <div class="text-[16px] font-extrabold tracking-[.3px]">NADI QURBAN</div>
                <div class="mt-0.5 text-[11px] text-[#d6d9bd]">Tempahan Ibadah Dalam Talian</div>
            </div>
            @if ($order->agent)
                <div class="min-w-0 text-right text-[11.5px] text-[#d6d9bd]">Ejen anda<div class="truncate text-[13px] font-bold text-white">{{ $order->agent->user->name }}</div></div>
            @endif
        </div>
    </header>

    <main class="mx-auto max-w-[880px] px-4 pt-[26px] pb-[60px] md:px-5 print:p-0">
        <div @class([
            'mb-4 flex items-center gap-3 rounded-[12px] border px-[18px] py-[14px] print:hidden',
            'border-[#BFE5CC] bg-success-soft' => $bTone === 'success',
            'border-[#F5D9A8] bg-warning-soft' => $bTone === 'warning',
            'border-[#F7CFCF] bg-danger-soft' => $bTone === 'danger',
        ]) role="status">
            <i @class(['ph-fill shrink-0 text-[26px]', 'ph-check-circle text-success' => $bTone === 'success', 'ph-clock-countdown text-warning' => $bTone === 'warning', 'ph-x-circle text-danger' => $bTone === 'danger'])></i>
            <div class="min-w-0 flex-1">
                <div @class(['text-[15px] font-extrabold', 'text-[#14532D]' => $bTone === 'success', 'text-[#92400E]' => $bTone === 'warning', 'text-danger' => $bTone === 'danger'])>{{ $bTitle }}</div>
                <div class="mt-0.5 text-[12.5px] leading-[1.5] text-ink-3">{{ $bMsg }}</div>
            </div>
        </div>

        @error('pay')<div class="mb-4 rounded-[9px] bg-danger-soft px-[14px] py-3 text-[13px] font-semibold text-danger print:hidden" role="alert">{{ $message }}</div>@enderror

        <div class="overflow-x-auto rounded-[14px] bg-[#E5E8E1] p-3 md:p-6 print:overflow-visible print:bg-transparent print:p-0">
            <x-ui.doc-a4 padding="20mm 18mm" id="pay-rcpt">
                @include('pdf.partials.booking-receipt-a4', ['order' => $order])
            </x-ui.doc-a4>
        </div>

        <div class="mt-[18px] flex flex-wrap justify-center gap-[10px] print:hidden">
            @if ($awaiting)
                <button type="button" wire:click="payAgain" wire:loading.attr="disabled" class="inline-flex min-h-11 items-center gap-[7px] rounded-[10px] bg-primary px-[18px] py-[11px] text-[13.5px] font-semibold text-white">
                    <i class="ph ph-lock-simple text-[16px]"></i> Cuba Bayar Semula
                </button>
            @endif
            <button type="button" x-on:click="window.print()" class="inline-flex min-h-11 items-center gap-[7px] rounded-[10px] border border-primary bg-surface px-[18px] py-[11px] text-[13.5px] font-semibold text-primary">
                <i class="ph ph-printer text-[16px]"></i> Cetak
            </button>
            <a href="{{ route('booking.receipt.pdf', $order->tracking_token) }}" class="inline-flex min-h-11 items-center gap-[7px] rounded-[10px] bg-gold px-[18px] py-[11px] text-[13.5px] font-semibold text-white hover:text-white">
                <i class="ph ph-download-simple text-[16px]"></i> Muat Turun PDF
            </a>
            <a href="{{ $againUrl }}" class="inline-flex min-h-11 items-center gap-[7px] rounded-[10px] bg-primary px-[18px] py-[11px] text-[13.5px] font-semibold text-white hover:text-white">
                <i class="ph ph-plus text-[16px]"></i> Buat Tempahan Lain
            </a>
        </div>
    </main>
</div>
