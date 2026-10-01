@php
    /** @var \App\Models\User|null $user */
    $user = auth()->user();
    // Items from the Tetapan modal (Tempahan & Pelanggan.dc.html `settingsItems`).
    $items = collect([
        ['icon' => 'user-gear', 'tone' => 'primary', 'title' => 'Profil & Akaun', 'desc' => 'Nama, emel, kata laluan', 'route' => 'settings.profile', 'can' => null],
        ['icon' => 'bell', 'tone' => 'info', 'title' => 'Notifikasi', 'desc' => 'E-mel, WhatsApp, dalam sistem', 'route' => 'settings.notifications', 'can' => null],
        ['icon' => 'users', 'tone' => 'gold', 'title' => 'Pengguna & Peranan', 'desc' => 'Kawalan akses (RBAC)', 'route' => 'users.index', 'can' => 'users.view'],
        ['icon' => 'buildings', 'tone' => 'purple', 'title' => 'Maklumat Syarikat', 'desc' => 'Alamat, SSM, bank', 'route' => 'settings.company', 'can' => 'settings.view'],
        ['icon' => 'shield-check', 'tone' => 'success', 'title' => 'Keselamatan', 'desc' => '2FA, log masuk, sesi', 'route' => 'settings.security', 'can' => null],
        ['icon' => 'plugs', 'tone' => 'info', 'title' => 'Integrasi API', 'desc' => 'Kunci API & gerbang pembayaran', 'route' => 'settings.integrations', 'can' => 'api.view'],
        ['icon' => 'webhooks-logo', 'tone' => 'warning', 'title' => 'Webhooks', 'desc' => 'Notifikasi peristiwa ke sistem luar', 'route' => 'settings.webhooks', 'can' => 'webhooks.view'],
    ])->filter(fn ($i) => $user && ($i['can'] === null || $user->can($i['can'])));
@endphp

<x-ui.modal name="settings" title="Tetapan" subtitle="Konfigurasi sistem Nadi Qurban OSI" icon="gear-six" max-width="480px" body-class="px-2 py-[14px] md:px-4">
    <div class="flex flex-col">
        @foreach ($items as $item)
            <a href="{{ route($item['route']) }}" wire:navigate x-on:click="$dispatch('close-modal', 'settings')"
               class="flex min-h-11 items-center gap-[13px] rounded-[9px] px-3 py-[13px] hover:bg-bg">
                <span class="flex size-[34px] items-center justify-center rounded-[9px] {{ \App\Support\Tone::classes($item['tone']) }}"><i class="ph ph-{{ $item['icon'] }} text-[17px]"></i></span>
                <div class="flex-1">
                    <div class="text-[13.5px] font-semibold text-ink">{{ $item['title'] }}</div>
                    <div class="mt-px text-[11.5px] text-faint">{{ $item['desc'] }}</div>
                </div>
                <i class="ph ph-caret-right text-[15px] text-faint"></i>
            </a>
        @endforeach
    </div>
    <x-slot:footer>
        <x-ui.button x-on:click="$dispatch('close-modal', 'settings')">Tutup</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
