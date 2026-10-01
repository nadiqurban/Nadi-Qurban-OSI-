@php
    $card = 'rounded-[12px] border border-border bg-surface shadow-[0_1px_2px_rgba(16,24,40,.04)]';
    $isOpen = fn (string $k) => ! in_array($k, $collapsed, true);
    $gauge = $data['gauge'];
    $rankStyles = ['bg-primary text-gold', 'bg-primary-soft text-primary dark:text-[#c9ce93]', 'bg-primary-soft text-primary dark:text-[#c9ce93]', 'bg-divider text-muted', 'bg-divider text-muted'];
@endphp

<div>
    {{-- Header --}}
    <div class="mb-5 flex flex-wrap items-end gap-3 md:mb-[26px] md:gap-4">
        <div class="mr-auto min-w-0 max-md:w-full">
            <h1 class="text-[20px] font-bold text-ink md:text-[23px]">Dashboard</h1>
            <p class="mt-1 text-[13.5px] text-muted">Assalamualaikum {{ $firstName }} &mdash; ringkasan operasi bagi musim Qurban {{ $season }}.</p>
        </div>
        <label class="inline-flex items-center gap-2 rounded-[9px] border border-border bg-surface px-[13px] py-2 max-md:min-h-11 max-md:w-full">
            <i class="ph ph-calendar-blank text-[16px] text-primary dark:text-[#c9ce93]"></i>
            <input type="date" id="dash-date" x-data x-init="if (! $el.value) $el.value = @js($untilValue)" wire:model.live="date" aria-label="Tarikh" class="bg-transparent text-[13px] font-semibold text-ink-2 outline-none max-md:flex-1 max-md:text-[16px]">
        </label>
        <x-ui.button variant="success" icon="microsoft-excel-logo" id="dash-export" wire:click="export" class="!px-[14px] !py-[9px] [&>i]:!text-[16px] max-md:flex-1">Eksport Excel</x-ui.button>
        <x-ui.button icon="arrows-clockwise" id="dash-refresh" wire:click="refreshData" wire:loading.attr="disabled" class="!py-[10px] [&>i]:!text-[16px] max-md:flex-1">Muat Semula</x-ui.button>
    </div>

    {{-- 9 KPI --}}
    <div class="mb-6 grid grid-cols-2 gap-3 md:gap-[18px] lg:grid-cols-4">
        @foreach ($data['kpis'] as $k)
            <x-ui.kpi-card :icon="$k['icon']" :tone="$k['tone']" :value="$k['value']" :label="$k['label']" :delta="$k['delta']" :trend="$k['trend']" />
        @endforeach
    </div>

    {{-- Jualan Bulanan + Pencapaian --}}
    <div class="mb-6 grid grid-cols-1 gap-[18px] lg:grid-cols-[minmax(0,1.9fr)_minmax(0,1fr)]">
        <section class="{{ $card }} min-w-0 px-4 py-5 md:px-6 md:py-[22px]">
            <div class="mb-2 flex flex-wrap items-start justify-between gap-[10px]">
                <div class="flex items-start gap-[10px]">
                    @include('livewire.partials.dashboard-check', ['key' => 'jualan', 'label' => 'Jualan Bulanan'])
                    <div>
                        <h2 class="text-[15.5px] font-bold text-ink">Jualan Bulanan</h2>
                        <p class="mt-[3px] text-[12.5px] text-muted">Nilai tempahan disahkan (RM '000) mengikut bulan</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-[12.5px] text-muted">
                    <span class="flex items-center gap-1.5"><span class="size-[10px] rounded-[3px] bg-primary"></span>Jualan</span>
                    <span class="flex items-center gap-1.5"><span class="size-[10px] rounded-[3px] bg-gold"></span>Sasaran</span>
                </div>
            </div>
            @if ($isOpen('jualan'))
                <x-chart.sales-line :rows="$data['monthly']['rows']" :target="$data['monthly']['target']" />
            @endif
        </section>

        <section class="{{ $card }} flex min-w-0 flex-col px-4 py-5 md:px-6 md:py-[22px]">
            <div class="flex items-start gap-[10px]">
                @include('livewire.partials.dashboard-check', ['key' => 'sasaran', 'label' => 'Pencapaian Jualan'])
                <div>
                    <h2 class="text-[15.5px] font-bold text-ink">Pencapaian Jualan</h2>
                    <p class="mt-[3px] text-[12.5px] text-muted">Sasaran musim RM {{ number_format($gauge['target'] / 100 / 1_000_000, 2) }} juta</p>
                </div>
            </div>
            @if ($isOpen('sasaran'))
                <div class="relative my-1.5 flex flex-1 items-center justify-center py-2">
                    <x-chart.gauge :pct="$gauge['pct']" />
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-[32px] leading-none font-extrabold text-primary dark:text-[#c9ce93]">{{ $gauge['pct'] }}%</div>
                        <div class="mt-1 text-[12px] text-muted">tercapai</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-[10px] border-t border-border pt-[14px] text-center">
                    <div><div class="text-[16px] font-bold text-ink">{{ rm_short($gauge['achieved']) }}</div><div class="mt-0.5 text-[11.5px] text-muted">Dicapai</div></div>
                    <div><div class="text-[16px] font-bold text-gold">{{ rm_short($gauge['balance']) }}</div><div class="mt-0.5 text-[11.5px] text-muted">Baki</div></div>
                </div>
            @endif
        </section>
    </div>

    {{-- Agihan Negara + Ranking Vendor --}}
    <div class="mb-6 grid grid-cols-1 gap-[18px] lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]">
        <section class="{{ $card }} min-w-0 px-4 py-5 md:px-6 md:py-[22px]">
            <div class="mb-1 flex items-start gap-[10px]">
                @include('livewire.partials.dashboard-check', ['key' => 'negara', 'label' => 'Agihan Negara'])
                <h2 class="text-[15.5px] font-bold text-ink">Agihan Negara</h2>
            </div>
            @if ($isOpen('negara'))
                <p class="mb-4 text-[12.5px] text-muted">Tempahan mengikut negara pelaksanaan</p>
                @php $points = array_map(fn ($c) => ['name' => $c['name'], 'value' => $c['pct'], 'color' => $c['color'], 'lon' => $c['lon'], 'lat' => $c['lat']], $data['countries']); @endphp
                <div class="flex flex-col gap-4">
                    <div wire:ignore wire:key="map-{{ md5(json_encode($points)) }}" x-data="countryMap(@js($points))" id="nq-country-map" class="min-h-[180px] w-full md:min-h-[200px]"></div>
                    <div class="grid grid-cols-2 gap-x-[22px] gap-y-[9px]">
                        @forelse ($data['countries'] as $c)
                            <div class="flex items-center gap-[9px] text-[12.5px]">
                                <span class="size-[9px] shrink-0 rounded-full" style="background: {{ $c['color'] }}"></span>
                                <span class="flex-1 truncate text-ink-2">{{ $c['name'] }}</span>
                                <span class="font-bold text-ink">{{ $c['pct'] }}%</span>
                            </div>
                        @empty
                            <p class="col-span-2 text-[12.5px] text-faint">Belum ada tempahan musim ini.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>

        <section class="{{ $card }} min-w-0 px-4 py-5 md:px-6 md:py-[22px]">
            <h2 class="mb-1 text-[15.5px] font-bold text-ink">Ranking Vendor</h2>
            <p class="mb-[18px] text-[12.5px] text-muted">Kedudukan mengikut kadar penyiapan pesanan</p>
            <div class="flex flex-col">
                @forelse ($data['vendors'] as $i => $v)
                    <div class="flex items-center gap-[13px] border-b border-divider py-[11px] last:border-b-0">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-[8px] text-[13px] font-extrabold {{ $rankStyles[$i] ?? $rankStyles[4] }}">{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[13.5px] font-semibold text-ink-2">{{ $v['name'] }}</div>
                            <div class="mt-0.5 flex items-center gap-[7px]"><span class="text-[11.5px] text-faint">{{ $v['country'] }}</span><span class="rounded-[20px] px-2 py-0.5 text-[10px] font-bold {{ $v['levelClasses'] }}">{{ $v['level'] }}</span></div>
                        </div>
                        <span class="text-[15px] font-extrabold text-ink">{{ $v['pct'] }}</span>
                    </div>
                @empty
                    <x-ui.empty-state icon="truck" title="Belum ada vendor aktif." />
                @endforelse
            </div>
        </section>
    </div>

    {{-- Prestasi Negara + Aktiviti Terkini --}}
    <div class="mb-6 grid grid-cols-1 gap-[18px] lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
        <section class="{{ $card }} min-w-0 overflow-hidden">
            <div class="flex items-center gap-[10px] px-4 pt-5 pb-[14px] md:px-6">
                @include('livewire.partials.dashboard-check', ['key' => 'prestasi', 'label' => 'Prestasi Negara'])
                <h2 class="text-[15.5px] font-bold text-ink">Prestasi Negara</h2>
            </div>
            @if ($isOpen('prestasi'))
                <div class="grid grid-cols-[1.4fr_1fr_1.2fr_1fr] border-b border-border px-4 pb-[10px] text-[11px] font-bold tracking-[.4px] text-faint uppercase md:px-6 md:text-[11.5px]">
                    <span>Negara</span><span class="text-right">Tempahan</span><span class="text-right">Jualan</span><span class="text-right">Bahagian</span>
                </div>
                @forelse ($data['countries'] as $c)
                    <div class="grid grid-cols-[1.4fr_1fr_1.2fr_1fr] items-center border-b border-divider px-4 py-[14px] last:border-b-0 md:px-6">
                        <span class="truncate text-[13px] font-semibold text-ink md:text-[13.5px]">{{ $c['name'] }}</span>
                        <span class="text-right text-[13px] text-ink-2 md:text-[13.5px]">{{ number_format($c['orders']) }}</span>
                        <span class="text-right text-[13px] font-semibold text-primary md:text-[13.5px] dark:text-[#c9ce93]">{{ rm_short($c['sales']) }}</span>
                        <span class="text-right text-[13px] text-ink-2 md:text-[13.5px]">{{ $c['pct'] }}%</span>
                    </div>
                @empty
                    <x-ui.empty-state icon="globe-hemisphere-west" title="Belum ada data negara." />
                @endforelse
            @endif
        </section>

        <section class="{{ $card }} min-w-0 px-4 py-5 md:px-6">
            <h2 class="mb-[18px] text-[15.5px] font-bold text-ink">Aktiviti Terkini</h2>
            <div class="flex flex-col">
                @forelse ($activities as $a)
                    @php
                        [$icon, $tone] = \App\Support\AuditAction::style($a->event);
                        $who = $a->causer instanceof \App\Models\User ? $a->causer->name.' ('.$a->causer->role_label.')' : 'Sistem';
                    @endphp
                    <div class="flex gap-[14px]">
                        <div class="flex flex-col items-center">
                            <span class="flex size-[30px] shrink-0 items-center justify-center rounded-[8px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[17px]"></i></span>
                            <span @class(['my-1 w-0.5 flex-1', 'bg-border' => ! $loop->last, 'bg-transparent' => $loop->last])></span>
                        </div>
                        <div class="min-w-0 pb-4">
                            <div class="text-[13px] font-semibold text-ink">{{ $a->description }}</div>
                            <div class="mt-0.5 text-[12px] text-muted">{{ $who }} &middot; {{ masa_lalu($a->created_at) }}</div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="clock-counter-clockwise" title="Belum ada aktiviti." />
                @endforelse
            </div>
        </section>
    </div>

    {{-- Pakej + Haiwan --}}
    <div class="mb-6 grid grid-cols-1 gap-[18px] md:grid-cols-2">
        <section class="{{ $card }} min-w-0 px-4 py-5 md:px-6 md:py-[22px]">
            <h2 class="mb-1 flex items-center gap-[10px] text-[15.5px] font-bold text-ink">@include('livewire.partials.dashboard-check', ['key' => 'pakej', 'label' => 'Jumlah Mengikut Pakej'])Jumlah Mengikut Pakej</h2>
            @if ($isOpen('pakej'))
                <p class="mb-4 text-[12.5px] text-muted">Bilangan tempahan setiap jenis pakej</p>
                <div class="flex flex-col gap-[13px]">
                    @forelse ($data['packages'] as $p)
                        <div>
                            <div class="mb-1.5 flex items-baseline justify-between"><span class="text-[13px] font-semibold text-ink-2">{{ $p['name'] }}</span><span class="text-[13px] font-bold text-ink">{{ number_format($p['count']) }}</span></div>
                            <div class="h-2 overflow-hidden rounded-[20px] bg-[#eef0e4] dark:bg-[#2c2e1c]"><div class="h-full rounded-[20px]" style="width: {{ $p['width'] }}%; background: {{ $p['color'] }}"></div></div>
                        </div>
                    @empty
                        <p class="text-[12.5px] text-faint">Belum ada tempahan.</p>
                    @endforelse
                </div>
            @endif
        </section>
        <section class="{{ $card }} min-w-0 px-4 py-5 md:px-6 md:py-[22px]">
            <h2 class="mb-1 flex items-center gap-[10px] text-[15.5px] font-bold text-ink">@include('livewire.partials.dashboard-check', ['key' => 'haiwan', 'label' => 'Jumlah Mengikut Haiwan'])Jumlah Mengikut Haiwan</h2>
            <p class="mb-4 text-[12.5px] text-muted">Bilangan ibadah setiap jenis haiwan</p>
            @if ($isOpen('haiwan'))
                <div class="grid grid-cols-2 gap-3">
                    @foreach ($data['animals'] as $an)
                        <div class="flex items-center gap-3 rounded-[11px] bg-primary-soft px-[14px] py-3 dark:bg-[#26281a]">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-[10px] bg-primary-soft text-primary dark:text-[#c9ce93]"><i class="ph ph-{{ $an['icon'] }} text-[22px]"></i></span>
                            <div><div class="text-[20px] leading-none font-extrabold text-ink">{{ number_format($an['count']) }}</div><div class="mt-[3px] text-[12px] text-muted">{{ $an['name'] }}</div></div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    {{-- Tempahan Terkini --}}
    <section class="{{ $card }} overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-4 pt-5 pb-[14px] md:px-6">
            <div class="flex items-center gap-[10px]">
                @include('livewire.partials.dashboard-check', ['key' => 'terkini', 'label' => 'Tempahan Terkini'])
                <div>
                    <h2 class="text-[15.5px] font-bold text-ink">Tempahan Terkini</h2>
                    <p class="mt-[3px] text-[12.5px] text-muted">8 tempahan terbaru diterima</p>
                </div>
            </div>
            @can('orders.view')
                <a href="{{ route('orders.index') }}" wire:navigate class="shrink-0 text-[13px] font-semibold text-primary max-md:min-h-11 max-md:content-center dark:text-[#c9ce93]">Lihat semua &rarr;</a>
            @endcan
        </div>
        @if ($isOpen('terkini'))
            <x-ui.data-table cols="1.3fr 1.5fr 1.4fr 0.9fr 1.1fr 0.7fr 1fr 1.1fr" min-width="960px" class="!rounded-none !border-x-0 !border-b-0">
                <x-slot:head>
                    <span>No. Tempahan</span><span>Pelanggan</span><span>Servis</span><span>Pakej</span><span>Negara</span><span class="text-center">Kuantiti</span><span class="text-right">Jumlah</span><span class="text-center">Status</span>
                </x-slot:head>
                @forelse ($orders as $o)
                    <x-ui.tr wire:key="dash-o-{{ $o->id }}" class="md:!px-6">
                        <x-ui.td span class="flex items-center justify-between gap-2">
                            <a href="{{ route('orders.show', $o) }}" wire:navigate class="truncate font-semibold text-primary tabular-nums dark:text-[#c9ce93]">{{ $o->order_no }}</a>
                            <span class="md:hidden"><x-ui.badge :tone="$o->status->tone()">{{ $o->status->label() }}</x-ui.badge></span>
                        </x-ui.td>
                        <x-ui.td label="Pelanggan" class="truncate text-ink">{{ $o->customer->name }}</x-ui.td>
                        <x-ui.td label="Servis" class="truncate text-ink-2">{{ $o->service->label() }} – {{ $o->animal->label() }}</x-ui.td>
                        <x-ui.td label="Pakej" class="text-ink-2">{{ $o->package_name }}</x-ui.td>
                        <x-ui.td label="Negara" class="truncate text-ink-2">{{ $o->country->name }}</x-ui.td>
                        <x-ui.td label="Kuantiti" align="center" class="font-semibold text-ink">{{ $o->quantity }}</x-ui.td>
                        <x-ui.td label="Jumlah" align="right" class="font-semibold text-ink tabular-nums">{{ rm($o->total_sen) }}</x-ui.td>
                        <x-ui.td align="center" mobile="hide"><x-ui.badge :tone="$o->status->tone()">{{ $o->status->label() }}</x-ui.badge></x-ui.td>
                    </x-ui.tr>
                @empty
                    <x-slot:empty><x-ui.empty-state icon="shopping-cart-simple" title="Belum ada tempahan." /></x-slot:empty>
                @endforelse
            </x-ui.data-table>
        @endif
    </section>
</div>
