{{-- Global search / ⌘K palette shell. Results wiring comes in Phase 9 (Livewire command palette). --}}
<div x-data x-cloak x-show="$store.ui.search" x-transition.opacity
     @keydown.escape.window="$store.ui.search = false"
     class="fixed inset-0 z-[130] flex items-start justify-center bg-[rgba(20,24,20,.55)] md:px-4 md:pt-[12vh]"
     role="dialog" aria-modal="true" aria-label="Carian">
    <div @click.outside="$store.ui.search = false"
         class="flex h-full w-full flex-col overflow-hidden bg-surface shadow-modal md:h-auto md:max-w-[620px] md:rounded-[14px]">
        <div class="flex items-center gap-[11px] border-b border-border px-4 py-3 md:px-5">
            <i class="ph ph-magnifying-glass text-[20px] text-faint"></i>
            <input type="search" x-ref="q" x-effect="$store.ui.search && $nextTick(() => $refs.q.focus())"
                   placeholder="Cari tempahan, pelanggan, vendor, no. tracking…"
                   class="min-h-11 flex-1 bg-transparent text-[15px] text-ink outline-none placeholder:text-faint">
            <button type="button" @click="$store.ui.search = false"
                    class="flex size-11 items-center justify-center rounded-lg border border-border text-muted md:size-8" aria-label="Tutup carian">
                <i class="ph ph-x text-[16px]"></i>
            </button>
        </div>
        <div class="flex flex-1 flex-col items-center justify-center px-6 py-12 text-center md:flex-none">
            <div class="mx-auto mb-3 flex size-[52px] items-center justify-center rounded-[13px] bg-neutral-soft text-faint"><i class="ph ph-magnifying-glass text-[26px]"></i></div>
            <div class="text-[14px] font-semibold text-ink-2">Mula menaip untuk mencari</div>
            <p class="mt-[5px] text-[12.5px] text-faint">No. tempahan, no. tracking, nama atau telefon pelanggan.</p>
        </div>
    </div>
</div>
