@php
    $paidCount = $plan->paidCount();
    $pct = $plan->progressPct();
    $next = $plan->nextDue();
    $card = 'w-full max-w-[520px] rounded-[14px] border border-border bg-surface';
@endphp

<div class="flex w-full flex-col items-center">
    {{-- Brand bar --}}
    <div class="mb-[18px] flex w-full max-w-[520px] items-center gap-[11px]">
        <div class="size-10 overflow-hidden rounded-[10px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover"></div>
        <div class="leading-none"><div class="text-[15px] font-extrabold tracking-[.3px] text-primary">NADI QURBAN</div><div class="mt-[3px] text-[10px] font-semibold tracking-[2px] text-faint">PORTAL BAYARAN ANSURAN</div></div>
        <span class="ml-auto inline-flex items-center gap-[5px] rounded-[20px] bg-success-soft px-[11px] py-[5px] text-[11.5px] font-semibold text-success"><i class="ph-fill ph-shield-check text-[14px]"></i> Selamat</span>
    </div>

    {{-- Summary --}}
    <div class="mb-4 w-full max-w-[520px] rounded-[16px] bg-primary px-6 py-[22px] text-white">
        <div class="text-[12px] tracking-[.3px] text-[#c9cbb4]">No. Tempahan</div>
        <div class="mt-0.5 text-[16px] font-bold">{{ $plan->order_no }}</div>
        <div class="mt-4 flex items-end justify-between gap-3">
            <div><div class="text-[12px] text-[#c9cbb4]">Baki Perlu Dibayar</div><div class="mt-[3px] text-[26px] leading-[1.1] font-extrabold sm:text-[30px]">{{ rm($plan->balanceSen()) }}</div></div>
            <div class="text-right"><div class="text-[12px] text-[#c9cbb4]">Jumlah Pelan</div><div class="mt-[3px] text-[15px] font-bold">{{ rm($plan->total_sen) }}</div></div>
        </div>
        <div class="mt-4 h-2 overflow-hidden rounded-[20px] bg-white/20"><div class="h-full bg-gold" style="width: {{ $pct }}%"></div></div>
        <div class="mt-[7px] flex justify-between text-[11.5px] text-[#c9cbb4]"><span>{{ rm($plan->paidSen() + $plan->deposit_sen) }} dibayar</span><span>{{ $pct }}% selesai</span></div>
    </div>

    {{-- Customer + package --}}
    <div class="{{ $card }} mb-4 grid grid-cols-2 gap-x-[18px] gap-y-[14px] px-5 py-[18px]">
        <div><div class="text-[11px] text-faint">Pelanggan</div><div class="mt-0.5 text-[13.5px] font-semibold text-ink">{{ $plan->customer->name }}</div></div>
        <div><div class="text-[11px] text-faint">Pakej</div><div class="mt-0.5 text-[13.5px] font-semibold text-ink">{{ $plan->package_name }}</div></div>
        <div><div class="text-[11px] text-faint">Ansuran Bulanan</div><div class="mt-0.5 text-[13.5px] font-semibold text-ink">{{ rm($plan->monthly_sen) }}</div></div>
        <div><div class="text-[11px] text-faint">Bayaran Seterusnya</div><div class="mt-0.5 text-[13.5px] font-semibold text-warning">{{ $next ? tarikh($next->due_date) : '—' }}</div></div>
    </div>

    {{-- Schedule --}}
    <div class="{{ $card }} mb-4 overflow-hidden">
        <div class="border-b border-divider px-5 py-[14px] text-[13.5px] font-bold text-ink">Jadual Ansuran</div>
        @foreach ($plan->installments as $i)
            @php $done = $i->isPaid(); $on = ! $done && ($selected[$i->seq] ?? false); @endphp
            <div class="flex items-center gap-[13px] border-b border-divider px-5 py-[13px] last:border-b-0" wire:key="inst-{{ $i->id }}">
                @if ($done || ! $open)
                    <span class="w-5 shrink-0"></span>
                @else
                    <button type="button" wire:click="toggle({{ $i->seq }})" role="checkbox" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="Pilih ansuran ke-{{ $i->seq }}"
                            class="-m-3 flex shrink-0 items-center justify-center p-3">
                        <span @class(['flex size-5 items-center justify-center rounded-[6px]', 'border border-primary bg-primary text-white' => $on, 'border-[1.5px] border-checkbox-border text-transparent' => ! $on])><i class="ph-fill ph-check text-[12px]"></i></span>
                    </button>
                @endif
                <span @class(['flex size-[34px] shrink-0 items-center justify-center rounded-[9px]', 'bg-success-soft text-success' => $done, 'bg-warning-soft text-warning' => ! $done])><i class="{{ $done ? 'ph-fill ph-check-circle' : 'ph ph-clock' }} text-[17px]"></i></span>
                <div class="min-w-0 flex-1"><div class="text-[13px] font-semibold text-ink">Ansuran ke-{{ $i->seq }}</div><div class="text-[11.5px] text-faint">{{ tarikh($i->due_date) }}</div></div>
                <span class="text-[13px] font-bold whitespace-nowrap text-ink">{{ rm($i->amount_sen) }}</span>
                <span @class(['rounded-[20px] px-[10px] py-[3px] text-[10.5px] font-bold whitespace-nowrap', 'bg-success-soft text-success' => $done, 'bg-warning-soft text-warning' => ! $done])>{{ $i->status->label() }}</span>
            </div>
        @endforeach
    </div>

    @if ($open)
        {{-- Method --}}
        <div class="{{ $card }} mb-4 px-5 py-[18px]">
            <div class="mb-3 flex items-center justify-between gap-2"><span class="text-[13.5px] font-bold text-ink">Pilih Kaedah Bayaran</span><span class="inline-flex items-center gap-[5px] rounded-[20px] bg-success-soft px-[9px] py-[3px] text-[10.5px] font-semibold whitespace-nowrap text-success"><i class="ph ph-shield-check text-[12px]"></i> Dikuasakan CHIP IN</span></div>
            <div class="flex flex-col gap-[9px]" role="radiogroup" aria-label="Kaedah bayaran">
                @foreach (\App\Enums\PortalPayMethod::cases() as $pm)
                    @php $sel = $method === $pm->value; @endphp
                    <button type="button" wire:click="$set('method', '{{ $pm->value }}')" role="radio" aria-checked="{{ $sel ? 'true' : 'false' }}"
                            @class(['flex min-h-11 items-center gap-3 rounded-[10px] border-[1.5px] px-[13px] py-[11px] text-left', 'border-primary bg-bg' => $sel, 'border-border' => ! $sel])>
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-[9px] {{ $pm->tileClasses() }}"><i class="ph ph-{{ $pm->icon() }} text-[19px]"></i></span>
                        <span class="flex-1 text-[13.5px] font-semibold text-ink">{{ $pm->label() }}</span>
                        <span @class(['flex size-5 shrink-0 items-center justify-center rounded-full', 'bg-primary' => $sel, 'border-[1.5px] border-checkbox-border' => ! $sel])>@if ($sel)<i class="ph-fill ph-check text-[11px] text-white"></i>@endif</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- CTA (sticky on phones) --}}
        <div class="sticky bottom-0 w-full max-w-[520px] bg-[#EEF1EC] pt-2 pb-[max(12px,env(safe-area-inset-bottom))] sm:static sm:bg-transparent sm:p-0">
            <button type="button" wire:click="pay" wire:loading.attr="disabled" wire:target="pay" id="portal-pay"
                    class="flex w-full items-center justify-center gap-[9px] rounded-[11px] bg-primary p-[15px] text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70">
                <i class="ph ph-lock-simple-open text-[19px]" wire:loading.remove wire:target="pay"></i>
                <i class="ph ph-circle-notch animate-spin text-[19px]" wire:loading wire:target="pay"></i>
                <span wire:loading.remove wire:target="pay">Bayar {{ rm($payAmount) }} Sekarang</span>
                <span wire:loading wire:target="pay">Mengalihkan ke CHIP IN…</span>
            </button>
        </div>
    @else
        <div class="{{ $card }} mb-4 flex items-center gap-3 px-5 py-4 text-[13px] text-ink-2">
            <i class="ph-fill {{ $plan->status === \App\Enums\InstallmentPlanStatus::Cancelled ? 'ph-x-circle text-danger' : 'ph-check-circle text-success' }} text-[20px]"></i>
            {{ $plan->status === \App\Enums\InstallmentPlanStatus::Cancelled ? 'Pelan ansuran ini telah dibatalkan. Sila hubungi Nadi Qurban.' : 'Alhamdulillah, semua ansuran telah dijelaskan. Terima kasih!' }}
        </div>
    @endif

    <div class="w-full max-w-[520px]">
        <p class="mt-3 text-center text-[11.5px] leading-[1.6] text-faint"><i class="ph-fill ph-shield-check align-[-2px] text-[13px] text-success"></i> Pembayaran dilindungi &amp; disulitkan. Dikuasakan oleh gerbang pembayaran FPX &middot; DuitNow.</p>
        <div class="mt-2 text-center text-[11px] text-faint">nadiqurban.com &middot; {{ app(\App\Support\Settings::class)->get('company.name', 'Nadi Qurban Sdn. Bhd.') }} ({{ app(\App\Support\Settings::class)->get('company.ssm', '1677511-A') }})</div>
    </div>

    {{-- Result / receipt --}}
    @if ($payState)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-[rgba(20,24,20,.6)] px-4 py-6" role="dialog" aria-modal="true">
            <div class="max-h-full w-full max-w-[400px] overflow-y-auto rounded-[16px] bg-surface shadow-modal">
                @if ($payState === 'done' && $tx && ! $showReceipt)
                    <div class="px-7 py-9 text-center">
                        <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-success-soft"><i class="ph-fill ph-check-circle text-[38px] text-success"></i></div>
                        <div class="mt-4 text-[18px] font-extrabold text-ink">Bayaran Berjaya!</div>
                        <p class="mt-1.5 text-[12.5px] text-faint">Disahkan oleh CHIP IN</p>
                        <p class="mt-2 text-[13px] leading-[1.6] text-muted">Ansuran <b>{{ rm($tx->amount_sen) }}</b> anda telah diterima melalui {{ $tx->method?->shortLabel() }}. Resit dihantar ke emel anda.</p>
                        <div class="mt-4 rounded-[10px] bg-bg px-[14px] py-3 text-left text-[12.5px] text-ink-2">
                            <div class="flex justify-between"><span class="text-faint">No. Rujukan</span><span class="font-mono font-bold">{{ $tx->reference }}</span></div>
                            <div class="mt-1.5 flex justify-between"><span class="text-faint">Baki Baharu</span><span class="font-bold">{{ rm($plan->balanceSen()) }}</span></div>
                        </div>
                        <x-ui.button icon="receipt" class="mt-[18px] w-full !py-[13px] !text-[14px] !font-bold" wire:click="$set('showReceipt', true)">Lihat Resit</x-ui.button>
                        <x-ui.button variant="secondary" class="mt-[10px] w-full" wire:click="closePay">Selesai</x-ui.button>
                    </div>
                @elseif ($payState === 'done' && $tx)
                    <div class="flex items-center justify-between gap-2 border-b border-border bg-bg px-[22px] py-[14px]">
                        <span class="text-[13px] font-bold text-ink-2">Resit Bayaran</span>
                        <div class="flex gap-2">
                            <x-ui.button size="sm" variant="gold" icon="download-simple" :href="route('installments.portal.receipt', ['token' => $plan->pay_token, 'reference' => $tx->reference, 'muat-turun' => 1])">PDF</x-ui.button>
                            <button type="button" wire:click="closePay" class="flex size-9 items-center justify-center rounded-[8px] border border-border text-muted max-md:size-11" aria-label="Tutup"><i class="ph ph-x text-[14px]"></i></button>
                        </div>
                    </div>
                    <div class="px-[30px] py-7 max-sm:px-5">
                        @include('pdf.partials.installment-receipt-a5', ['plan' => $plan, 'tx' => $tx])
                    </div>
                @else
                    <div class="px-7 py-9 text-center">
                        <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-danger-soft"><i class="ph-fill ph-x-circle text-[38px] text-danger"></i></div>
                        <div class="mt-4 text-[18px] font-extrabold text-ink">Bayaran Tidak Berjaya</div>
                        <p class="mt-2 text-[13px] leading-[1.6] text-muted">Bayaran anda tidak diterima atau dibatalkan. Ansuran kekal <b>Perlu Bayar</b> — sila cuba semula.</p>
                        <x-ui.button class="mt-[18px] w-full" wire:click="closePay">Cuba Semula</x-ui.button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
