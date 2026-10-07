<div class="flex min-h-screen items-center justify-center bg-primary p-4 md:p-6">
    <div class="w-full max-w-[400px] rounded-[16px] bg-surface px-6 py-8 shadow-[0_24px_60px_rgba(0,0,0,.25)] md:px-7">
        <div class="flex items-center gap-3">
            <div class="size-11 overflow-hidden rounded-[11px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover"></div>
            <div>
                <div class="text-[16px] font-extrabold text-primary dark:text-[#c9ce93]">NADI QURBAN</div>
                <div class="text-[11px] font-semibold tracking-[1.5px] text-faint">PORTAL EJEN SALES</div>
            </div>
        </div>

        <h1 class="mt-[26px] text-[22px] font-extrabold text-ink">Log Masuk Ejen</h1>
        <p class="mt-1.5 text-[13.5px] text-muted">Urus link jualan, jejak tempahan &amp; komisen anda.</p>

        @if (session('status') || session('warning'))
            <div class="mt-4 rounded-[9px] bg-bg px-[14px] py-3 text-[12.5px] text-ink-3" role="status">{{ session('status') ?? session('warning') }}</div>
        @endif

        <form wire:submit="login" class="mt-[22px]" novalidate>
            <label for="ag-email" class="mb-1.5 block text-[12.5px] font-semibold text-ink-2">Emel</label>
            <input id="ag-email" type="email" wire:model="email" placeholder="ejen@nadiqurban.com" autocomplete="username" autofocus
                   class="w-full rounded-[9px] border border-border bg-surface px-[13px] py-3 text-[14px] text-ink outline-none focus:border-primary max-md:text-[16px]">

            <label for="ag-password" class="mt-[14px] mb-1.5 block text-[12.5px] font-semibold text-ink-2">Kata Laluan</label>
            <input id="ag-password" type="password" wire:model="password" placeholder="••••••••" autocomplete="current-password"
                   class="w-full rounded-[9px] border border-border bg-surface px-[13px] py-3 text-[14px] text-ink outline-none focus:border-primary max-md:text-[16px]">

            @error('email')<p class="mt-[10px] text-[12.5px] font-semibold text-danger" role="alert">{{ $message }}</p>@enderror
            @error('password')<p class="mt-[10px] text-[12.5px] font-semibold text-danger" role="alert">{{ $message }}</p>@enderror

            <button type="submit" wire:loading.attr="disabled"
                    class="mt-5 flex min-h-12 w-full items-center justify-center gap-2 rounded-[10px] bg-primary p-[13px] text-[14.5px] font-bold text-white hover:bg-primary-hover disabled:opacity-70">
                <i class="ph ph-circle-notch animate-spin text-[18px]" wire:loading wire:target="login"></i>
                Log Masuk
            </button>
        </form>

        <div class="mt-[18px] flex items-start gap-2 rounded-[10px] bg-bg px-[14px] py-3 text-[12px] leading-[1.6] text-muted">
            <i class="ph ph-shield-check mt-[1px] text-[16px] text-success"></i>
            <span>Akaun ejen didaftarkan oleh pihak HQ. Lupa kata laluan? Hubungi pentadbir anda.</span>
        </div>
    </div>
</div>
