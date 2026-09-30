@props([
    'label' => null,        // shown above the value in the phone card
    'align' => 'left',      // left | center | right (desktop alignment)
    'span' => false,        // phone card: take the full row (title, actions)
    'mobile' => 'show',     // show | hide (hidden in the phone card)
])

@php
    $alignClass = ['center' => 'md:text-center md:justify-self-center', 'right' => 'md:text-right md:justify-self-end'][$align] ?? '';
@endphp

<div {{ $attributes->class([
    'min-w-0',
    $alignClass,
    'max-md:col-span-2' => $span,
    'max-md:hidden' => $mobile === 'hide',
]) }}>
    @if ($label)
        <div class="mb-1 text-[10.5px] font-bold tracking-[.4px] text-faint uppercase md:hidden">{{ $label }}</div>
    @endif
    {{ $slot }}
</div>
