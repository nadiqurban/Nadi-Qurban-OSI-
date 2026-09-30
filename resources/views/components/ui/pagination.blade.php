@props([
    'paginator' => null,   // LengthAwarePaginator; omit to render a static demo
    'noun' => 'rekod',     // "tempahan", "pelanggan"…
])

@php
    if ($paginator) {
        $from = $paginator->firstItem() ?? 0;
        $to = $paginator->lastItem() ?? 0;
        $total = $paginator->total();
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
    } else {
        [$from, $to, $total, $current, $last] = [1, 9, 248, 1, 4];
    }
    $start = max(1, min($current - 1, $last - 3));
    $pages = range($start, min($last, $start + 3));
    $btn = 'flex size-11 items-center justify-center rounded-[8px] text-[13px] font-semibold md:size-[34px]';
@endphp

<span class="text-[13px] text-muted">Memaparkan {{ $from }}–{{ $to }} daripada {{ number_format($total) }} {{ $noun }}</span>
<nav class="flex items-center gap-1.5" aria-label="Halaman">
    <button type="button" @if ($paginator) wire:click="previousPage" @endif @disabled($current <= 1)
            class="{{ $btn }} border border-border text-faint disabled:opacity-50" aria-label="Sebelum"><i class="ph ph-caret-left text-[15px]"></i></button>
    @foreach ($pages as $n)
        <button type="button" @if ($paginator) wire:click="gotoPage({{ $n }})" @endif
                @class([$btn, 'bg-primary text-white' => $n === $current, 'border border-border text-ink-2' => $n !== $current])
                @if ($n === $current) aria-current="page" @endif>{{ $n }}</button>
    @endforeach
    <button type="button" @if ($paginator) wire:click="nextPage" @endif @disabled($current >= $last)
            class="{{ $btn }} border border-border text-ink-2 disabled:opacity-50" aria-label="Seterusnya"><i class="ph ph-caret-right text-[15px]"></i></button>
</nav>
