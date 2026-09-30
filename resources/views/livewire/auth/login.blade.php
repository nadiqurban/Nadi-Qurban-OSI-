<div>
    <div class="text-[13px] font-semibold tracking-[.5px] text-gold">SELAMAT KEMBALI</div>
    <h2 class="mt-2 text-[24px] font-extrabold text-ink md:text-[26px]">Log Masuk Akaun</h2>
    <p class="mt-2 text-[14px] text-muted">Masukkan kelayakan anda untuk mengakses sistem operasi.</p>

    @if (session('status'))
        <div class="mt-5 flex items-start gap-[10px] rounded-[9px] border border-[#BBE5C9] bg-success-soft p-[14px]" role="status">
            <i class="ph-fill ph-check-circle text-[20px] text-success"></i>
            <span class="text-[13px] leading-[1.5] text-[#15803D]">{{ session('status') }}</span>
        </div>
    @endif
    @if (session('warning'))
        <div class="mt-5 flex items-start gap-[10px] rounded-[9px] border border-[#F7D9A8] bg-warning-soft p-[14px]" role="status">
            <i class="ph ph-clock-countdown text-[20px] text-warning"></i>
            <span class="text-[13px] leading-[1.5] text-[#B45309]">{{ session('warning') }}</span>
        </div>
    @endif

    <form wire:submit="login" class="mt-7" novalidate>
        <x-ui.auth-input label="Emel" icon="envelope-simple" type="email" wire:model="email" error="email"
                         placeholder="nama@nadiqurban.com" autocomplete="username" autofocus />

        <div class="mt-[18px]">
            <x-ui.auth-input label="Kata Laluan" icon="lock-key" toggle wire:model="password" error="password"
                             placeholder="••••••••" autocomplete="current-password" />
        </div>

        <div class="mt-4 flex items-center justify-between">
            <x-ui.checkbox :size="18" wire:model="remember" label="Ingat saya" label-class="text-[13.5px] text-ink-2" />
            <a href="{{ route('password.request') }}" wire:navigate class="flex min-h-11 items-center text-[13.5px] font-semibold text-primary">Lupa Kata Laluan?</a>
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="mt-6 flex w-full items-center justify-center gap-[9px] rounded-[9px] bg-primary p-[14px] text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70">
            <i class="ph ph-sign-in text-[19px]" wire:loading.remove wire:target="login"></i>
            <i class="ph ph-circle-notch animate-spin text-[19px]" wire:loading wire:target="login"></i>
            Log Masuk
        </button>
    </form>

    <div class="mt-[22px] flex items-center gap-[9px] rounded-[9px] border border-border bg-bg px-[14px] py-3">
        <i class="ph ph-shield-check text-[18px] text-success"></i>
        <span class="text-[12.5px] leading-[1.5] text-muted">Sesi disulitkan &amp; auto-logout selepas 30 minit tidak aktif. Akaun dikunci selepas 5 percubaan gagal.</span>
    </div>
</div>
