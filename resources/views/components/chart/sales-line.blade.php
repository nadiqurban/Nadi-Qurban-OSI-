@props([
    'rows' => [],          // [['label' => 'Jan', 'value' => 180.0], …] RM '000
    'target' => null,      // monthly target, same unit (dashed gold line)
    'w' => 720, 'h' => 260, 'padL' => 44, 'padB' => 30, 'padT' => 14, 'padR' => 14,
])

@php
    // Geometry from Dashboard Operasi.dc.html salesChart: 4 gridlines, x at slot centres,
    // area polygon under the line, dashed target polyline, white-filled points r=4.
    $values = array_column($rows, 'value');
    $max = max(1, $target ?? 0, ...($values ?: [0]));
    $step = $max >= 20 ? max(25, (int) ceil($max * 1.15 / 4 / 50) * 50) : max(1, (int) ceil($max * 1.15 / 4));
    $yMax = $step * 4;
    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;
    $n = max(1, count($rows));
    $x = fn (int $i) => round($padL + $plotW * ($i + 0.5) / $n, 2);
    $y = fn (float $v) => round($padT + $plotH - $plotH * $v / $yMax, 2);
    $pts = implode(' ', array_map(fn ($r, $i) => $x($i).','.$y($r['value']), $rows, array_keys($rows)));
    $area = $padL.','.$y(0).' '.$pts.' '.($padL + $plotW).','.$y(0);
    $tgt = $target !== null ? implode(' ', array_map(fn ($i) => $x($i).','.$y($target), array_keys($rows))) : null;
@endphp

<svg viewBox="0 0 {{ $w }} {{ $h }}" {{ $attributes->merge(['class' => 'mt-1.5 block h-auto w-full']) }} role="img" aria-label="Carta jualan bulanan">
    @for ($t = 0; $t <= $yMax; $t += $step)
        <line x1="{{ $padL }}" y1="{{ $y($t) }}" x2="{{ $w - $padR }}" y2="{{ $y($t) }}" stroke-width="1" class="stroke-[#eef0e4] dark:stroke-[#2c2e1c]" />
        <text x="{{ $padL - 10 }}" y="{{ $y($t) + 4 }}" text-anchor="end" font-size="11" font-family="Inter,sans-serif" class="fill-[#94A3AC] dark:fill-[#7C8E86]">{{ $t }}</text>
    @endfor
    @foreach ($rows as $i => $r)
        <text x="{{ $x($i) }}" y="{{ $h - $padB + 18 }}" text-anchor="middle" font-size="11" font-family="Inter,sans-serif" class="fill-[#94A3AC] dark:fill-[#7C8E86]">{{ $r['label'] }}</text>
    @endforeach
    @if ($rows)
        <polygon points="{{ $area }}" class="fill-[#42481c] opacity-[.08] dark:fill-[#a9b46b] dark:opacity-[.14]" />
        @if ($tgt)
            <polyline points="{{ $tgt }}" fill="none" stroke="#C9A227" stroke-width="2" stroke-dasharray="5 4" />
        @endif
        <polyline points="{{ $pts }}" fill="none" stroke-width="2.6" stroke-linejoin="round" stroke-linecap="round" class="stroke-[#42481c] dark:stroke-[#a9b46b]" />
        @foreach ($rows as $i => $r)
            <circle cx="{{ $x($i) }}" cy="{{ $y($r['value']) }}" r="4" stroke-width="2.4" class="fill-surface stroke-[#42481c] dark:stroke-[#a9b46b]"><title>{{ $r['label'] }} · RM {{ number_format($r['value'], 1) }}k</title></circle>
        @endforeach
    @endif
</svg>
