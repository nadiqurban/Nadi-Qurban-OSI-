@props([
    'name' => null,
    'title' => null,
    'subtitle' => null,
    'side' => 'right',
    'width' => '440px',
])

{{-- Side panel (e.g. Audit Log detail). Full width on phones. --}}
<div x-data="{ open: false }"
     x-modelable="open"
     {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
     @if ($name)
         @open-drawer.window="if ($event.detail === @js($name)) open = true"
         @close-drawer.window="if ($event.detail === @js($name) || ! $event.detail) open = false"
     @endif
     @keydown.escape.window="open = false"
     x-cloak x-show="open" class="fixed inset-0 z-[115]" role="dialog" aria-modal="true">
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-[rgba(20,24,20,.55)]" @click="open = false"></div>
    <aside x-show="open"
           x-transition:enter="transition duration-200 ease-out"
           x-transition:enter-start="{{ $side === 'left' ? '-translate-x-full' : 'translate-x-full' }}"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition duration-150 ease-in"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="{{ $side === 'left' ? '-translate-x-full' : 'translate-x-full' }}"
           style="--drawer-w: {{ $width }}"
           class="absolute inset-y-0 {{ $side === 'left' ? 'left-0' : 'right-0' }} flex w-full flex-col bg-surface shadow-modal md:w-(--drawer-w)">
        <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
            <div class="min-w-0">
                <h2 class="truncate text-[16px] font-bold text-ink">{{ $title }}</h2>
                @if ($subtitle)<p class="mt-0.5 text-[12px] text-faint">{{ $subtitle }}</p>@endif
            </div>
            <button type="button" @click="open = false" class="flex size-11 items-center justify-center rounded-[8px] border border-border text-muted md:size-8" aria-label="Tutup"><i class="ph ph-x text-[16px]"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-5">{{ $slot }}</div>
        @isset($footer)
            <div class="flex gap-[10px] border-t border-border px-5 pt-3 pb-safe">{{ $footer }}</div>
        @endisset
    </aside>
</div>
