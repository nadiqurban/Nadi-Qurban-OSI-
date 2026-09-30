@props([
    'cols',                  // CSS grid-template-columns from the design, e.g. "36px 1.4fr 1.5fr 0.9fr …"
    'minWidth' => null,      // inner min-width on md+ (design uses e.g. 1340px) → horizontal scroll
    'scroll' => false,       // very wide tables: keep grid on phones too (horizontal scroll + sticky first column)
])

{{--
    Desktop/tablet (≥md): CSS grid identical to the design (header 11px/700 uppercase,
    tracking .4px, #94A3AC on #F6F8F7; rows 14px 20px, border-bottom #F1F5F4),
    horizontally scrollable inside the card when narrower than min-width.
    Phones (<md): each <x-ui.tr> becomes a stacked card unless `scroll` is set.
    Use <x-ui.td label="…"> so cards can label each value.
--}}
<div style="--cols: {{ $cols }}; --tbl-min: {{ $minWidth ?? 'auto' }}"
     {{ $attributes->merge(['class' => 'overflow-hidden rounded-[12px] border border-border bg-surface']) }}
     data-table-mode="{{ $scroll ? 'scroll' : 'stack' }}">
    @isset($toolbar)
        {{-- Search/filter row inside the card (Pengguna & Peranan.dc.html): 14px 20px, border-bottom --}}
        <div class="flex flex-wrap items-center gap-3 border-b border-border px-4 py-[14px] md:px-5">
            {{ $toolbar }}
        </div>
    @endisset
    <div @class(['overflow-x-auto' => true, 'max-md:overflow-x-visible' => ! $scroll])>
        <div @class(['md:min-w-(--tbl-min)', 'max-md:min-w-(--tbl-min)' => $scroll])>
            @isset($head)
                <div @class([
                    'grid-cols-(--cols) gap-[14px] border-b border-border bg-head px-5 py-[13px] text-[11px] font-bold tracking-[.4px] text-faint uppercase',
                    'grid' => $scroll,
                    'hidden md:grid' => ! $scroll,
                    '[&>*:first-child]:sticky [&>*:first-child]:left-0 [&>*:first-child]:z-[1] [&>*:first-child]:bg-head' => $scroll,
                ])>
                    {{ $head }}
                </div>
            @endisset

            <div @class(['max-md:flex max-md:flex-col max-md:gap-3 max-md:bg-bg max-md:p-3' => ! $scroll]) @if ($scroll) data-scroll @endif>
                {{ $slot }}
            </div>
        </div>
    </div>

    @isset($empty)
        {{ $empty }}
    @endisset

    @isset($footer)
        <div class="flex flex-col items-center justify-between gap-3 border-t border-border px-4 py-[14px] sm:flex-row md:px-5">
            {{ $footer }}
        </div>
    @endisset
</div>
