@php
    $agent = $this->agent;
    $link = $agent->shareUrl();
    $season = app(\App\Support\Settings::class)->get('season.year', now()->year);
    $waText = 'Assalamualaikum, tempah ibadah Qurban & Aqiqah bersama Nadi Qurban melalui link ini: '.$link;
@endphp

<div class="min-h-screen bg-bg">
    <header class="border-b border-border bg-surface">
        <div class="mx-auto flex max-w-[1120px] items-center gap-3 px-4 py-[14px] md:px-6">
            <div class="size-[38px] shrink-0 overflow-hidden rounded-[10px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover"></div>
            <div class="min-w-0 flex-1">
                <div class="text-[15px] font-extrabold text-primary dark:text-[#c9ce93]">NADI QURBAN</div>
                <div class="text-[10.5px] font-semibold tracking-[1.5px] text-faint">PORTAL EJEN SALES</div>
            </div>
            <div class="min-w-0 text-right">
                <div class="truncate text-[13px] font-bold text-ink">{{ $agent->user->name }}</div>
                <div class="text-[11px] text-muted">Kod Ejen: {{ $agent->code }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log keluar" aria-label="Log keluar" class="flex size-11 items-center justify-center rounded-[10px] border border-border bg-surface text-muted md:size-[38px]"><i class="ph ph-sign-out text-[18px]"></i></button>
            </form>
        </div>
    </header>

    <main class="mx-auto max-w-[1120px] px-4 pt-[26px] pb-14 md:px-6">
        <h1 class="text-[20px] font-bold text-ink md:text-[22px]">Assalamualaikum, {{ \Illuminate\Support\Str::before($agent->user->name.' ', ' ') }}</h1>
        <p class="mt-1 text-[13.5px] text-muted">Prestasi link jualan anda bagi musim {{ $season }}.</p>

        <div class="mt-4 flex flex-wrap items-center gap-[10px]">
            <span class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-faint"><i class="ph ph-funnel text-[15px]"></i> Tapis:</span>
            <div class="flex gap-[2px] rounded-[9px] border border-border bg-surface p-[3px]" role="tablist" aria-label="Tempoh">
                @foreach (['all' => 'Semua', 'day' => 'Hari', 'month' => 'Bulan', 'year' => 'Tahun'] as $key => $label)
                    <button type="button" role="tab" wire:click="setPeriod('{{ $key }}')" aria-selected="{{ $period === $key ? 'true' : 'false' }}"
                            @class(['rounded-[7px] px-3 py-1.5 text-[12.5px] font-semibold max-md:min-h-10', 'bg-primary text-white' => $period === $key, 'text-ink-3' => $period !== $key])>{{ $label }}</button>
                @endforeach
            </div>
            <label class="flex items-center gap-1.5 rounded-[9px] border border-border bg-surface px-[10px] py-1.5">
                <i class="ph ph-calendar-blank text-[15px] text-primary"></i>
                <input type="date" wire:model.live="refDate" aria-label="Tarikh" class="border-0 bg-transparent text-[12.5px] text-ink outline-none max-md:text-[16px]">
            </label>
            <span class="text-[12px] text-muted">{{ $p->label('Semua tempahan') }} &middot; {{ $this->orders->count() }} tempahan</span>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 md:gap-[14px] lg:grid-cols-[repeat(auto-fit,minmax(200px,1fr))]">
            @foreach ($stats as $i => $s)
                <div @class(['flex items-center gap-[14px] rounded-[12px] border border-border bg-surface px-4 py-4 md:px-[18px]', 'max-sm:col-span-2' => $i >= 2, 'sm:max-lg:col-span-2' => $i === 4])>
                    <div class="flex size-[42px] shrink-0 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes($s['tone']) }}"><i class="ph ph-{{ $s['icon'] }} text-[22px]"></i></div>
                    <div class="min-w-0">
                        <div class="text-[18px] leading-[1.1] font-extrabold whitespace-nowrap text-ink lg:text-[clamp(16px,1.45vw,21px)]">{{ $s['value'] }}</div>
                        <div class="mt-1 text-[12.5px] text-muted">{{ $s['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Link Jualan Anda --}}
        <section class="mt-[18px] rounded-[12px] border border-border bg-surface px-4 py-5 md:px-[22px]">
            <h2 class="text-[15.5px] font-bold text-ink">Link Jualan Anda</h2>
            <p class="mt-[3px] text-[12.5px] text-muted">Kongsi link ini — setiap tempahan &amp; bayaran melaluinya dikreditkan kepada anda.</p>
            <div class="mt-[14px] flex flex-wrap gap-2" x-data="{ copied: false }">
                <input value="{{ $link }}" readonly aria-label="Link jualan" x-on:focus="$el.select()"
                       class="min-w-0 flex-1 basis-full rounded-[9px] border border-border bg-bg px-[13px] py-[11px] font-mono text-[12.5px] text-ink outline-none sm:basis-[240px]">
                <button type="button" x-on:click="navigator.clipboard?.writeText(@js($link)); copied = true; setTimeout(() => copied = false, 1600)"
                        class="inline-flex h-[42px] flex-1 items-center justify-center gap-[7px] rounded-[9px] bg-primary px-4 text-[13px] font-semibold text-white sm:flex-none max-md:h-11">
                    <i class="ph text-[16px]" :class="copied ? 'ph-check' : 'ph-copy'"></i> <span x-text="copied ? 'Disalin' : 'Salin'">Salin</span>
                </button>
                <a href="https://wa.me/?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener"
                   class="inline-flex h-[42px] flex-1 items-center justify-center gap-[7px] rounded-[9px] bg-success px-4 text-[13px] font-semibold text-white hover:text-white sm:flex-none max-md:h-11">
                    <i class="ph ph-whatsapp-logo text-[16px]"></i> WhatsApp
                </a>
                <button type="button" x-on:click="$dispatch('open-modal', 'link-preview')"
                        class="inline-flex h-[42px] flex-1 items-center justify-center gap-[7px] rounded-[9px] border border-primary bg-surface px-4 text-[13px] font-semibold text-primary sm:flex-none max-md:h-11">
                    <i class="ph ph-eye text-[16px]"></i> Pratonton
                </button>
            </div>
        </section>

        {{-- Tempahan Melalui Link --}}
        <section class="mt-[18px] overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="flex flex-wrap items-center justify-between gap-[10px] border-b border-divider px-4 py-4 md:px-[22px]">
                <h2 class="text-[15.5px] font-bold text-ink">Tempahan Melalui Link</h2>
                <div class="-mx-1 flex max-w-full gap-2 overflow-x-auto px-1">
                    @foreach (\App\Livewire\Agent\Portal::TABS as $key => $label)
                        <button type="button" wire:click="$set('tab', '{{ $key }}')" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}"
                                @class(['inline-flex shrink-0 items-center gap-[7px] rounded-[9px] px-[13px] py-2 text-[12.5px] font-semibold whitespace-nowrap max-md:min-h-10', 'bg-primary text-white' => $tab === $key, 'border border-border bg-surface text-ink-3' => $tab !== $key])>
                            {{ $label }}
                            <span @class(['rounded-[20px] px-[7px] py-[1px] text-[11px] font-bold', 'bg-white/20 text-white' => $tab === $key, 'bg-divider text-muted' => $tab !== $key])>{{ $counts[$key] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="hidden grid-cols-[1.25fr_1.25fr_1.4fr_0.55fr_0.95fr_0.95fr_1fr_0.8fr] gap-3 bg-bg px-[22px] py-3 text-[11px] font-bold tracking-[.4px] text-faint uppercase lg:grid">
                <span>No. Tempahan</span><span>Pelanggan</span><span>Produk</span><span class="text-center">Kuantiti</span><span class="text-right">Jualan</span><span class="text-right">Komisen</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
            </div>
            @forelse ($list as $o)
                @php [$stLabel, $stTone] = \App\Livewire\Agent\Portal::statusOf($o); @endphp
                <div wire:key="ag-order-{{ $o->id }}" class="grid grid-cols-2 gap-x-3 gap-y-2 border-t border-divider px-4 py-[13px] text-[13px] lg:grid-cols-[1.25fr_1.25fr_1.4fr_0.55fr_0.95fr_0.95fr_1fr_0.8fr] lg:items-center lg:gap-3 lg:px-[22px]">
                    <span class="font-bold text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</span>
                    <span class="text-right lg:hidden"><span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold whitespace-nowrap {{ \App\Support\Tone::classes($stTone) }}">{{ $stLabel }}</span></span>
                    <span class="col-span-2 min-w-0 lg:col-span-1">
                        <span class="block truncate font-semibold text-ink">{{ $o->customer->name }}</span>
                        <span class="text-[11.5px] text-faint">{{ tarikh($o->created_at) }}</span>
                    </span>
                    <span class="col-span-2 text-ink-3 lg:col-span-1">{{ $o->product_name }} — {{ $o->package_name }}</span>
                    <span class="font-semibold lg:text-center"><span class="text-faint lg:hidden">Kuantiti: </span>{{ $o->quantity }}</span>
                    <span class="text-right font-bold text-ink lg:text-right"><span class="font-normal text-faint lg:hidden">Jualan: </span>{{ rm($o->total_sen, true) }}</span>
                    <span class="font-bold text-success lg:text-right"><span class="font-normal text-faint lg:hidden">Komisen: </span>{{ rm($o->status === \App\Enums\OrderStatus::Cancelled ? 0 : $o->commission_sen, true) }}</span>
                    <span class="hidden text-center lg:block"><span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold whitespace-nowrap {{ \App\Support\Tone::classes($stTone) }}">{{ $stLabel }}</span></span>
                    <span class="flex justify-end">
                        <button type="button" wire:click="openProof({{ $o->id }})" aria-label="Bukti bayaran {{ $o->order_no }}"
                                @class(['inline-flex min-h-10 items-center gap-[5px] rounded-[8px] px-[11px] py-[7px] text-[12px] font-semibold lg:min-h-0',
                                    'border border-[#F5D9A8] bg-warning-soft text-warning' => $stLabel === 'Menunggu Pengesahan',
                                    'border border-border bg-surface text-primary' => $stLabel !== 'Menunggu Pengesahan'])>
                            <i class="ph ph-receipt text-[14px]"></i> Bukti
                        </button>
                    </span>
                </div>
            @empty
                <div class="border-t border-divider px-[22px] py-9 text-center text-[13.5px] text-faint">Belum ada tempahan melalui link anda.</div>
            @endforelse
        </section>
    </main>

    {{-- Bukti Bayaran --}}
    <x-ui.modal wire:model="showProof" title="Bukti Bayaran" :subtitle="$proofOrder ? $proofOrder->order_no.' · '.$proofOrder->customer->name.' · '.$proofOrder->payment_method->label() : null" icon="receipt" max-width="640px" body-class="flex flex-col items-center gap-[10px] bg-bg px-4 py-[18px] md:px-[22px]">
        @if ($proofOrder)
            @php $proofUrl = $proofOrder->payment?->proof() ? route('agent.proof', $proofOrder->payment) : null; @endphp
            @if ($proofUrl && str_starts_with((string) $proofOrder->payment->proof()?->mime_type, 'image/'))
                <img src="{{ $proofUrl }}" alt="Bukti bayaran" class="block max-h-[62vh] max-w-full rounded-[10px] shadow-[0_4px_16px_rgba(0,0,0,.12)]">
            @elseif ($proofUrl)
                <iframe src="{{ $proofUrl }}" title="Bukti bayaran" class="h-[62vh] w-full rounded-[10px] border-0 bg-white"></iframe>
            @else
                <div class="px-5 py-10 text-center text-[13px] text-faint"><i class="ph ph-file-dashed mb-2 block text-[34px]"></i>Tiada bukti dimuat naik — bayaran dibuat melalui {{ $proofOrder->payment_method->label() }} dan disahkan secara automatik.</div>
            @endif
            <div class="text-[12px] text-muted">Jumlah <b class="text-ink">{{ rm($proofOrder->total_sen, true) }}</b></div>
        @endif
        <x-slot:footer>
            @if ($proofOrder)
                @php [$stLabel, $stTone] = \App\Livewire\Agent\Portal::statusOf($proofOrder); @endphp
                <span class="mr-auto rounded-[20px] px-3 py-[5px] text-[11.5px] font-bold {{ \App\Support\Tone::classes($stTone) }}">{{ $stLabel }}</span>
            @endif
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Pratonton Link Jualan --}}
    <x-ui.modal name="link-preview" title="Pratonton Link Jualan" :subtitle="$link" icon="eye" max-width="980px" body-class="p-0">
        <iframe src="{{ $link }}?pratonton=1" title="Pratonton Tempahan Awam" loading="lazy" class="block h-[70vh] w-full border-0"></iframe>
    </x-ui.modal>
</div>
