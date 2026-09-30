@php
    $stats = [
        ['value' => $v->active_po_count, 'label' => 'PO Aktif', 'class' => 'text-primary dark:text-[#c9ce93]'],
        ['value' => $v->completed_po_count, 'label' => 'PO Selesai', 'class' => 'text-success'],
        ['value' => $v->completionLabel(), 'label' => 'Kadar Siap', 'class' => 'text-gold'],
        ['value' => $v->rating, 'label' => 'Rating Purata', 'class' => 'text-info'],
    ];
@endphp

<div>
    @unless ($isPic)
        <a href="{{ route('vendors.index') }}" wire:navigate class="mb-[18px] inline-flex min-h-10 items-center gap-[7px] text-[13.5px] font-semibold text-primary hover:text-primary-hover dark:text-[#c9ce93]"><i class="ph ph-arrow-left text-[17px]"></i> Kembali ke senarai</a>
    @endunless

    {{-- Header --}}
    <div class="mb-5 flex flex-wrap items-center gap-4 rounded-[14px] border border-border bg-surface p-5 md:gap-5 md:p-6">
        <div class="flex size-[60px] shrink-0 items-center justify-center rounded-[16px] text-[21px] font-extrabold md:size-[72px] md:text-[24px] {{ $v->avatarClasses() }}">{{ $v->initials() }}</div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                <h1 class="text-[20px] font-bold text-ink md:text-[22px]">{{ $v->name }}</h1>
                @if ($v->level)
                    <span class="inline-flex items-center gap-[5px] rounded-[20px] px-[11px] py-1 text-[11.5px] font-bold {{ $v->level->badgeClasses() }}"><i class="ph-fill ph-medal text-[14px]"></i> {{ $v->level->label() }}</span>
                @endif
                <x-ui.badge :tone="$v->statusTone()" class="!text-[11px] !px-[11px]">{{ $v->status->label() }}</x-ui.badge>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-muted">
                <span class="flex items-center gap-[5px]"><i class="ph ph-map-pin text-[15px]"></i>{{ $v->country->name }}</span>
                @if ($v->phone)<span class="flex items-center gap-[5px]"><i class="ph ph-phone text-[15px]"></i>{{ $v->phone }}</span>@endif
                @if ($v->email)<span class="flex min-w-0 items-center gap-[5px] break-all"><i class="ph ph-envelope-simple text-[15px]"></i>{{ $v->email }}</span>@endif
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="mb-5 grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($stats as $s)
            <div class="rounded-[12px] border border-border bg-surface px-5 py-[18px]"><div class="text-[22px] font-bold md:text-[24px] {{ $s['class'] }}">{{ $s['value'] }}</div><div class="mt-1.5 text-[12.5px] text-muted">{{ $s['label'] }}</div></div>
        @endforeach
    </div>

    {{-- Tabs (scroll horizontally on phones) --}}
    <div class="mb-5 flex items-center gap-1 overflow-x-auto border-b border-border [scrollbar-width:none]" role="tablist">
        @foreach ($this->tabs() as $key => [$label, $icon])
            @php $on = $tab === $key; @endphp
            <button type="button" role="tab" aria-selected="{{ $on ? 'true' : 'false' }}" wire:click="setTab('{{ $key }}')"
                    @class(['-mb-px flex min-h-11 shrink-0 items-center gap-[7px] border-b-2 px-[15px] py-[11px] text-[13px] font-semibold whitespace-nowrap', 'border-primary text-primary dark:text-[#c9ce93]' => $on, 'border-transparent text-muted hover:text-ink-2' => ! $on])>
                <i class="ph ph-{{ $icon }} text-[16px]"></i> {{ $label }}
            </button>
        @endforeach
    </div>

    @switch($tab)
        @case('po')
            <livewire:vendors.tabs.purchase-orders :vendor-id="$v->id" :key="'po-'.$v->id" />
            @break
        @case('bayaran')
            <livewire:vendors.tabs.payments :vendor-id="$v->id" :key="'pay-'.$v->id" />
            @break
        @case('laporan')
            <livewire:vendors.tabs.reports :vendor-id="$v->id" :key="'rpt-'.$v->id" />
            @break
        @case('prestasi')
            <livewire:vendors.tabs.performance :vendor-id="$v->id" :key="'perf-'.$v->id" />
            @break
        @case('audit')
            <livewire:vendors.tabs.audit-log :vendor-id="$v->id" :key="'audit-'.$v->id" />
            @break
        @default
            <livewire:vendors.tabs.profile :vendor-id="$v->id" :key="'profile-'.$v->id" />
    @endswitch
</div>
