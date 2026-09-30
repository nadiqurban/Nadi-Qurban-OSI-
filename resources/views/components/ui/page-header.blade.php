@props([
    'title',
    'subtitle' => null,
    'breadcrumb' => [],   // ['Operasi', 'Tempahan & Pelanggan'] — last item highlighted
])

{{--
    Breadcrumb 12.5px + H1 23px/700 + actions (design). Actions wrap below the
    title on tablet; on phones the primary action (use mobileBlock) goes full width.
--}}
<div {{ $attributes->merge(['class' => 'mb-5 flex flex-wrap items-end gap-3 md:mb-6 md:gap-4']) }}>
    <div class="mr-auto min-w-0 max-md:w-full">
        @if ($breadcrumb)
            <div class="mb-1.5 flex flex-wrap items-center gap-2 text-[12.5px] text-faint">
                @foreach ($breadcrumb as $crumb)
                    @if (! $loop->first)<i class="ph ph-caret-right text-[12px]"></i>@endif
                    <span @class(['font-semibold text-primary dark:text-[#c9ce93]' => $loop->last])>{{ $crumb }}</span>
                @endforeach
            </div>
        @endif
        <h1 class="text-[20px] font-bold text-ink md:text-[23px]">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-[13.5px] text-muted">{{ $subtitle }}</p>
        @endif
        @isset($note)
            <div class="mt-2.5">{{ $note }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2 max-md:w-full">
            {{ $actions }}
        </div>
    @endisset
</div>
