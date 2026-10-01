@php
    $isKanban = $mode !== 'senarai';
    $seg = 'flex items-center gap-[7px] px-[15px] py-[9px] text-[13px] font-semibold max-md:min-h-11 max-md:flex-1 max-md:justify-center';
@endphp

<div>
    <x-ui.page-header title="Saluran Jualan" :breadcrumb="['Jualan & Kewangan', 'Sales CRM']">
        <x-slot:actions>
            <div class="flex items-center overflow-hidden rounded-[9px] border border-border bg-surface max-md:flex-1" role="tablist">
                <button type="button" role="tab" id="mode-kanban" wire:click="$set('mode', 'kanban')" aria-selected="{{ $isKanban ? 'true' : 'false' }}" @class([$seg, 'bg-primary text-white' => $isKanban, 'text-muted' => ! $isKanban])><i class="ph ph-kanban text-[16px]"></i> Kanban</button>
                <button type="button" role="tab" id="mode-senarai" wire:click="$set('mode', 'senarai')" aria-selected="{{ $isKanban ? 'false' : 'true' }}" @class([$seg, 'bg-primary text-white' => ! $isKanban, 'text-muted' => $isKanban])><i class="ph ph-list-bullets text-[16px]"></i> Senarai</button>
            </div>
            @if ($canManage)
                <x-ui.button icon="plus" id="btn-new-lead" wire:click="openNewLead" class="max-md:flex-1">Lead Baharu</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-[18px] lg:grid-cols-4">
        @foreach ($this->kpis() as $k)
            <x-ui.stat-card :icon="$k['icon']" :tone="$k['tone']" :value="$k['value']" :label="$k['label']" class="md:!px-5 md:!py-[18px] md:[&>div:first-child]:!size-11 md:[&>div:first-child]:!rounded-[11px]" />
        @endforeach
    </div>

    @if ($isKanban)
        {{-- KANBAN: 5 columns; phones scroll horizontally with 85vw snap columns --}}
        <div class="nq-scroll-x -mx-4 grid snap-x snap-mandatory auto-cols-[85vw] grid-flow-col gap-[14px] px-4 pb-2 md:mx-0 md:grid-flow-row md:snap-none md:grid-cols-[repeat(5,minmax(240px,1fr))] md:overflow-x-auto md:px-0">
            @foreach ($columns as $col)
                @php $stage = $col['stage']; @endphp
                <div wire:key="col-{{ $stage->value }}" data-stage="{{ $stage->value }}" class="flex min-h-[200px] snap-start flex-col gap-[10px] rounded-[12px] border border-border bg-[#eef0e6] p-3 dark:bg-bg">
                    <div class="flex items-center justify-between px-1 py-0.5">
                        <div class="flex items-center gap-2">
                            <span class="size-[9px] rounded-full" style="background: {{ $stage->dot() }}"></span>
                            <span class="text-[13px] font-bold text-ink-2">{{ $stage->label() }}</span>
                            <span class="rounded-[20px] bg-surface px-2 py-px text-[11px] font-bold text-muted">{{ $col['leads']->count() }}</span>
                        </div>
                        @if ($canManage)
                            <button type="button" wire:click="openNewLead('{{ $stage->value }}')" class="flex size-6 items-center justify-center text-faint hover:text-primary max-md:size-11" aria-label="Tambah lead ke {{ $stage->label() }}"><i class="ph ph-plus text-[15px]"></i></button>
                        @endif
                    </div>
                    <div class="px-1 text-[11.5px] font-semibold text-faint">{{ rm_short((int) $col['leads']->sum('value_sen')) }}</div>

                    <div @if ($canManage) x-data="kanbanColumn('{{ $stage->value }}')" @endif class="flex min-h-[60px] flex-1 flex-col gap-[10px]">
                        @foreach ($col['leads'] as $lead)
                            <a href="{{ route('crm.show', $lead) }}" wire:navigate wire:key="lead-{{ $lead->id }}" data-lead="{{ $lead->id }}"
                               @class(['flex flex-col gap-[9px] rounded-[10px] border border-border bg-surface p-[13px] hover:border-primary/40', 'cursor-grab' => $canManage])>
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-[13.5px] leading-[1.3] font-bold text-ink">{{ $lead->name }}</span>
                                    <x-ui.badge :tone="$lead->serviceTone()" variant="tag">{{ $lead->service }}</x-ui.badge>
                                </div>
                                <div class="flex items-center gap-[5px] text-[12px] text-muted"><i class="ph ph-buildings text-[14px]"></i><span class="truncate">{{ $lead->company ?: 'Individu' }}</span></div>
                                <div class="flex items-center justify-between border-t border-divider pt-[9px]">
                                    <span class="text-[13px] font-bold text-primary dark:text-[#c9ce93]">{{ rm($lead->value_sen) }}</span>
                                    <span class="flex items-center gap-[5px]">
                                        <x-ui.avatar :name="$lead->owner?->name ?? 'Sistem'" :size="22" class="!text-[10px] !font-bold" />
                                        <span class="text-[11px] text-faint">{{ $lead->daysLabel() }}</span>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- SENARAI --}}
        <x-ui.data-table cols="1.5fr 1.3fr 1fr 1fr 1.1fr 0.9fr 0.6fr" min-width="960px">
            <x-slot:head>
                <span>Lead</span><span>Syarikat</span><span>Servis</span><span class="text-right">Nilai</span><span>Peringkat</span><span>Owner</span><span class="text-right">Lihat</span>
            </x-slot:head>
            @forelse (collect($columns)->flatMap(fn ($c) => $c['leads']) as $lead)
                <x-ui.tr wire:key="row-{{ $lead->id }}">
                    <x-ui.td span class="truncate font-semibold text-ink">{{ $lead->name }}</x-ui.td>
                    <x-ui.td label="Syarikat" class="truncate text-[12.5px] text-muted">{{ $lead->company ?: 'Individu' }}</x-ui.td>
                    <x-ui.td label="Servis"><x-ui.badge :tone="$lead->serviceTone()" variant="tag">{{ $lead->service }}</x-ui.badge></x-ui.td>
                    <x-ui.td label="Nilai" align="right" class="font-bold text-primary tabular-nums dark:text-[#c9ce93]">{{ rm($lead->value_sen) }}</x-ui.td>
                    <x-ui.td label="Peringkat"><span class="flex items-center gap-1.5"><span class="size-2 shrink-0 rounded-full" style="background: {{ $lead->stage->dot() }}"></span><span class="text-[12.5px] text-ink-2">{{ $lead->stage->label() }}</span></span></x-ui.td>
                    <x-ui.td label="Owner"><span class="flex items-center gap-[7px]"><x-ui.avatar :name="$lead->owner?->name ?? 'Sistem'" :size="24" class="!text-[10px] !font-bold" /><span class="text-[12px] text-faint">{{ $lead->daysLabel() }}</span></span></x-ui.td>
                    <x-ui.td align="right" span>
                        <a href="{{ route('crm.show', $lead) }}" wire:navigate aria-label="Lihat {{ $lead->name }}" class="flex size-[30px] items-center justify-center rounded-[8px] border border-border text-primary max-md:h-11 max-md:w-full max-md:gap-2 max-md:text-[13px] max-md:font-semibold dark:text-[#c9ce93]"><i class="ph ph-eye text-[16px]"></i><span class="md:hidden">Lihat Lead</span></a>
                    </x-ui.td>
                </x-ui.tr>
            @empty
                <x-slot:empty><x-ui.empty-state icon="users-three" title="Belum ada lead." /></x-slot:empty>
            @endforelse
        </x-ui.data-table>
    @endif

    @if ($canManage)
        <x-ui.modal wire:model="showNewLead" title="Lead Baharu" :subtitle="$newStage === 'baru' ? 'Tambah prospek jualan baharu' : 'Tambah prospek ke lajur '.(\App\Enums\LeadStage::tryFrom($newStage)?->label() ?? '')" icon="user-plus" max-width="520px" :footer-border="false">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field label="Nama Prospek" id="lead-name" wire:model="name" placeholder="Nama penuh / syarikat" span />
                <x-ui.field label="No. Telefon" wire:model="phone" placeholder="01X-XXXXXXX" inputmode="tel" />
                <x-ui.field label="Emel" type="email" wire:model="email" placeholder="emel@contoh.com" />
                <x-ui.field label="Syarikat" wire:model="company" placeholder="Individu / nama syarikat" />
                <x-ui.field label="Jenis Servis" as="select" wire:model="service">
                    @foreach (\App\Models\Lead::SERVICES as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                </x-ui.field>
                <x-ui.field label="Jenis Pakej" as="select" wire:model="package">
                    @foreach (\App\Models\Lead::PACKAGES as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
                </x-ui.field>
                <x-ui.field label="Nilai (RM)" wire:model="value" placeholder="cth. 2450" inputmode="decimal" />
                <x-ui.field label="Catatan Nota" as="textarea" rows="3" wire:model="notes" placeholder="Catatan tambahan mengenai lead ini..." span />
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button icon="check" id="btn-save-lead" wire:click="saveLead" wire:loading.attr="disabled" wire:target="saveLead">Simpan Lead</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
