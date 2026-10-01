@php
    $sel = $this->selected;
    $sevText = ['info' => 'text-ink-2', 'amaran' => 'text-warning', 'kritikal' => 'text-danger'];
    $chip = 'rounded-[8px] px-[13px] py-2 text-[12.5px] font-semibold max-md:min-h-10';
@endphp

<div>
    <x-ui.page-header title="Log Audit Sistem" :breadcrumb="['Laporan', 'Audit Log']" class="md:!mb-[22px]">
        <x-slot:actions>
            <label class="relative flex items-center gap-2 rounded-[9px] border border-border bg-surface px-[14px] py-[10px] text-[13.5px] font-semibold text-ink-2 max-md:min-h-11 max-md:flex-1">
                <i class="ph ph-calendar-blank text-[16px] text-muted"></i>
                <select wire:model.live="range" id="audit-range" aria-label="Tempoh" class="cursor-pointer appearance-none bg-transparent pr-5 font-semibold outline-none max-md:flex-1 max-md:text-[16px]">
                    @foreach (\App\Livewire\Audit\Index::RANGES as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
                <i class="ph ph-caret-down pointer-events-none absolute right-[14px] text-[13px] text-faint"></i>
            </label>
            <x-ui.button variant="secondary" icon="download-simple" id="btn-audit-csv" :href="route('audit.export', array_filter(['tempoh' => $range, 'tahap' => $severity, 'q' => $search]))" class="!px-[14px] [&>i]:!text-[16px] [&>i]:text-muted max-md:flex-1">Eksport CSV</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-5 grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($overview['stats'] as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_300px]">
        <x-ui.data-table cols="1.5fr 1.3fr 2fr 1.1fr 0.5fr" min-width="760px">
            <x-slot:toolbar>
                <label class="flex min-w-[180px] flex-1 items-center gap-[10px] rounded-[9px] border border-border bg-bg px-[13px] py-[9px] max-md:min-h-11 max-md:w-full">
                    <i class="ph ph-magnifying-glass text-[16px] text-faint"></i>
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari tindakan atau pengguna" aria-label="Cari tindakan atau pengguna" class="min-w-0 flex-1 bg-transparent text-[13px] text-ink outline-none max-md:text-[16px]">
                </label>
                <div class="nq-scroll-x flex items-center gap-2 max-md:w-full">
                    <button type="button" wire:click="$set('severity', '')" @class([$chip, 'shrink-0', 'bg-primary text-white' => $severity === '', 'bg-bg text-muted' => $severity !== ''])>Semua</button>
                    @foreach (\App\Enums\Severity::cases() as $sv)
                        <button type="button" id="sev-{{ $sv->value }}" wire:click="$set('severity', '{{ $sv->value }}')" @class([$chip, 'shrink-0', 'bg-primary text-white' => $severity === $sv->value, 'bg-bg text-muted' => $severity !== $sv->value])>{{ $sv->label() }}</button>
                    @endforeach
                </div>
            </x-slot:toolbar>
            <x-slot:head>
                <span>Pengguna</span><span>Tindakan</span><span>Butiran</span><span>Masa</span><span></span>
            </x-slot:head>
            @forelse ($this->logs as $log)
                @php
                    [$icon, $tone] = \App\Support\AuditAction::style($log->event);
                    $causer = $log->causer instanceof \App\Models\User ? $log->causer : null;
                    $sv = (string) $log->getAttribute('severity');
                @endphp
                <x-ui.tr wire:key="log-{{ $log->id }}" id="log-{{ $log->id }}" wire:click="select({{ $log->id }})" @class(['cursor-pointer hover:bg-bg md:!py-[13px]', '!bg-[#f7f8f2] dark:!bg-bg' => $selectedId === $log->id])>
                    <x-ui.td span class="flex items-center gap-[11px]">
                        @if ($causer)
                            <x-ui.avatar :name="$causer->name" :size="34" class="!text-[12px] !font-bold" />
                        @else
                            <span class="flex size-[34px] shrink-0 items-center justify-center rounded-full bg-neutral-soft text-[12px] font-bold text-neutral">SI</span>
                        @endif
                        <span class="min-w-0">
                            <span class="block truncate text-[13px] font-semibold text-ink">{{ $causer->name ?? 'Sistem' }}</span>
                            <span class="text-[11px] text-faint">{{ $causer->role_label ?? 'Automasi' }}</span>
                        </span>
                    </x-ui.td>
                    <x-ui.td label="Tindakan" class="flex items-center gap-2">
                        <span class="flex size-[26px] shrink-0 items-center justify-center rounded-[7px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[14px]"></i></span>
                        <span class="truncate text-[13px] font-semibold {{ $sevText[$sv] ?? 'text-ink-2' }}">{{ \App\Support\AuditAction::label($log->event) }}</span>
                    </x-ui.td>
                    <x-ui.td label="Butiran" span class="truncate text-[12.5px] text-muted" :title="$log->description">{{ $log->description }}</x-ui.td>
                    <x-ui.td label="Masa" class="text-[12.5px] text-muted">{{ masa_lalu($log->created_at) }}</x-ui.td>
                    <x-ui.td align="right" mobile="hide"><i class="ph ph-caret-right text-[16px] text-[#CBD5D0]"></i></x-ui.td>
                </x-ui.tr>
            @empty
                <x-slot:empty><x-ui.empty-state icon="clock-counter-clockwise" title="Tiada log untuk tapisan ini." /></x-slot:empty>
            @endforelse
            <x-slot:footer>
                <x-ui.pagination :paginator="$this->logs" noun="log" />
            </x-slot:footer>
        </x-ui.data-table>

        @if ($sel)
            @php
                [$icon, $tone] = \App\Support\AuditAction::style($sel->event);
                $sevEnum = \App\Enums\Severity::tryFrom((string) $sel->getAttribute('severity')) ?? \App\Enums\Severity::Info;
                $causer = $sel->causer instanceof \App\Models\User ? $sel->causer : null;
                $ref = data_get($sel->properties, 'ref');
                $rows = array_filter([
                    'Log ID' => 'LOG-'.$sel->id,
                    'Pengguna' => ($causer->name ?? 'Sistem').' ('.($causer->role_label ?? 'Automasi').')',
                    'Tindakan' => \App\Support\AuditAction::label($sel->event),
                    'Butiran' => $sel->description,
                    'Rujukan' => $ref,
                    'Alamat IP' => $sel->getAttribute('ip_address') ?: '-',
                    'Masa' => tarikh($sel->created_at, true),
                ], fn ($v) => $v !== null && $v !== '');
            @endphp
            <div class="fixed inset-0 z-[110] bg-[rgba(20,24,20,.55)] lg:hidden" wire:click="select({{ $sel->id }})"></div>
            <section class="rounded-[12px] border border-border bg-surface px-[22px] py-5 max-lg:fixed max-lg:inset-x-0 max-lg:bottom-0 max-lg:z-[111] max-lg:max-h-[85vh] max-lg:overflow-y-auto max-lg:rounded-b-none lg:sticky lg:top-[100px]">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-[15px] font-bold text-ink">Butiran Log</h2>
                    <button type="button" wire:click="select({{ $sel->id }})" class="flex size-7 items-center justify-center rounded-[8px] border border-border text-muted max-md:size-11" aria-label="Tutup"><i class="ph ph-x text-[15px]"></i></button>
                </div>
                <div class="flex items-center gap-[11px] border-b border-divider pb-4">
                    <span class="flex size-11 items-center justify-center rounded-[11px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[22px]"></i></span>
                    <div>
                        <div class="text-[14px] font-bold text-ink">{{ \App\Support\AuditAction::label($sel->event) }}</div>
                        <x-ui.badge :tone="$sevEnum->tone()" class="mt-[3px] !px-[9px] !py-[3px] !text-[10.5px] !font-bold">{{ $sevEnum->label() }}</x-ui.badge>
                    </div>
                </div>
                <div class="flex flex-col gap-[13px] pt-4">
                    @foreach ($rows as $k => $v)
                        <div>
                            <div class="text-[11px] tracking-[.4px] text-faint uppercase">{{ $k }}</div>
                            <div class="mt-[3px] text-[13px] font-semibold break-words text-ink">{{ $v }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <section class="rounded-[12px] border border-border bg-surface px-[22px] py-5">
                <h2 class="mb-4 text-[15px] font-bold text-ink">Aktiviti Mengikut Jenis</h2>
                <div class="flex flex-col gap-[14px]">
                    @foreach ($overview['types'] as $t)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between">
                                <span class="flex items-center gap-[7px] text-[12.5px] text-ink-2"><i class="ph ph-{{ $t['icon'] }} text-[15px] {{ \App\Support\Tone::fg($t['tone']) }}"></i>{{ $t['label'] }}</span>
                                <span class="text-[12.5px] font-bold text-ink">{{ number_format($t['count']) }}</span>
                            </div>
                            <div class="h-[7px] overflow-hidden rounded-[20px] bg-primary-soft"><div class="h-full rounded-[20px] {{ ['gold' => 'bg-gold', 'info' => 'bg-info', 'primary' => 'bg-primary', 'purple' => 'bg-purple', 'danger' => 'bg-danger'][$t['tone']] ?? 'bg-primary' }}" style="width: {{ $t['width'] }}%"></div></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 flex items-start gap-[10px] rounded-[10px] bg-bg p-[14px]">
                    <i class="ph ph-shield-check text-[18px] text-success"></i>
                    <span class="text-[12px] leading-[1.5] text-muted">Semua log disimpan selama 24 bulan dan tidak boleh diubah (immutable).</span>
                </div>
            </section>
        @endif
    </div>
</div>
