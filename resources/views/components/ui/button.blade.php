@props([
    'variant' => 'primary',   // primary | secondary | success | gold | gold-outline | danger | danger-soft | soft | ghost | on-dark
    'size' => 'md',           // md (11px 18px, 13.5px) | sm (8px 13px, 12.5px)
    'icon' => null,           // Phosphor name, e.g. "plus" or "fill check-circle"
    'iconRight' => null,
    'href' => null,
    'type' => 'button',
    'iconOnly' => false,
    'block' => false,         // full width
    'mobileBlock' => false,   // full width below md (primary page action)
])

@php
    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-hover hover:text-white',
        'secondary' => 'border border-border bg-surface text-ink-2 hover:bg-bg hover:text-ink-2',
        'success' => 'bg-success text-white hover:brightness-95 hover:text-white',
        'gold' => 'bg-gold text-white hover:brightness-95 hover:text-white',
        'gold-outline' => 'border border-gold bg-surface text-gold hover:bg-gold-soft hover:text-gold',
        'danger' => 'bg-danger text-white hover:brightness-95 hover:text-white',
        'danger-soft' => 'bg-danger-soft text-danger hover:text-danger',
        'soft' => 'bg-primary-soft text-primary hover:text-primary',
        'ghost' => 'text-ink-3 hover:bg-bg hover:text-ink-3',
        'on-dark' => 'bg-white/15 text-white hover:bg-white/25 hover:text-white',
    ];
    $sizes = $iconOnly
        ? ['md' => 'size-11 md:size-[38px] rounded-[9px]', 'sm' => 'size-11 md:size-[30px] rounded-[8px]']
        : ['md' => 'gap-2 rounded-[9px] px-[18px] py-[11px] text-[13.5px] max-md:min-h-11', 'sm' => 'gap-1.5 rounded-[8px] px-[13px] py-2 text-[12.5px] max-md:min-h-10'];
    $iconSize = $size === 'sm' ? 'text-[15px]' : 'text-[17px]';
    $iconClass = fn (string $name) => (str_starts_with($name, 'fill ') ? 'ph-fill ph-'.substr($name, 5) : 'ph ph-'.$name).' leading-none '.$iconSize;

    $classes = implode(' ', [
        'inline-flex shrink-0 items-center justify-center font-semibold whitespace-nowrap transition-colors disabled:cursor-not-allowed disabled:opacity-50',
        $variants[$variant] ?? $variants['primary'],
        $sizes[$size] ?? $sizes['md'],
        $block ? 'w-full' : '',
        $mobileBlock ? 'max-md:w-full' : '',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<i class="{{ $iconClass($icon) }}"></i>@endif
        {{ $slot }}
        @if ($iconRight)<i class="{{ $iconClass($iconRight) }}"></i>@endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<i class="{{ $iconClass($icon) }}"></i>@endif
        {{ $slot }}
        @if ($iconRight)<i class="{{ $iconClass($iconRight) }}"></i>@endif
    </button>
@endif
