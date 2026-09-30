@props([
    'icon',
    'value',
    'label',
    'tone' => 'primary',
])

{{-- Summary chip: 42px tinted icon box + 22px value + 12.5px label (Tempahan & Pelanggan.dc.html) --}}
<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-[12px] border border-border bg-surface px-[14px] py-[14px] md:gap-[14px] md:px-[18px] md:py-4']) }}>
    <div class="flex size-10 shrink-0 items-center justify-center rounded-[10px] md:size-[42px] {{ \App\Support\Tone::classes($tone) }}">
        <i class="ph ph-{{ $icon }} text-[20px] md:text-[22px]"></i>
    </div>
    <div class="min-w-0">
        <div class="truncate text-[19px] leading-none font-bold text-ink md:text-[22px]">{{ $value }}</div>
        <div class="mt-[5px] text-[12px] text-muted md:text-[12.5px]">{{ $label }}</div>
    </div>
</div>
