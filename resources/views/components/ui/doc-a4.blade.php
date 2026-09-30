@props([
    'padding' => '48px 52px',
])

{{--
    A4 preview (210×297mm ≈ 794×1123px at 96dpi). Scales down to fit its
    container on small screens (Alpine docScale), keeping the layout identical
    to the printed PDF.
--}}
<div x-data="docScale(794)" class="w-full overflow-hidden" :style="`height: ${1123 * scale}px`">
    <div :style="`transform: scale(${scale}); transform-origin: top left`"
         style="width: 794px; min-height: 1123px; padding: {{ $padding }}"
         {{ $attributes->merge(['class' => 'bg-white text-[#1A1D21] shadow-[0_4px_16px_rgba(0,0,0,.12)]']) }}>
        {{ $slot }}
    </div>
</div>
