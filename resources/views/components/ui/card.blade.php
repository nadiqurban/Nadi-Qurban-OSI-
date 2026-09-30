@props([
    'padding' => 'p-[18px] md:px-6 md:py-[22px]',
    'title' => null,
    'subtitle' => null,
])

<section {{ $attributes->merge(['class' => 'min-w-0 rounded-[12px] border border-border bg-surface shadow-[0_1px_2px_rgba(16,24,40,.04)] '.$padding]) }}>
    @if ($title || isset($actions))
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-[10px]">
                {{ $leading ?? '' }}
                <div>
                    <h2 class="text-[15.5px] font-bold text-ink">{{ $title }}</h2>
                    @if ($subtitle)
                        <p class="mt-[3px] text-[12.5px] text-muted">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            {{ $actions ?? '' }}
        </div>
    @endif
    {{ $slot }}
</section>
