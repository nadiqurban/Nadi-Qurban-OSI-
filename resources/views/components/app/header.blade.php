@php
    $user = auth()->user();
    $userName = $user?->name ?? 'Tetamu';
    $userRole = $user?->roleLabel ?? '—';
    $unread = $user?->unreadNotifications()->count() ?? 0;
@endphp

<header class="sticky top-0 z-20 flex h-[72px] items-center gap-3 border-b border-border bg-surface px-4 md:px-6 lg:gap-6 lg:px-8">
    {{-- Mobile/tablet: hamburger + small logo --}}
    <button type="button" x-data @click="$store.ui.sidebar = true"
            class="-ml-1 flex size-11 items-center justify-center rounded-[10px] text-muted lg:hidden" aria-label="Buka menu">
        <i class="ph ph-list text-[22px]"></i>
    </button>
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 lg:hidden">
        <span class="size-8 overflow-hidden rounded-[9px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="" class="size-full scale-[1.22] object-cover"></span>
        <span class="text-[14px] font-extrabold tracking-[.3px] text-primary max-sm:hidden dark:text-[#c9ce93]">NADI QURBAN</span>
    </a>

    {{-- Desktop search box (opens command palette) --}}
    <button type="button" x-data @click="$store.ui.search = true"
            class="mx-auto hidden max-w-[520px] flex-1 items-center gap-[11px] rounded-[10px] border border-border bg-bg px-[15px] py-[10px] text-left lg:flex">
        <i class="ph ph-magnifying-glass text-[18px] leading-none text-faint"></i>
        <span class="text-[13.5px] text-faint">Cari tempahan, pelanggan, vendor, no. tracking&hellip;</span>
        <span class="ml-auto rounded-[5px] border border-border bg-surface px-[7px] py-0.5 text-[11px] text-faint">&#8984;K</span>
    </button>

    <div class="ml-auto flex items-center gap-2 lg:ml-0">
        <button type="button" x-data @click="$store.ui.search = true"
                class="flex size-11 items-center justify-center rounded-[10px] border border-border text-muted lg:hidden" aria-label="Cari">
            <i class="ph ph-magnifying-glass text-[20px] leading-none"></i>
        </button>

        <a href="{{ Route::has('notifications.index') ? route('notifications.index') : '#' }}" wire:navigate
           class="relative flex size-11 items-center justify-center rounded-[10px] border border-border text-muted hover:text-muted lg:size-10" aria-label="Notifikasi">
            <i class="ph ph-bell text-[20px] leading-none"></i>
            @if ($unread > 0)
                <span class="absolute -top-1 -right-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-[20px] border-2 border-white bg-danger px-1 text-[10px] font-bold text-white">{{ $unread > 99 ? '99+' : $unread }}</span>
            @endif
        </a>

        <button type="button" x-data @click="$store.ui.toggleTheme()" title="Tukar tema"
                class="flex size-11 items-center justify-center rounded-[10px] border border-border text-muted lg:size-10" aria-label="Tukar tema">
            <i class="ph text-[20px] leading-none" :class="$store.ui.theme === 'dark' ? 'ph-sun' : 'ph-moon'"></i>
        </button>

        <div class="mx-1 hidden h-[30px] w-px bg-border md:block"></div>

        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = !open" class="flex items-center gap-[10px] text-left" :aria-expanded="open">
                <span class="flex size-10 items-center justify-center rounded-full bg-primary text-[13px] font-extrabold text-gold">{{ initials($userName) }}</span>
                <span class="hidden leading-[1.2] md:block">
                    <span class="block text-[13px] font-bold text-ink">{{ $userName }}</span>
                    <span class="block text-[11px] text-muted">{{ $userRole }}</span>
                </span>
                <i class="ph ph-caret-down hidden text-[16px] leading-none text-faint md:block"></i>
            </button>
            <div x-cloak x-show="open" x-transition.origin.top.right
                 class="absolute right-0 top-[50px] z-30 flex w-[200px] flex-col rounded-[10px] border border-border bg-surface p-1.5 shadow-pop">
                <a href="{{ Route::has('settings.profile') ? route('settings.profile') : '#' }}" wire:navigate class="flex items-center gap-[9px] rounded-[7px] px-[11px] py-[9px] text-[13px] text-ink-2 hover:bg-bg hover:text-ink-2"><i class="ph ph-user-circle text-[16px]"></i> Profil</a>
                <a href="{{ Route::has('settings.company') ? route('settings.company') : '#' }}" wire:navigate class="flex items-center gap-[9px] rounded-[7px] px-[11px] py-[9px] text-[13px] text-ink-2 hover:bg-bg hover:text-ink-2"><i class="ph ph-gear-six text-[16px]"></i> Tetapan</a>
                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-[9px] rounded-[7px] px-[11px] py-[9px] text-[13px] text-danger hover:bg-danger-soft"><i class="ph ph-sign-out text-[16px]"></i> Log Keluar</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</header>
