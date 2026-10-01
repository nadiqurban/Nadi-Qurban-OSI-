@props([
    'slices' => [],        // [['value' => 46, 'color' => '#42481c'], …]
    'size' => 150,
    'thickness' => 28,
    'title' => null,       // centre text (18px/700)
    'caption' => null,     // centre caption (10.5px)
])

@php
    // Arc maths from Kewangan.dc.html methodDonut (starts at 12 o'clock, clockwise).
    $r = $size / 2;
    $in = $r - $thickness;
    $total = array_sum(array_column($slices, 'value'));
    $a0 = -M_PI / 2;
    $paths = [];
    foreach ($slices as $s) {
        if ($total <= 0 || $s['value'] <= 0) { continue; }
        $frac = $s['value'] / $total;
        if ($frac >= 0.9999) { $frac = 0.9999; }
        $a1 = $a0 + $frac * M_PI * 2;
        $large = $frac > 0.5 ? 1 : 0;
        $pt = fn (float $ang, float $rad) => round($r + $rad * cos($ang), 3).' '.round($r + $rad * sin($ang), 3);
        $paths[] = ['d' => 'M'.$pt($a0, $r).' A'.$r.' '.$r.' 0 '.$large.' 1 '.$pt($a1, $r).' L'.$pt($a1, $in).' A'.$in.' '.$in.' 0 '.$large.' 0 '.$pt($a0, $in).' Z', 'color' => $s['color']];
        $a0 = $a1;
    }
@endphp

<svg viewBox="0 0 {{ $size }} {{ $size }}" width="{{ $size }}" height="{{ $size }}" {{ $attributes->merge(['class' => 'block']) }} role="img" aria-label="{{ $title }} {{ $caption }}">
    @if ($paths === [])
        <circle cx="{{ $r }}" cy="{{ $r }}" r="{{ $r - $thickness / 2 }}" fill="none" stroke="#F1F5F4" stroke-width="{{ $thickness }}" />
    @endif
    @foreach ($paths as $p)
        <path d="{{ $p['d'] }}" fill="{{ $p['color'] }}" />
    @endforeach
    @if ($title)
        <text x="{{ $r }}" y="{{ $r - 3 }}" text-anchor="middle" font-size="18" font-weight="700" fill="currentColor" font-family="Inter,sans-serif" class="text-ink">{{ $title }}</text>
    @endif
    @if ($caption)
        <text x="{{ $r }}" y="{{ $r + 15 }}" text-anchor="middle" font-size="10.5" fill="#64748B" font-family="Inter,sans-serif">{{ $caption }}</text>
    @endif
</svg>
