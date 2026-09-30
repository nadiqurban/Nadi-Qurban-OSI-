@props([
    'name' => null,          // open with $dispatch('open-modal', 'name'); close with 'close-modal'
    'title' => null,
    'subtitle' => null,
    'icon' => null,          // Phosphor name for the 36px tinted header icon
    'tone' => 'primary',
    'maxWidth' => '480px',   // design max-width per modal (480 / 560 / 640 / 760 …)
    'show' => false,
])

{{--
    Desktop: centred card (radius 14, shadow 0 24px 60px rgba(0,0,0,.3)) on
    overlay rgba(20,24,20,.55), scrolls with the page (align top, padding 32px 16px).
    Phones: full-screen sheet with sticky header and sticky footer.
    Bind with wire:model="showX" or x-model (x-modelable="open").
--}}
<div x-data="{ open: @js((bool) $show) }"
     x-modelable="open"
     {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
     @if ($name)
         @open-modal.window="if ($event.detail === @js($name)) open = true"
         @close-modal.window="if ($event.detail === @js($name) || ! $event.detail) open = false"
     @endif
     @keydown.escape.window="open = false"
     x-effect="document.body.classList.toggle('overflow-hidden', open)"
     x-cloak x-show="open"
     class="fixed inset-0 z-[120]"
     role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-[rgba(20,24,20,.55)]" @click="open = false"></div>

    <div class="relative flex h-full items-start justify-center overflow-y-auto md:px-4 md:py-8" @click.self="open = false">
        <div x-show="open"
             x-transition:enter="transition duration-200 ease-out"
             x-transition:enter-start="translate-y-6 opacity-0 md:translate-y-2"
             x-transition:enter-end="translate-y-0 opacity-100"
             style="--modal-max: {{ $maxWidth }}"
             {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->merge(['class' => 'flex w-full flex-col overflow-hidden bg-surface max-md:min-h-full md:max-w-(--modal-max) md:rounded-[14px] md:shadow-modal']) }}>
            @if ($title)
                <div class="sticky top-0 z-10 flex items-center justify-between gap-3 border-b border-border bg-surface px-4 py-[14px] md:static md:px-6 md:py-[18px]">
                    <div class="flex min-w-0 items-center gap-[11px]">
                        @if ($icon)
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[19px]"></i></span>
                        @endif
                        <div class="min-w-0">
                            <h2 class="truncate text-[16px] font-bold text-ink">{{ $title }}</h2>
                            @if ($subtitle)
                                <p class="mt-0.5 text-[12px] text-faint">{{ $subtitle }}</p>
                            @endif
                        </div>
                    </div>
                    <button type="button" @click="open = false"
                            class="flex size-11 shrink-0 items-center justify-center rounded-[8px] border border-border text-muted md:size-8" aria-label="Tutup">
                        <i class="ph ph-x text-[16px]"></i>
                    </button>
                </div>
            @endif

            <div class="flex-1 px-4 py-5 md:px-6 md:py-[22px]">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="sticky bottom-0 flex flex-wrap items-center justify-end gap-[10px] border-t border-border bg-surface px-4 pt-3 pb-safe max-md:[&>*]:flex-1 md:static md:px-6 md:py-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
