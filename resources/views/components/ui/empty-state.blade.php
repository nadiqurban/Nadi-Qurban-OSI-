@props([
    'icon' => 'tray',
    'title' => 'Tiada rekod',
])

<div {{ $attributes->merge(['class' => 'px-6 py-11 text-center']) }}>
    <div class="mx-auto mb-3 flex size-[52px] items-center justify-center rounded-[13px] bg-neutral-soft text-faint">
        <i class="ph ph-{{ $icon }} text-[26px]"></i>
    </div>
    <div class="text-[14px] font-semibold text-ink-2">{{ $title }}</div>
    @if (trim($slot) !== '')
        <p class="mt-[5px] text-[12.5px] text-faint">{{ $slot }}</p>
    @endif
</div>
