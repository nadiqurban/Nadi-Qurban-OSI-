@php
    $fmtStyle = ['pdf' => ['file-pdf', 'danger'], 'xlsx' => ['file-xls', 'success'], 'csv' => ['file-csv', 'info']];
    $chipOff = 'border border-border bg-bg text-muted';
    $chipOn = 'bg-primary text-white border border-primary';
@endphp

<div @if ($processing) wire:poll.4s @endif>
    <x-ui.page-header title="Pusat Laporan" :breadcrumb="['Laporan', 'Pusat Laporan']">
        <x-slot:actions>
            <x-ui.button icon="plus" mobile-block x-on:click="document.getElementById('builder').scrollIntoView({ behavior: 'smooth', block: 'start' }); document.getElementById('rep-type').focus()">Laporan Tersuai</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-[14px] text-[13px] font-bold text-ink-2">Jana Laporan Pantas</div>
    <div class="mb-7 grid gap-3 sm:grid-cols-2 md:gap-4 lg:grid-cols-[repeat(auto-fill,minmax(260px,1fr))]">
        @foreach (\App\Enums\ReportType::cases() as $t)
            <button type="button" id="quick-{{ $t->value }}" wire:click="quick('{{ $t->value }}')" wire:loading.attr="disabled"
                    class="group flex flex-col gap-[14px] rounded-[12px] border border-border bg-surface p-5 text-left transition-colors hover:border-primary/40">
                <span class="flex w-full items-center justify-between">
                    <span class="flex size-[46px] items-center justify-center rounded-[11px] {{ \App\Support\Tone::classes($t->tone()) }}"><i class="ph ph-{{ $t->icon() }} text-[24px]"></i></span>
                    <i class="ph ph-arrow-right text-[18px] text-faint transition-transform group-hover:translate-x-0.5"></i>
                </span>
                <span>
                    <span class="block text-[14.5px] font-bold text-ink">{{ $t->label() }}</span>
                    <span class="mt-[5px] block text-[12.5px] leading-[1.5] text-muted">{{ $t->description() }}</span>
                </span>
                <span class="flex w-full items-center gap-2 border-t border-divider pt-3">
                    <span class="rounded-[6px] bg-primary-soft px-[9px] py-[3px] text-[11.5px] font-semibold text-primary dark:text-[#c9ce93]">{{ strtoupper($t->defaultFormat()) }}</span>
                    <span class="text-[11.5px] text-faint">{{ $t->frequency() }}</span>
                </span>
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
        {{-- Bina Laporan --}}
        <section id="builder" class="min-w-0 scroll-mt-24 rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
            <h2 class="mb-1 text-[15px] font-bold text-ink">Bina Laporan</h2>
            <p class="mb-[18px] text-[12.5px] text-muted">Tetapkan parameter dan jana</p>
            <div class="flex flex-col gap-4">
                <div>
                    <label for="rep-type" class="mb-[7px] block text-[12.5px] font-semibold text-ink-2">Jenis Laporan</label>
                    <select id="rep-type" wire:model="type" class="w-full cursor-pointer rounded-[9px] border border-border bg-surface px-[14px] py-[11px] text-[13.5px] text-ink outline-none focus:border-primary max-md:text-[16px]">
                        @foreach (\App\Enums\ReportType::cases() as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <span class="mb-[7px] block text-[12.5px] font-semibold text-ink-2">Tempoh</span>
                    <div class="flex gap-[10px] max-sm:flex-col">
                        <label class="flex flex-1 items-center gap-2 rounded-[9px] border border-border px-3 py-[9px] text-[13px] text-muted max-md:min-h-11">
                            <i class="ph ph-calendar-blank text-[15px]"></i>
                            <input type="date" wire:model="from" aria-label="Tarikh mula" class="min-w-0 flex-1 bg-transparent text-[13px] text-ink outline-none max-md:text-[16px]">
                        </label>
                        <label class="flex flex-1 items-center gap-2 rounded-[9px] border border-border px-3 py-[9px] text-[13px] text-muted max-md:min-h-11">
                            <i class="ph ph-calendar-blank text-[15px]"></i>
                            <input type="date" wire:model="to" aria-label="Tarikh akhir" class="min-w-0 flex-1 bg-transparent text-[13px] text-ink outline-none max-md:text-[16px]">
                        </label>
                    </div>
                    @error('from')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                    @error('to')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                </div>
                <div>
                    <span class="mb-[7px] block text-[12.5px] font-semibold text-ink-2">Negara</span>
                    <div class="flex flex-wrap gap-[7px]">
                        <button type="button" wire:click="$set('countries', [])" @class(['rounded-[20px] px-3 py-1.5 text-[12px] font-semibold max-md:min-h-10', $countries === [] ? $chipOn : $chipOff])>Semua</button>
                        @foreach ($countryOptions as $id => $name)
                            <button type="button" wire:click="toggleCountry({{ $id }})" @class(['rounded-[20px] px-3 py-1.5 text-[12px] font-semibold max-md:min-h-10', in_array($id, $countries, true) ? $chipOn : $chipOff])>{{ $name }}</button>
                        @endforeach
                    </div>
                </div>
                <div>
                    <span class="mb-[7px] block text-[12.5px] font-semibold text-ink-2">Format</span>
                    <div class="flex gap-2">
                        @foreach (['pdf' => 'file-pdf', 'xlsx' => 'file-xls', 'csv' => 'file-csv'] as $f => $icon)
                            <button type="button" id="fmt-{{ $f }}" wire:click="$set('format', '{{ $f }}')" @class([
                                'flex items-center gap-1.5 rounded-[9px] border px-[14px] py-[9px] text-[12.5px] font-semibold max-md:min-h-11 max-md:flex-1 max-md:justify-center',
                                'border-primary bg-primary-soft text-primary dark:text-[#c9ce93]' => $format === $f,
                                'border-border bg-surface text-muted' => $format !== $f,
                            ])><i class="ph ph-{{ $icon }} text-[15px]"></i> {{ strtoupper($f) }}</button>
                        @endforeach
                    </div>
                </div>
                <x-ui.button icon="gear" id="btn-generate" wire:click="generate" wire:loading.attr="disabled" wire:target="generate" block class="mt-1 !py-[13px] !text-[14px] !font-bold">Jana Laporan</x-ui.button>
            </div>
        </section>

        {{-- Laporan Dijana --}}
        <section class="min-w-0 overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="flex items-center justify-between px-4 pt-5 pb-[14px] md:px-6">
                <h2 class="text-[15px] font-bold text-ink">Laporan Dijana</h2>
                <button type="button" wire:click="$toggle('showAll')" class="text-[12.5px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">{{ $showAll ? 'Tunjuk terkini' : 'Lihat semua' }}</button>
            </div>
            <x-ui.data-table cols="2fr 1fr 1.1fr 1fr 0.8fr" class="!rounded-none !border-x-0 !border-b-0">
                <x-slot:head>
                    <span>Laporan</span><span>Format</span><span>Dijana</span><span class="text-center">Status</span><span class="text-right">Muat</span>
                </x-slot:head>
                @forelse ($this->recent as $r)
                    @php [$icon, $tone] = $fmtStyle[$r->format] ?? $fmtStyle['pdf']; @endphp
                    <x-ui.tr wire:key="rep-{{ $r->id }}" class="md:!px-6">
                        <x-ui.td span class="flex items-center gap-[11px]">
                            <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[8px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph-fill ph-{{ $icon }} text-[17px]"></i></span>
                            <span class="min-w-0">
                                <span class="block truncate text-[13px] font-semibold text-ink">{{ $r->name }}</span>
                                <span class="text-[11.5px] text-faint">{{ $r->requester->name ?? 'Sistem' }}</span>
                            </span>
                        </x-ui.td>
                        <x-ui.td label="Format"><x-ui.badge :tone="$tone" variant="tag">{{ strtoupper($r->format) }}</x-ui.badge></x-ui.td>
                        <x-ui.td label="Dijana" class="text-[12.5px] text-muted">{{ tarikh($r->created_at, true) }}</x-ui.td>
                        <x-ui.td label="Status" align="center"><x-ui.badge :tone="$r->statusTone()" class="!px-[11px] !text-[11px]" :title="$r->error">{{ $r->statusLabel() }}</x-ui.badge></x-ui.td>
                        <x-ui.td align="right" span>
                            @if ($r->isDone())
                                <a href="{{ route('reports.download', $r) }}" aria-label="Muat turun {{ $r->name }}" class="flex size-[30px] items-center justify-center rounded-[8px] border border-border text-primary max-md:h-11 max-md:w-full max-md:gap-2 max-md:text-[13px] max-md:font-semibold dark:text-[#c9ce93]"><i class="ph ph-download-simple text-[16px]"></i><span class="md:hidden">Muat Turun</span></a>
                            @else
                                <span class="flex size-[30px] items-center justify-center rounded-[8px] border border-border text-[#CBD5D0] max-md:h-11 max-md:w-full" aria-hidden="true"><i class="ph ph-download-simple text-[16px]"></i></span>
                            @endif
                        </x-ui.td>
                    </x-ui.tr>
                @empty
                    <x-slot:empty><x-ui.empty-state icon="chart-bar" title="Belum ada laporan dijana." /></x-slot:empty>
                @endforelse
            </x-ui.data-table>
        </section>
    </div>
</div>
