@props([
    'count' => 0,
    'noun' => 'tempahan',
    'clearAction' => null,   // wire:click / @click expression
])

{{--
    Selection bar (#42481c, radius 10, 12px 18px). Desktop: inline above the table.
    Phones: sticks to the bottom of the screen; actions scroll horizontally.
--}}
<div {{ $attributes->merge(['class' => 'mb-[14px] flex items-center gap-3 rounded-[10px] bg-primary px-[18px] py-3 max-md:fixed max-md:inset-x-3 max-md:bottom-3 max-md:z-[90] max-md:mb-0 max-md:flex-col max-md:items-stretch max-md:gap-2 max-md:px-4 max-md:pb-safe max-md:shadow-modal']) }}>
    <div class="flex items-center gap-3">
        <i class="ph-fill ph-check-circle text-[18px] text-gold"></i>
        <span class="text-[13.5px] font-semibold text-white">{{ $count }} {{ $noun }} dipilih</span>
        @if ($clearAction)
            <button type="button" x-on:click="{{ $clearAction }}" class="ml-auto flex size-11 items-center justify-center rounded-[8px] text-[#cfe0d7] md:hidden" aria-label="Kosongkan pilihan"><i class="ph ph-x text-[15px]"></i></button>
        @endif
    </div>
    <div class="nq-scroll-x flex gap-2 md:ml-auto md:flex-wrap md:justify-end">
        {{ $slot }}
        @if ($clearAction)
            <button type="button" x-on:click="{{ $clearAction }}" class="hidden items-center gap-1.5 rounded-[8px] px-[10px] py-2 text-[12.5px] font-semibold text-[#cfe0d7] md:flex" aria-label="Kosongkan pilihan"><i class="ph ph-x text-[15px]"></i></button>
        @endif
    </div>
</div>
