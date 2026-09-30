@props([
    'tone' => 'neutral',
    'variant' => 'pill',   // pill (20px radius, 11.5px/600, 4px 12px) | tag (6px radius, 10.5px/700) | label (5px radius, 10px/700 uppercase-ish)
    'icon' => null,
])

@php
    $shape = [
        'pill' => 'rounded-[20px] px-3 py-1 text-[11.5px] font-semibold',
        'tag' => 'rounded-[6px] px-2 py-[3px] text-[10.5px] font-bold',
        'label' => 'rounded-[5px] px-[7px] py-0.5 text-[10px] font-bold',
    ][$variant] ?? '';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 whitespace-nowrap '.$shape.' '.\App\Support\Tone::classes($tone)]) }}>
    @if ($icon)<i class="ph ph-{{ $icon }} text-[11px]"></i>@endif
    {{ $slot }}
</span>
