@props([
    'scroll' => false,   // must match the parent data-table's `scroll`
])

<div {{ $attributes->class([
    'grid-cols-(--cols) items-center gap-[14px] border-b border-divider px-5 py-[14px] text-[13px]',
    'grid' => $scroll,
    '[&>*:first-child]:sticky [&>*:first-child]:left-0 [&>*:first-child]:z-[1] [&>*:first-child]:bg-surface' => $scroll,
    // stacked card on phones
    'max-md:grid max-md:grid-cols-2 max-md:gap-x-4 max-md:gap-y-3 max-md:rounded-[12px] max-md:border max-md:border-border max-md:bg-surface max-md:p-4 md:grid' => ! $scroll,
]) }}>
    {{ $slot }}
</div>
