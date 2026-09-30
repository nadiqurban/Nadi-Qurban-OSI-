@props([
    'hasFilters' => false,   // show "Reset" chip
    'resetAction' => null,   // wire:click / @click expression for Reset
    'activeCount' => 0,      // badge on the mobile "Tapis" button
    'plain' => false,        // pipeline screens: bare row (no card) with a "Tapis:" label and mini selects
])

{{--
    Filter card (Tempahan & Pelanggan.dc.html): optional `tabs` slot on top,
    then search + filters inline. On phones the `filters` slot moves into a
    bottom sheet opened by a "Tapis" button (same DOM, so bindings are shared).
--}}
<div x-data="{ sheet: false }" {{ $attributes->merge(['class' => $plain ? 'mb-[14px]' : 'mb-5 rounded-[12px] border border-border bg-surface px-4 py-4 md:px-5 md:py-[18px]']) }}>
    @isset($tabs)
        <div class="mb-[14px]">{{ $tabs }}</div>
    @endisset

    <div @class(['flex flex-wrap items-center', 'gap-[10px]' => $plain, 'gap-3' => ! $plain])>
        {{ $search ?? '' }}

        @if ($plain)
            <span class="hidden items-center gap-1.5 text-[12px] font-semibold text-faint md:inline-flex"><i class="ph ph-funnel text-[15px]"></i> Tapis:</span>
        @endif

        <button type="button" @click="sheet = true"
                class="relative flex min-h-11 items-center gap-2 rounded-[9px] border border-border bg-surface px-[14px] text-[13.5px] font-semibold text-ink-2 md:hidden">
            <i class="ph ph-funnel-simple text-[17px]"></i> Tapis
            @if ($activeCount > 0)
                <span class="rounded-[20px] bg-primary px-[7px] text-[11px] font-bold text-white">{{ $activeCount }}</span>
            @endif
        </button>

        {{-- Overlay (phones only) --}}
        <div x-cloak x-show="sheet" x-transition.opacity @click="sheet = false"
             class="fixed inset-0 z-[110] bg-[rgba(20,24,20,.55)] md:hidden"></div>

        <div :data-open="sheet"
             class="contents max-md:fixed max-md:inset-x-0 max-md:bottom-0 max-md:z-[120] max-md:flex max-md:max-h-[85vh] max-md:invisible max-md:translate-y-full max-md:flex-col max-md:data-[open=true]:visible max-md:data-[open=true]:translate-y-0 max-md:rounded-t-[16px] max-md:bg-surface max-md:shadow-modal max-md:transition-transform max-md:duration-200"
             role="dialog" aria-label="Tapis">
            <div class="flex items-center justify-between border-b border-border px-5 py-4 md:hidden">
                <div class="flex items-center gap-2 text-[16px] font-bold text-ink"><i class="ph ph-funnel-simple text-[19px] text-primary"></i> Tapis</div>
                <button type="button" @click="sheet = false" class="flex size-11 items-center justify-center rounded-lg border border-border text-muted" aria-label="Tutup"><i class="ph ph-x text-[16px]"></i></button>
            </div>
            <div class="contents max-md:flex max-md:flex-1 max-md:flex-col max-md:gap-3 max-md:overflow-y-auto max-md:px-5 max-md:py-4">
                {{ $filters ?? '' }}
            </div>
            <div class="flex gap-3 border-t border-border px-5 pt-3 pb-safe md:hidden">
                @if ($resetAction)
                    <x-ui.button variant="danger-soft" icon="x" class="flex-1" x-on:click="{{ $resetAction }}">Reset</x-ui.button>
                @endif
                <x-ui.button class="flex-1" @click="sheet = false">Guna</x-ui.button>
            </div>
        </div>

        @if ($hasFilters && $resetAction && $plain)
            <button type="button" x-on:click="{{ $resetAction }}" class="hidden items-center gap-[5px] text-[12px] font-semibold text-danger md:inline-flex">
                <i class="ph ph-x text-[13px]"></i> Reset
            </button>
        @elseif ($hasFilters && $resetAction)
            <button type="button" x-on:click="{{ $resetAction }}"
                    class="hidden items-center gap-[7px] rounded-[9px] bg-danger-soft px-[14px] py-[10px] text-[13px] font-semibold text-danger md:flex">
                <i class="ph ph-x text-[15px]"></i> Reset
            </button>
        @endif
    </div>
</div>
