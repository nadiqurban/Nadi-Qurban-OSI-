@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Tetamu';
    $userRole = $user?->roleLabel ?? '—';
@endphp

{{-- Overlay behind the mobile drawer --}}
<div x-data x-cloak x-show="$store.ui.sidebar" x-transition.opacity
     @click="$store.ui.sidebar = false"
     class="fixed inset-0 z-40 bg-[rgba(20,24,20,.55)] lg:hidden"></div>

<aside x-data
       @keydown.escape.window="$store.ui.sidebar = false"
       :data-open="$store.ui.sidebar"
       class="fixed inset-y-0 left-0 z-50 flex w-[280px] shrink-0 flex-col border-r border-border bg-surface transition-transform duration-200 max-lg:-translate-x-full max-lg:data-[open=true]:translate-x-0 lg:sticky lg:top-0 lg:z-auto lg:h-screen lg:w-[260px] lg:translate-x-0"
       aria-label="Navigasi utama">
    <div class="flex h-[72px] shrink-0 items-center gap-3 border-b border-border px-[22px]">
        <div class="size-[38px] shrink-0 overflow-hidden rounded-[10px]">
            <img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover">
        </div>
        <div class="leading-none">
            <div class="text-[15px] font-extrabold tracking-[.3px] text-primary dark:text-[#c9ce93]">NADI QURBAN</div>
            <div class="mt-[3px] text-[10.5px] font-semibold tracking-[2px] text-faint">OSI SYSTEM</div>
        </div>
        <button type="button" @click="$store.ui.sidebar = false"
                class="ml-auto flex size-11 items-center justify-center rounded-[9px] text-muted lg:hidden" aria-label="Tutup menu">
            <i class="ph ph-x text-[20px]"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-[14px]">
        @foreach (config('navigation') as $group)
            <div class="px-[14px] pt-4 pb-2 text-[10.5px] font-bold tracking-[1.4px] text-faint">{{ $group['section'] }}</div>
            @foreach ($group['items'] as $item)
                @php
                    $href = Route::has($item['route']) ? route($item['route']) : '#';
                    $active = request()->routeIs(...(array) ($item['active'] ?? $item['route']));
                    $badgeClass = $item['badge'] ?? null;
                    $badge = $badgeClass && class_exists($badgeClass) ? app($badgeClass)() : null;
                @endphp
                <a href="{{ $href }}" wire:navigate
                   @class([
                       'relative my-0.5 flex items-center gap-3 rounded-[9px] px-[14px] py-[10px] text-[13.5px] max-lg:min-h-11',
                       'bg-primary-soft font-bold text-nav-active hover:text-nav-active' => $active,
                       'font-medium text-ink-3 hover:bg-bg hover:text-ink-3' => ! $active,
                   ])
                   @if ($active) aria-current="page" @endif>
                    <span @class(['absolute top-2 bottom-2 left-0 w-[3px] rounded-r-[3px]', 'bg-nav-bar' => $active, 'bg-transparent' => ! $active])></span>
                    <span @class(['flex', 'text-nav-active' => $active, 'text-faint' => ! $active])><i class="ph ph-{{ $item['icon'] }} block text-[18px] leading-none"></i></span>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if ($badge)
                        <span class="rounded-[20px] bg-gold px-2 py-0.5 text-[10.5px] font-bold text-white">{{ $badge }}</span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="flex shrink-0 items-center gap-[11px] border-t border-border px-4 py-[14px] pb-safe lg:pb-[14px]">
        <div class="flex size-[38px] shrink-0 items-center justify-center rounded-full bg-primary text-[13px] font-extrabold text-gold">{{ initials($userName) }}</div>
        <div class="min-w-0 flex-1">
            <div class="truncate text-[13px] font-bold text-ink">{{ $userName }}</div>
            <div class="truncate text-[11px] text-muted">{{ $userRole }}</div>
        </div>
    </div>
</aside>
