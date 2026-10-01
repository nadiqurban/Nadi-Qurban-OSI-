{{-- Global search / ⌘K palette (Livewire GlobalSearch). Full screen on phones. --}}
<div x-data x-cloak x-show="$store.ui.search" x-transition.opacity
     @keydown.escape.window="$store.ui.search = false"
     class="fixed inset-0 z-[130] flex items-start justify-center bg-[rgba(20,24,20,.55)] md:px-4 md:pt-[12vh]"
     role="dialog" aria-modal="true" aria-label="Carian">
    <div @click.outside="$store.ui.search = false"
         class="flex h-full w-full flex-col overflow-hidden bg-surface shadow-modal md:h-auto md:max-w-[620px] md:rounded-[14px]">
        <livewire:global-search />
    </div>
</div>
