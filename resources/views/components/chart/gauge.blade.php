@props([
    'pct' => 0,        // 0–100
    'size' => 180,
    'thickness' => 18,
])

@php
    // Dashboard Operasi.dc.html gauge: ring rotated -90°, round cap, dashoffset = circ × (1 − pct).
    $r = $size / 2;
    $rad = $r - $thickness / 2;
    $circ = 2 * M_PI * $rad;
    $offset = $circ * (1 - max(0, min(100, $pct)) / 100);
@endphp

<svg viewBox="0 0 {{ $size }} {{ $size }}" width="{{ $size }}" height="{{ $size }}" {{ $attributes->merge(['class' => 'block -rotate-90']) }} role="img" aria-label="{{ $pct }}% tercapai">
    <circle cx="{{ $r }}" cy="{{ $r }}" r="{{ $rad }}" fill="none" stroke-width="{{ $thickness }}" class="stroke-[#eef0e4] dark:stroke-[#2c2e1c]" />
    @if ($pct > 0)
        <circle cx="{{ $r }}" cy="{{ $r }}" r="{{ $rad }}" fill="none" stroke-width="{{ $thickness }}" stroke-linecap="round"
                stroke-dasharray="{{ round($circ, 3) }}" stroke-dashoffset="{{ round($offset, 3) }}" class="stroke-[#42481c] dark:stroke-[#818e3f]" />
    @endif
</svg>
