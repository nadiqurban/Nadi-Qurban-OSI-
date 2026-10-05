<div wire:poll.30s x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" id="bell" @click="open = !open" :aria-expanded="open"
            class="relative flex size-11 items-center justify-center rounded-[10px] border border-border text-muted lg:size-10" aria-label="Notifikasi">
        <i class="ph ph-bell text-[20px] leading-none"></i>
        @if ($unread > 0)
            <span class="absolute -top-1 -right-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-[20px] border-2 border-white bg-danger px-1 text-[10px] font-bold text-white dark:border-surface">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </button>
    <div x-cloak x-show="open" x-transition.origin.top.right
         class="fixed inset-x-3 top-[76px] z-40 overflow-hidden rounded-[12px] border border-border bg-surface shadow-pop sm:absolute sm:inset-x-auto sm:top-12 sm:right-0 sm:w-[360px]">
        <div class="flex items-center justify-between border-b border-divider px-4 py-3">
            <span class="text-[14px] font-bold text-ink">Notifikasi @if ($unread > 0)<span class="ml-1 rounded-[20px] bg-primary-soft px-2 py-px text-[11px] font-bold text-primary dark:text-[#c9ce93]">{{ $unread }}</span>@endif</span>
            @if ($unread > 0)
                <button type="button" wire:click="markAllRead" class="text-[12px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">Tanda semua dibaca</button>
            @endif
        </div>
        <div class="max-h-[60vh] overflow-y-auto">
            @forelse ($latest as $n)
                @php [$icon, $tone] = \App\Enums\NotificationType::categories()[$n->data['category'] ?? 'Sistem'] ?? ['bell', 'neutral']; @endphp
                <button type="button" wire:key="bell-{{ $n->id }}" wire:click="open('{{ $n->id }}')" @class(['flex w-full items-start gap-3 border-b border-divider px-4 py-3 text-left hover:bg-bg', 'bg-[#fbfcf9] dark:bg-bg' => $n->read_at === null])>
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[18px]"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-1.5 text-[13px] font-bold text-ink">{{ $n->data['title'] ?? '' }}@if ($n->read_at === null)<span class="size-1.5 shrink-0 rounded-full bg-primary"></span>@endif</span>
                        <span class="mt-0.5 line-clamp-2 block text-[12px] leading-[1.45] text-muted">{{ $n->data['body'] ?? '' }}</span>
                        <span class="mt-1 block text-[11px] text-faint">{{ masa_lalu($n->created_at) }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-8 text-center text-[12.5px] text-faint">Tiada notifikasi.</div>
            @endforelse
        </div>
        @if ($canPage)
            <a href="{{ route('notifications.index') }}" wire:navigate class="flex min-h-11 items-center justify-center gap-1.5 bg-bg px-4 py-3 text-[12.5px] font-semibold text-primary dark:text-[#c9ce93]">Lihat semua notifikasi <i class="ph ph-arrow-right text-[14px]"></i></a>
        @endif
    </div>
</div>
