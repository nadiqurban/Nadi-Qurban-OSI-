{{-- ⌘K palette body: ↑/↓ to move, Enter to open, Esc to close. --}}
<div x-data="{ active: 0 }"
     x-init="$watch('$wire.q', () => active = 0)"
     @keydown.arrow-down.prevent="active = Math.min(active + 1, {{ max(0, count($results) - 1) }}); $nextTick(() => $root.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }))"
     @keydown.arrow-up.prevent="active = Math.max(active - 1, 0); $nextTick(() => $root.querySelector('[data-active=true]')?.scrollIntoView({ block: 'nearest' }))"
     @keydown.enter.prevent="$root.querySelector('[data-active=true]')?.click()"
     class="flex min-h-0 flex-1 flex-col">
    <div class="flex items-center gap-[11px] border-b border-border px-4 py-3 md:px-5">
        <i class="ph ph-magnifying-glass text-[20px] text-faint"></i>
        <input type="search" id="global-search" x-ref="q" wire:model.live.debounce.250ms="q" autocomplete="off"
               x-effect="$store.ui.search && $nextTick(() => $refs.q.focus())"
               placeholder="Cari tempahan, pelanggan, vendor, no. tracking…" aria-label="Carian global"
               class="min-h-11 flex-1 bg-transparent text-[15px] text-ink outline-none placeholder:text-faint max-md:text-[16px]">
        <span wire:loading wire:target="q" class="text-[12px] text-faint">Mencari…</span>
        <button type="button" @click="$store.ui.search = false"
                class="flex size-11 items-center justify-center rounded-lg border border-border text-muted md:size-8" aria-label="Tutup carian">
            <i class="ph ph-x text-[16px]"></i>
        </button>
    </div>

    @if (mb_strlen(trim($q)) < 2)
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center md:flex-none">
            <div class="mx-auto mb-3 flex size-[52px] items-center justify-center rounded-[13px] bg-neutral-soft text-faint"><i class="ph ph-magnifying-glass text-[26px]"></i></div>
            <div class="text-[14px] font-semibold text-ink-2">Mula menaip untuk mencari</div>
            <p class="mt-[5px] text-[12.5px] text-faint">No. tempahan, no. tracking, nama atau telefon pelanggan, vendor, invois, lead.</p>
        </div>
    @elseif ($results === [])
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center md:flex-none">
            <div class="mx-auto mb-3 flex size-[52px] items-center justify-center rounded-[13px] bg-neutral-soft text-faint"><i class="ph ph-magnifying-glass-minus text-[26px]"></i></div>
            <div class="text-[14px] font-semibold text-ink-2">Tiada hasil untuk “{{ $q }}”</div>
        </div>
    @else
        <div class="min-h-0 flex-1 overflow-y-auto py-2 md:max-h-[60vh]" role="listbox" aria-label="Hasil carian">
            @foreach (collect($results)->groupBy('group') as $group => $items)
                <div class="px-5 pt-2 pb-1 text-[10.5px] font-bold tracking-[.6px] text-faint uppercase">{{ $group }}</div>
                @foreach ($items as $r)
                    @php $i = array_search($r, $results, true); @endphp
                    <a href="{{ $r['url'] }}" wire:navigate @click="$store.ui.search = false" role="option"
                       :data-active="active === {{ $i }}" :aria-selected="active === {{ $i }}" @mouseenter="active = {{ $i }}"
                       :class="active === {{ $i }} ? 'bg-primary-soft' : ''"
                       class="mx-2 flex min-h-11 items-center gap-3 rounded-[9px] px-3 py-2">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-[8px] bg-bg text-primary dark:text-[#c9ce93]"><i class="ph ph-{{ $r['icon'] }} text-[16px]"></i></span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-[13.5px] font-semibold text-ink">{{ $r['title'] }}</span>
                            <span class="block truncate text-[12px] text-muted">{{ $r['subtitle'] }}</span>
                        </span>
                        <i class="ph ph-arrow-elbow-down-left text-[14px] text-faint" x-show="active === {{ $i }}"></i>
                    </a>
                @endforeach
            @endforeach
        </div>
        <div class="hidden items-center gap-4 border-t border-border px-5 py-2.5 text-[11px] text-faint md:flex">
            <span><kbd class="rounded border border-border px-1">↑</kbd> <kbd class="rounded border border-border px-1">↓</kbd> pilih</span>
            <span><kbd class="rounded border border-border px-1">Enter</kbd> buka</span>
            <span><kbd class="rounded border border-border px-1">Esc</kbd> tutup</span>
        </div>
    @endif
</div>
