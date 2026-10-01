@props([
    'data' => [],                              // [['label' => 'Jan', 'in' => 280, 'out' => 160], …]
    'colors' => ['#42481c', '#C9A227'],
    'w' => 720, 'h' => 250, 'padL' => 44, 'padB' => 30, 'padT' => 14, 'padR' => 14,
])

@php
    // Geometry from Kewangan.dc.html cashChart: 4 gridlines, bar width = slot × 0.26, 2px gap.
    $max = max(1, ...array_map(fn ($d) => max($d['in'], $d['out']), $data ?: [['in' => 0, 'out' => 0]]));
    $step = max(25, (int) ceil($max * 1.15 / 4 / 25) * 25);
    if ($max < 20) { $step = max(1, (int) ceil($max * 1.15 / 4)); }
    $yMax = $step * 4;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $n = max(1, count($data));
    $slot = $plotW / $n;
    $bw = $slot * 0.26;
    $y = fn (float $v) => $padT + $plotH - $plotH * $v / $yMax;
    $base = $padT + $plotH;
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" {{ $attributes->merge(['class' => 'mt-2 block h-auto w-full']) }} role="img" aria-label="Carta aliran tunai">
    @for ($t = 0; $t <= $yMax; $t += $step)
        <line x1="{{ $padL }}" y1="{{ $y($t) }}" x2="{{ $w - $padR }}" y2="{{ $y($t) }}" stroke="#EEF0E4" stroke-width="1" class="dark:stroke-[#2a302a]" />
        <text x="{{ $padL - 10 }}" y="{{ $y($t) + 4 }}" text-anchor="end" font-size="11" fill="#94A3AC" font-family="Inter,sans-serif">{{ $t }}</text>
    @endfor
    @foreach ($data as $i => $d)
        @php $cx = $padL + $slot * ($i + 0.5); $yi = $y($d['in']); $yo = $y($d['out']); @endphp
        <rect x="{{ $cx - $bw - 2 }}" y="{{ $yi }}" width="{{ $bw }}" height="{{ max(0, $base - $yi) }}" rx="3" fill="{{ $colors[0] }}"><title>{{ $d['label'] }} · Kutipan RM {{ number_format($d['in'], 1) }}k</title></rect>
        <rect x="{{ $cx + 2 }}" y="{{ $yo }}" width="{{ $bw }}" height="{{ max(0, $base - $yo) }}" rx="3" fill="{{ $colors[1] }}"><title>{{ $d['label'] }} · Bayaran RM {{ number_format($d['out'], 1) }}k</title></rect>
        <text x="{{ $cx }}" y="{{ $h - $padB + 18 }}" text-anchor="middle" font-size="11" fill="#94A3AC" font-family="Inter,sans-serif">{{ $d['label'] }}</text>
    @endforeach
</svg>
