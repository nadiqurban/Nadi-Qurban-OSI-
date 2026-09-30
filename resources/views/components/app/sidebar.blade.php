@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    $groups = app(\App\Support\Navigation::class)->for($user);
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
        @foreach ($groups as $group)
            <div class="px-[14px] pt-4 pb-2 text-[10.5px] font-bold tracking-[1.4px] text-faint">{{ $group['section'] }}</div>
            @foreach ($group['items'] as $item)
                @php
                    $active = $item['is_active'];
                    $classes = \Illuminate\Support\Arr::toCssClasses([
                        'relative my-0.5 flex w-full items-center gap-3 rounded-[9px] px-[14px] py-[10px] text-left text-[13.5px] max-lg:min-h-11',
                        'bg-primary-soft font-bold text-nav-active hover:text-nav-active' => $active,
                        'font-medium text-ink-3 hover:bg-bg hover:text-ink-3' => ! $active,
                    ]);
                @endphp
                @if (isset($item['modal']))
                    <button type="button" x-on:click="$store.ui.sidebar = false; $dispatch('open-modal', @js($item['modal']))" class="{{ $classes }}">
                @else
                    <a href="{{ $item['href'] }}" wire:navigate class="{{ $classes }}" @if ($active) aria-current="page" @endif>
                @endif
                    <span @class(['absolute top-2 bottom-2 left-0 w-[3px] rounded-r-[3px]', 'bg-nav-bar' => $active, 'bg-transparent' => ! $active])></span>
                    <span @class(['flex', 'text-nav-active' => $active, 'text-faint' => ! $active])><i class="ph ph-{{ $item['icon'] }} block text-[18px] leading-none"></i></span>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if ($item['badge_count'])
                        <span class="rounded-[20px] bg-gold px-2 py-0.5 text-[10.5px] font-bold text-white">{{ $item['badge_count'] }}</span>
                    @endif
                @if (isset($item['modal']))
                    </button>
                @else
                    </a>
                @endif
            @endforeach
        @endforeach
    </nav>

    @if ($user)
        <a href="{{ route('settings.profile') }}" wire:navigate class="flex shrink-0 items-center gap-[11px] border-t border-border px-4 py-[14px] pb-safe hover:bg-bg lg:pb-[14px]">
            <x-app.user-avatar :user="$user" :size="38" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-bold text-ink">{{ $user->name }}</div>
                <div class="truncate text-[11px] text-muted">{{ $user->role_label }}</div>
            </div>
        </a>
    @endif
</aside>

<x-app.settings-modal />
