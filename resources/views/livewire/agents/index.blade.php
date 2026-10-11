@php $canManage = $this->canManage(); @endphp

<div>
    <div class="mb-6 flex flex-wrap items-end gap-4">
        <div class="mr-auto min-w-0">
            <nav aria-label="Breadcrumb" class="mb-1.5 flex items-center gap-2 text-[12.5px] text-faint">
                <span>Jualan &amp; Kewangan</span><i class="ph ph-caret-right text-[12px]"></i><span class="font-semibold text-primary dark:text-[#c9ce93]">Pengurusan Ejen</span>
            </nav>
            <h1 class="text-[20px] font-bold text-ink md:text-[23px]">Pengurusan Ejen</h1>
            <p class="mt-1 text-[13.5px] text-muted">Daftar ejen sebagai pengguna untuk log masuk ke Portal Ejen.</p>
        </div>

        {{-- Tempoh --}}
        <div class="order-last flex basis-full flex-wrap items-center gap-[10px]">
            <span class="inline-flex items-center gap-1.5 text-[12px] font-semibold text-faint"><i class="ph ph-funnel text-[15px]"></i> Tempoh:</span>
            <div class="flex max-w-full gap-[2px] overflow-x-auto rounded-[9px] border border-border bg-surface p-[3px]" role="tablist" aria-label="Tempoh">
                @foreach (\App\Support\Period::KEYS as $key => $label)
                    <button type="button" role="tab" wire:click="setPeriod('{{ $key }}')" aria-selected="{{ $period === $key ? 'true' : 'false' }}"
                            @class(['shrink-0 rounded-[7px] px-3 py-1.5 text-[12.5px] font-semibold whitespace-nowrap max-md:min-h-10', 'bg-primary text-white' => $period === $key, 'text-ink-3' => $period !== $key])>{{ $label }}</button>
                @endforeach
            </div>
            @if ($period === 'range')
                <div class="flex flex-wrap items-center gap-2 rounded-[9px] border border-primary bg-surface px-[10px] py-1.5">
                    <i class="ph ph-calendar-dots text-[15px] text-primary"></i>
                    <span class="text-[11.5px] text-faint">Dari</span>
                    <input type="date" wire:model.live="fromDate" aria-label="Dari tarikh" class="border-0 bg-transparent text-[12.5px] text-ink outline-none max-md:text-[16px]">
                    <span class="text-[11.5px] text-faint">hingga</span>
                    <input type="date" wire:model.live="toDate" aria-label="Hingga tarikh" class="border-0 bg-transparent text-[12.5px] text-ink outline-none max-md:text-[16px]">
                </div>
            @else
                <label class="flex items-center gap-1.5 rounded-[9px] border border-border bg-surface px-[10px] py-1.5">
                    <i class="ph ph-calendar-blank text-[15px] text-primary"></i>
                    <input type="date" wire:model.live="refDate" aria-label="Tarikh rujukan" class="border-0 bg-transparent text-[12.5px] text-ink outline-none max-md:text-[16px]">
                </label>
            @endif
            <span class="rounded-[20px] bg-primary-soft px-3 py-[5px] text-[12px] font-semibold text-primary dark:text-[#c9ce93]">{{ $p->label() }}</span>
        </div>

        <div class="flex flex-wrap gap-2 max-md:w-full">
            <button type="button" wire:click="togglePending" aria-pressed="{{ $onlyPending ? 'true' : 'false' }}"
                    @class(['flex items-center gap-2 rounded-[9px] border px-[15px] py-[11px] text-[13.5px] font-semibold max-md:min-h-11',
                        'border-warning bg-warning text-white' => $onlyPending,
                        'border-[#F5D9A8] bg-warning-soft text-warning' => ! $onlyPending])>
                <i class="ph ph-hourglass-medium text-[16px]"></i> Pendaftaran Baharu
                <span @class(['rounded-[20px] px-2 py-px text-[11px] font-extrabold', 'bg-white/25 text-white' => $onlyPending, 'bg-warning text-white' => ! $onlyPending])>{{ $pendingCount }}</span>
            </button>
            <button type="button" x-data="{ copied: false }"
                    x-on:click="navigator.clipboard?.writeText(@js(route('agent.register'))); copied = true; setTimeout(() => copied = false, 1800)"
                    class="flex items-center gap-2 rounded-[9px] border border-primary bg-surface px-4 py-[11px] text-[13.5px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">
                <i class="ph text-[16px]" x-bind:class="copied ? 'ph-check' : 'ph-link-simple'"></i>
                <span x-text="copied ? 'Link Disalin' : 'Salin Link Pendaftaran'">Salin Link Pendaftaran</span>
            </button>
            <a href="{{ route('agent.register') }}" target="_blank" rel="noopener"
               class="flex items-center gap-[7px] rounded-[9px] border border-border bg-surface px-[14px] py-[11px] text-[13.5px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">
                <i class="ph ph-arrow-square-out text-[16px]"></i> Buka Borang
            </a>
            @if ($canManage)
                <x-ui.button icon="user-plus" mobile-block wire:click="create">Tambah Ejen</x-ui.button>
            @endif
        </div>
    </div>

    @error('agent')
        <div class="mb-5 flex items-center gap-[10px] rounded-[10px] border border-[#F7CFCF] bg-danger-soft px-[14px] py-3 text-[13px] font-semibold text-danger" role="alert"><i class="ph-fill ph-warning-circle text-[18px]"></i> {{ $message }}</div>
    @enderror

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4">
        @foreach ($stats as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <div class="mb-[22px] grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.6fr)]">
        <div class="flex flex-col gap-4">
            <div class="rounded-[12px] bg-primary px-5 py-[18px] text-white">
                <div class="flex items-center gap-2 text-[12px] text-[#d6d9bd]"><i class="ph ph-chart-line-up text-[16px]"></i> Jumlah Jualan Ejen</div>
                <div class="mt-2 text-[26px] font-extrabold tracking-[-.3px]">{{ rm($totals['sales'], true) }}</div>
                <div class="mt-1 text-[11.5px] text-[#d6d9bd]">{{ $totals['orders'] }} tempahan berbayar &middot; {{ $p->label() }}</div>
            </div>
            <div class="rounded-[12px] border border-border bg-surface px-5 py-[18px]">
                <div class="flex items-center gap-2 text-[12px] text-muted"><i class="ph ph-hand-coins text-[16px] text-gold-ink"></i> Jumlah Komisen Ejen</div>
                <div class="mt-2 text-[26px] font-extrabold tracking-[-.3px] text-gold-ink">{{ rm($totals['commission'], true) }}</div>
                <div class="mt-1 text-[11.5px] text-faint">Mengikut komisen produk (RM per unit) yang ditetapkan di halaman Produk</div>
            </div>
        </div>

        <section class="min-w-0 rounded-[12px] border border-border bg-surface px-5 py-[18px]">
            <div class="mb-[14px] flex items-center justify-between gap-2">
                <h2 class="text-[15px] font-bold text-ink">Statistik Jualan &amp; Komisen</h2>
                <span class="rounded-[20px] bg-primary-soft px-[10px] py-[3px] text-[11px] font-bold text-primary dark:text-[#c9ce93]">Top 10 Ejen</span>
            </div>
            <div class="grid grid-cols-[1.4fr_2fr_0.9fr] gap-3 border-b border-divider pb-2 text-[10.5px] font-bold tracking-[.4px] text-faint uppercase max-sm:grid-cols-[1fr_auto]">
                <span>Ejen</span><span class="max-sm:hidden">Jualan (RM)</span><span class="text-right">Komisen (RM)</span>
            </div>
            <div class="-mr-2 max-h-[286px] overflow-y-auto pr-2">
                @forelse ($top as $i => $r)
                    <div wire:key="perf-{{ $r['agent']->id }}" class="grid grid-cols-[1.4fr_2fr_0.9fr] items-center gap-3 border-b border-bg py-[11px] max-sm:grid-cols-[1fr_auto]">
                        <div class="flex min-w-0 items-center gap-[9px]">
                            <span @class([
                                'flex size-[22px] shrink-0 items-center justify-center rounded-[6px] text-[11px] font-extrabold',
                                'bg-gold text-white' => $i === 0,
                                'bg-primary text-white' => $i > 0 && $i < 3,
                                'bg-divider text-muted' => $i >= 3,
                            ])>{{ $i + 1 }}</span>
                            <div class="min-w-0">
                                <div class="truncate text-[13px] font-semibold text-ink">{{ $r['agent']->user->name }}</div>
                                <div class="font-mono text-[11px] text-faint">{{ $r['agent']->code }} &middot; {{ $r['count'] }} tempahan</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-[10px] max-sm:hidden">
                            <div class="h-2 flex-1 overflow-hidden rounded-[20px] bg-divider"><div class="h-full rounded-[20px] bg-primary" style="width: {{ round($r['sales_sen'] / $maxSales * 100) }}%"></div></div>
                            <span class="min-w-[86px] text-right text-[12.5px] font-bold text-ink">{{ rm($r['sales_sen'], true) }}</span>
                        </div>
                        <span class="text-right text-[13px] font-bold text-gold-ink">{{ rm($r['commission_sen'], true) }}</span>
                    </div>
                @empty
                    <p class="py-8 text-center text-[13px] text-faint">Belum ada ejen.</p>
                @endforelse
            </div>
        </section>
    </div>

    <x-ui.data-table cols="0.7fr 1.4fr 1.6fr 1.1fr 1fr 1fr 0.8fr 1.3fr" min-width="1040px">
        <x-slot:toolbar>
            <x-ui.search-input placeholder="Cari ID, nama, emel…" wire:model.live.debounce.300ms="search" class="md:max-w-[320px]" />
            <select wire:change="pickMonth($event.target.value)" aria-label="Bulan"
                    class="rounded-[9px] border border-border bg-surface px-[11px] py-2 text-[12.5px] text-ink-2 outline-none md:ml-auto max-md:min-h-11 max-md:text-[16px]">
                @foreach ($this->monthOptions() as $value => $label)
                    <option value="{{ $value }}" @selected($period === 'month' ? $p->date->format('Y-m') === $value : $value === '__all')>{{ $label }}</option>
                @endforeach
            </select>
            <x-ui.button variant="secondary" size="sm" icon="file-text" class="!border-primary !text-primary" x-on:click="$dispatch('open-modal', 'agent-invoice')">Pratonton Invois</x-ui.button>
            <x-ui.button variant="success" size="sm" icon="microsoft-excel-logo" wire:click="exportExcel">Eksport Excel</x-ui.button>
        </x-slot:toolbar>

        <x-slot:head>
            <span>ID Ejen</span><span>Nama Penuh</span><span>Emel</span><span>No. Telefon</span><span class="text-right">Jualan (RM)</span><span class="text-right">Komisen (RM)</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
        </x-slot:head>

        @foreach ($rows as $r)
            @php $a = $r['agent']; $active = $a->isActive(); $photo = $a->photo(); @endphp
            <x-ui.tr wire:key="agent-{{ $a->id }}">
                <x-ui.td span class="flex items-center justify-between gap-2 md:block">
                    <span class="font-mono font-bold text-primary dark:text-[#c9ce93]">{{ $a->code }}</span>
                    <span class="rounded-[20px] px-[11px] py-1 text-[11px] font-semibold md:hidden {{ $a->statusClasses() }}">{{ $a->statusLabel() }}</span>
                </x-ui.td>
                <x-ui.td span class="flex min-w-0 items-center gap-[9px]">
                    <button type="button" title="Lihat gambar" aria-label="Lihat gambar {{ $a->user->name }}"
                            @if ($photo) x-on:click="$dispatch('agent-photo', {{ \Illuminate\Support\Js::from(['src' => route('agents.photo', $a), 'download' => route('agents.photo', ['agent' => $a, 'muat-turun' => 1]), 'name' => $a->user->name, 'code' => $a->code]) }})" @else wire:click="view({{ $a->id }})" @endif
                            class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-soft text-[11px] font-bold text-primary dark:text-[#c9ce93]">
                        @if ($photo)<img src="{{ route('agents.photo', $a) }}" alt="{{ $a->user->name }}" loading="lazy" class="block size-full object-cover">@else{{ $a->initials() }}@endif
                    </button>
                    <span class="truncate font-semibold text-ink">{{ $a->user->name }}</span>
                </x-ui.td>
                <x-ui.td label="Emel" class="truncate text-ink-3">{{ $a->user->email }}</x-ui.td>
                <x-ui.td label="No. Telefon" class="text-ink-3">{{ $a->user->phone ?? '-' }}</x-ui.td>
                <x-ui.td label="Jualan (RM)" align="right" class="font-semibold text-ink">{{ rm($r['sales_sen'], true) }}</x-ui.td>
                <x-ui.td label="Komisen (RM)" align="right" class="font-bold text-gold-ink">{{ rm($r['commission_sen'], true) }}</x-ui.td>
                <x-ui.td align="center" mobile="hide">
                    <span class="inline-block rounded-[20px] px-[11px] py-1 text-[11px] font-semibold {{ $a->statusClasses() }}">{{ $a->statusLabel() }}</span>
                </x-ui.td>
                <x-ui.td align="right" span class="flex gap-1.5 md:justify-end">
                    <button type="button" wire:click="view({{ $a->id }})" title="Detail" aria-label="Detail {{ $a->user->name }}"
                            class="flex size-11 items-center justify-center rounded-[8px] border border-border text-primary md:size-[30px]"><i class="ph ph-eye text-[15px]"></i></button>
                    @if ($canManage)
                        <button type="button" wire:click="edit({{ $a->id }})" title="Edit" aria-label="Edit {{ $a->user->name }}"
                                class="flex size-11 items-center justify-center rounded-[8px] border border-border text-primary md:size-[30px]"><i class="ph ph-pencil-simple text-[15px]"></i></button>
                        @if ($a->isPending())
                            <button type="button" wire:click="approve({{ $a->id }})" title="Lulus" aria-label="Lulus {{ $a->user->name }}"
                                    class="flex h-11 items-center gap-[5px] rounded-[8px] bg-success px-[10px] text-[12px] font-semibold text-white md:h-[30px]"><i class="ph-fill ph-check-circle text-[14px]"></i> Lulus</button>
                            <button type="button" wire:click="reject({{ $a->id }})" wire:confirm="Tolak pendaftaran {{ $a->user->name }}?" title="Tolak" aria-label="Tolak {{ $a->user->name }}"
                                    class="flex size-11 items-center justify-center rounded-[8px] border border-[#F7CFCF] text-danger md:size-[30px]"><i class="ph ph-x text-[15px]"></i></button>
                        @elseif (! $a->isRejected())
                            <button type="button" wire:click="toggleStatus({{ $a->id }})" title="{{ $active ? 'Nyahaktif' : 'Aktifkan' }}" aria-label="{{ $active ? 'Nyahaktif' : 'Aktifkan' }} {{ $a->user->name }}"
                                    @class(['flex size-11 items-center justify-center rounded-[8px] border border-border md:size-[30px]', 'text-warning' => $active, 'text-success' => ! $active])><i class="ph {{ $active ? 'ph-prohibit' : 'ph-power' }} text-[15px]"></i></button>
                        @endif
                        <button type="button" wire:click="delete({{ $a->id }})" wire:confirm="Buang ejen {{ $a->user->name }}?" title="Buang" aria-label="Buang {{ $a->user->name }}"
                                class="flex size-11 items-center justify-center rounded-[8px] border border-[#F7CFCF] text-danger md:size-[30px]"><i class="ph ph-trash text-[15px]"></i></button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach

        @if ($rows->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="identification-badge" :title="$onlyPending ? 'Tiada pendaftaran baharu.' : 'Tiada ejen dijumpai.'" />
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    {{-- Pratonton Invois Komisen --}}
    <x-ui.modal name="agent-invoice" title="Pratonton Invois Komisen · A4" icon="file-text" max-width="860px" body-class="bg-bg p-3 md:p-6">
        <x-ui.doc-a4 padding="16mm 15mm">
            @include('pdf.partials.agent-commission-a4', ['rows' => $this->performance, 'period' => $p, 'number' => 'INV-KOM-'.str_replace('_', '-', $p->slug())])
        </x-ui.doc-a4>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            <x-ui.button variant="secondary" icon="printer" :href="route('agents.invoice', ['tempoh' => $period, 'tarikh' => $refDate, 'dari' => $fromDate, 'hingga' => $toDate])" target="_blank">Cetak</x-ui.button>
            <x-ui.button variant="gold" icon="download-simple" :href="route('agents.invoice', ['tempoh' => $period, 'tarikh' => $refDate, 'dari' => $fromDate, 'hingga' => $toDate, 'muat-turun' => 1])">Muat Turun PDF</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Detail ejen --}}
    @php $v = $this->viewing; $vp = $v ? $this->performance->firstWhere('agent.id', $v->id) : null; @endphp
    <x-ui.modal wire:model="showView" :title="$v?->user->name" :subtitle="$v ? $v->code.' · '.$v->statusLabel() : null" icon="identification-badge" max-width="520px" :footer-border="false">
        @if ($v)
            @php $vPhoto = $v->photo(); @endphp
            <div class="mb-4 flex items-center gap-[14px]">
                <button type="button" @if ($vPhoto) x-on:click="$dispatch('agent-photo', {{ \Illuminate\Support\Js::from(['src' => route('agents.photo', $v), 'download' => route('agents.photo', ['agent' => $v, 'muat-turun' => 1]), 'name' => $v->user->name, 'code' => $v->code]) }})" @endif
                        aria-label="Gambar {{ $v->user->name }}"
                        class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-border bg-primary-soft text-[18px] font-extrabold text-primary dark:text-[#c9ce93]">
                    @if ($vPhoto)<img src="{{ route('agents.photo', $v) }}" alt="{{ $v->user->name }}" class="block size-full object-cover">@else{{ $v->initials() }}@endif
                </button>
                <div class="min-w-0">
                    <div class="truncate text-[15px] font-bold text-ink">{{ $v->user->name }}</div>
                    <div class="mt-0.5 font-mono text-[12px] text-faint">{{ $v->code }} &middot; {{ $v->statusLabel() }}</div>
                    @unless ($vPhoto)<div class="mt-[3px] text-[11px] text-faint">Tiada gambar dimuat naik</div>@endunless
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-[10px] bg-primary px-[14px] py-3 text-white"><div class="text-[11px] text-[#d6d9bd]">Jualan</div><div class="mt-[3px] text-[17px] font-extrabold">{{ rm($vp['sales_sen'] ?? 0, true) }}</div></div>
                <div class="rounded-[10px] border border-[#EBDDAF] bg-[#FCFBF5] px-[14px] py-3"><div class="text-[11px] text-gold-ink">Komisen</div><div class="mt-[3px] text-[17px] font-extrabold text-gold-ink">{{ rm($vp['commission_sen'] ?? 0, true) }}</div></div>
            </div>
            @php
                $sections = [
                    'Hubungan' => ['Emel' => $v->user->email, 'No. Telefon' => $v->user->phone, 'Link Jualan' => $v->shareUrl()],
                    'Maklumat Peribadi' => ['Jantina' => $v->gender, 'Tarikh Lahir' => $v->birth_date?->format('d/m/Y'), 'Daerah' => $v->district, 'Negeri' => $v->state],
                    'Maklumat Bank' => ['Bank' => $v->bank_name, 'Nama Akaun' => $v->bank_account_name, 'Nombor Akaun' => $v->bank_account_no],
                    'Prestasi' => ['Bilangan Tempahan' => (string) ($vp['count'] ?? 0)],
                ];
            @endphp
            @foreach ($sections as $title => $items)
                <div class="pt-[14px] pb-1 text-[11px] font-bold tracking-[.5px] text-primary uppercase dark:text-[#c9ce93]">{{ $title }}</div>
                @foreach ($items as $k => $val)
                    <div class="flex justify-between gap-3 border-b border-divider py-[9px]">
                        <span class="shrink-0 text-[12.5px] text-faint">{{ $k }}</span>
                        <span class="min-w-0 text-right text-[13px] font-semibold break-all text-ink">{{ $val ?: '-' }}</span>
                    </div>
                @endforeach
            @endforeach
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            @if ($canManage && $v && $v->isPending())
                <x-ui.button variant="danger" icon="x" wire:click="reject({{ $v->id }})" wire:confirm="Tolak pendaftaran {{ $v->user->name }}?">Tolak</x-ui.button>
                <x-ui.button variant="success" icon="check-circle" wire:click="approve({{ $v->id }})">Lulus</x-ui.button>
            @elseif ($canManage && $v)
                <x-ui.button icon="pencil-simple" wire:click="edit({{ $v->id }})">Edit</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    {{-- Gambar Terkini (lightbox + Muat Turun) --}}
    <div x-data="{ pv: null }" x-on:agent-photo.window="pv = $event.detail" x-on:keydown.escape.window="pv = null">
        <template x-if="pv">
            <div class="fixed inset-0 z-[130] flex items-center justify-center bg-[rgba(12,14,12,.82)] p-6" x-on:click.self="pv = null" role="dialog" aria-modal="true" aria-label="Gambar ejen">
                <div class="w-full max-w-[420px] rounded-[16px] bg-surface p-[18px] text-center shadow-[0_24px_60px_rgba(0,0,0,.4)]">
                    <div class="aspect-square w-full overflow-hidden rounded-[12px] bg-divider"><img x-bind:src="pv.src" x-bind:alt="pv.name" class="block size-full object-cover"></div>
                    <div class="mt-[14px] text-[15px] font-bold text-ink" x-text="pv.name"></div>
                    <div class="mt-0.5 font-mono text-[12px] text-faint"><span x-text="pv.code"></span> &middot; Gambar Terkini</div>
                    <div class="mt-[14px] flex justify-center gap-2">
                        <a x-bind:href="pv.download" class="inline-flex items-center gap-1.5 rounded-[8px] bg-primary px-[14px] py-[9px] text-[12.5px] font-semibold text-white max-md:min-h-11"><i class="ph ph-download-simple text-[15px]"></i> Muat Turun</a>
                        <button type="button" x-on:click="pv = null" class="inline-flex items-center gap-1.5 rounded-[8px] border border-border bg-surface px-[14px] py-[9px] text-[12.5px] font-semibold text-ink-2 max-md:min-h-11">Tutup</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Tambah / Edit Ejen --}}
    @if ($canManage)
    <x-ui.modal wire:model="showForm" :title="$editingId ? 'Edit Ejen' : 'Tambah Ejen'" subtitle="Akaun log masuk Portal Ejen" icon="identification-badge" max-width="560px" :footer-border="false">
        <form id="agent-form" wire:submit="save" class="grid grid-cols-1 gap-[14px] md:grid-cols-2" novalidate>
            <x-ui.field label="ID Ejen" wire:model="code" placeholder="cth. AZ01" autocomplete="off" class="font-mono uppercase" />
            <x-ui.field label="No. Telefon" wire:model="phone" placeholder="01X-XXXXXXX" inputmode="tel" />
            <x-ui.field label="Nama Penuh Ejen" wire:model="name" placeholder="Nama penuh" span />
            <x-ui.field label="Emel" hint="(untuk log masuk)" type="email" wire:model="email" placeholder="nama@nadiqurban.com" span autocomplete="off" />
            <div class="col-span-full">
                <label for="f-password" class="mb-1.5 block text-[12px] font-semibold text-ink-2">Kata Laluan @if ($editingId)<span class="font-normal text-faint">(kosongkan jika tidak mahu tukar)</span>@endif</label>
                <div class="flex gap-2">
                    <input id="f-password" wire:model="password" placeholder="Kata laluan ejen" autocomplete="new-password"
                           @class(['min-w-0 flex-1 rounded-[9px] border bg-surface px-3 py-[10px] font-mono text-[13px] text-ink outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]', 'border-danger' => $errors->has('password'), 'border-border' => ! $errors->has('password')])>
                    <button type="button" wire:click="autoPassword" class="rounded-[9px] bg-primary-soft px-[14px] text-[12.5px] font-semibold text-primary max-md:min-h-11">Auto</button>
                </div>
                @error('password')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
            </div>

            <div class="col-span-full mt-1.5 border-t border-divider pt-3 text-[11px] font-bold tracking-[.5px] text-primary uppercase dark:text-[#c9ce93]">Maklumat Peribadi</div>
            <x-ui.field label="Jantina" as="select" wire:model="gender">
                @foreach (\App\Models\Agent::GENDERS as $g)<option>{{ $g }}</option>@endforeach
            </x-ui.field>
            <x-ui.field label="Tarikh Lahir" type="date" wire:model="birthDate" />
            <x-ui.field label="Daerah" wire:model="district" placeholder="cth. Petaling" />
            <x-ui.field label="Negeri" as="select" wire:model="state">
                @foreach (\App\Models\Agent::STATES as $st)<option>{{ $st }}</option>@endforeach
            </x-ui.field>

            <div class="col-span-full mt-1.5 border-t border-divider pt-3 text-[11px] font-bold tracking-[.5px] text-primary uppercase dark:text-[#c9ce93]">Maklumat Bank</div>
            <x-ui.field label="Bank" as="select" wire:model="bankName" span>
                @foreach (\App\Models\Agent::BANKS as $b)<option>{{ $b }}</option>@endforeach
            </x-ui.field>
            <x-ui.field label="Nama Akaun" wire:model="bankAccountName" placeholder="Nama pemegang akaun" />
            <x-ui.field label="Nombor Akaun" wire:model="bankAccountNo" placeholder="cth. 5623 5782 2681" inputmode="numeric" class="font-mono" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button type="submit" form="agent-form" icon="check">Simpan Ejen</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
    @endif
</div>
