@props([
    'name' => '',
    'size' => 38,
    'tone' => null,        // null = stable colour from name (design avPairs); 'brand' = olive + gold (current user)
    'shape' => 'circle',   // circle | rounded
])

@php
    $isBrand = $tone === 'brand';
    $tone ??= \App\Support\Tone::avatarFor($name);
    $colour = $isBrand ? 'bg-primary text-gold' : \App\Support\Tone::classes($tone);
    $fontSize = $size >= 44 ? 15 : ($size >= 36 ? 13 : 11);
@endphp

<span style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ $fontSize }}px"
      {{ $attributes->class([
          'inline-flex shrink-0 items-center justify-center font-extrabold',
          'rounded-full' => $shape === 'circle',
          'rounded-[10px]' => $shape !== 'circle',
          $colour,
      ]) }}>{{ initials($name) }}</span>
