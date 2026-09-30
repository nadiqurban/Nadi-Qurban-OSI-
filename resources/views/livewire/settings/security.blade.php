<div>
    <x-ui.page-header title="Keselamatan" subtitle="Pengesahan dua langkah, sesi aktif dan sejarah log masuk akaun anda." :breadcrumb="['Tetapan', 'Keselamatan']" />

    @if ($message)
        <div class="mb-5 flex max-w-[940px] items-center gap-2 rounded-[10px] bg-success-soft px-[14px] py-3 text-[13px] font-semibold text-success" role="status"><i class="ph-fill ph-check-circle text-[17px]"></i> {{ $message }}</div>
    @endif

    <div class="grid max-w-[940px] grid-cols-1 items-start gap-[22px] lg:grid-cols-2">
        {{-- 2FA --}}
        <x-ui.card title="Pengesahan Dua Langkah (2FA)" subtitle="Kod 6 digit daripada aplikasi pengesah setiap kali log masuk.">
            <x-slot:leading>
                <span class="flex size-9 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes('success') }}"><i class="ph ph-shield-check text-[19px]"></i></span>
            </x-slot:leading>

            @if ($user->hasTwoFactorEnabled())
                <div class="mb-4 flex items-center gap-2 text-[13px] font-semibold text-success"><i class="ph-fill ph-check-circle text-[17px]"></i> Aktif sejak {{ tarikh($user->two_factor_confirmed_at) }}</div>
                <form wire:submit="disableTwoFactor" class="flex flex-col gap-3" novalidate>
                    <x-ui.field label="Sahkan kata laluan untuk nyahaktif" type="password" wire:model="password" autocomplete="current-password" />
                    <x-ui.button type="submit" variant="danger-soft" icon="shield-slash">Nyahaktif 2FA</x-ui.button>
                </form>
            @elseif ($pendingSecret)
                <ol class="mb-4 list-decimal space-y-1 pl-5 text-[12.5px] text-ink-3">
                    <li>Imbas kod QR dengan Google Authenticator / Authy.</li>
                    <li>Masukkan kod 6 digit yang dipaparkan.</li>
                </ol>
                <div class="mb-3 flex justify-center rounded-[10px] border border-border bg-white p-4">{!! $qr !!}</div>
                <p class="mb-4 text-center text-[11.5px] break-all text-faint">Kunci manual: <span class="font-mono text-ink-2">{{ $pendingSecret }}</span></p>
                <form wire:submit="confirmTwoFactor" class="flex flex-col gap-3" novalidate>
                    <x-ui.field label="Kod Pengesahan" wire:model="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="123456" />
                    <div class="flex gap-[10px] [&>*]:flex-1">
                        <x-ui.button variant="secondary" wire:click="cancelTwoFactor">Batal</x-ui.button>
                        <x-ui.button type="submit" icon="check">Aktifkan</x-ui.button>
                    </div>
                </form>
            @else
                <p class="mb-4 text-[13px] text-muted">2FA belum diaktifkan. Kami syorkan semua pentadbir mengaktifkannya.</p>
                <x-ui.button icon="shield-plus" wire:click="startTwoFactor">Aktifkan 2FA</x-ui.button>
            @endif
        </x-ui.card>

        {{-- Sessions --}}
        <x-ui.card title="Sesi Aktif" subtitle="Peranti yang sedang log masuk ke akaun anda.">
            <x-slot:leading>
                <span class="flex size-9 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes('info') }}"><i class="ph ph-devices text-[19px]"></i></span>
            </x-slot:leading>

            <div class="flex flex-col gap-[10px]">
                @foreach ($sessions as $s)
                    <div class="flex items-center gap-3 rounded-[11px] border border-border px-[14px] py-3">
                        <i class="ph ph-{{ $s['mobile'] ? 'device-mobile' : 'desktop' }} text-[20px] text-muted"></i>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-[13px] font-semibold text-ink">{{ $s['device'] }}</div>
                            <div class="text-[11.5px] text-faint">{{ $s['ip'] ?? '-' }} · {{ masa_lalu($s['last']) }}</div>
                        </div>
                        @if ($s['current'])
                            <x-ui.badge tone="success" variant="tag">Sesi Semasa</x-ui.badge>
                        @endif
                    </div>
                @endforeach
            </div>

            @if ($sessions->count() > 1)
                <form wire:submit="logoutOtherSessions" class="mt-4 flex flex-col gap-3 border-t border-divider pt-4" novalidate>
                    <x-ui.field label="Sahkan kata laluan" type="password" wire:model="sessionPassword" autocomplete="current-password" />
                    <x-ui.button type="submit" variant="secondary" icon="sign-out">Log keluar sesi lain</x-ui.button>
                </form>
            @endif
        </x-ui.card>

        {{-- Login history --}}
        <x-ui.card title="Sejarah Log Masuk" subtitle="Aktiviti log masuk terkini bagi akaun anda." class="lg:col-span-2">
            <x-slot:leading>
                <span class="flex size-9 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes('primary') }}"><i class="ph ph-clock-counter-clockwise text-[19px]"></i></span>
            </x-slot:leading>
            <x-slot:actions>
                <a href="{{ route('login.history') }}" wire:navigate class="inline-flex min-h-11 items-center text-[12.5px] font-semibold text-primary">Lihat semua <i class="ph ph-arrow-right ml-1 text-[13px]"></i></a>
            </x-slot:actions>
            <x-login-history-list :history="$history" />
        </x-ui.card>
    </div>
</div>
