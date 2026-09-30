{{--
    Toasts: $this->dispatch('toast', message: '…', tone: 'success|danger|info') from Livewire,
    or session()->flash('toast', '…') before a redirect.
--}}
<div x-data="{
        items: [],
        push(detail) {
            const id = Date.now() + Math.random();
            this.items.push({ id, message: detail.message ?? detail, tone: detail.tone ?? 'success' });
            setTimeout(() => this.items = this.items.filter(i => i.id !== id), 4500);
        },
     }"
     x-init="@if (session('toast')) push(@js(['message' => session('toast'), 'tone' => session('toast_tone', 'success')])) @endif"
     @toast.window="push($event.detail)"
     class="pointer-events-none fixed inset-x-3 bottom-3 z-[140] flex flex-col items-center gap-2 md:inset-x-auto md:right-6 md:bottom-6 md:items-end"
     aria-live="polite">
    <template x-for="item in items" :key="item.id">
        <div x-transition.opacity
             class="pointer-events-auto flex max-w-[420px] items-start gap-[10px] rounded-[10px] border bg-surface px-4 py-3 text-[13px] font-semibold shadow-pop"
             :class="{
                'border-[#BBE5C9] text-[#15803D]': item.tone === 'success',
                'border-[#F7CFCF] text-danger': item.tone === 'danger',
                'border-border text-ink-2': item.tone === 'info',
             }">
            <i class="text-[18px]" :class="item.tone === 'danger' ? 'ph-fill ph-warning-circle' : (item.tone === 'info' ? 'ph ph-info' : 'ph-fill ph-check-circle')"></i>
            <span x-text="item.message"></span>
        </div>
    </template>
</div>
