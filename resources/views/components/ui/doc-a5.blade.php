@props([
    'padding' => '0',
])

{{-- A5 portrait preview (148×210mm ≈ 559×794px) — certificates & receipts. Scales to fit. --}}
<div x-data="docScale(559)" class="mx-auto w-full max-w-[559px] overflow-hidden" :style="`height: ${794 * scale}px`">
    <div :style="`transform: scale(${scale}); transform-origin: top left`"
         style="width: 559px; height: 794px; padding: {{ $padding }}"
         {{ $attributes->merge(['class' => 'relative overflow-hidden bg-white text-[#1A1D21] shadow-[0_4px_16px_rgba(0,0,0,.12)]']) }}>
        {{ $slot }}
    </div>
</div>
