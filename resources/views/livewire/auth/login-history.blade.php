<div>
    <a href="{{ route('settings.security') }}" wire:navigate class="mb-[22px] inline-flex min-h-11 items-center gap-[7px] text-[13.5px] font-semibold text-primary"><i class="ph ph-arrow-left text-[17px]"></i> Kembali</a>
    <div class="flex size-[52px] items-center justify-center rounded-[14px] bg-primary-soft"><i class="ph ph-clock-counter-clockwise text-[26px] text-primary"></i></div>
    <h2 class="mt-[18px] text-[24px] font-extrabold text-ink md:text-[26px]">Sejarah Log Masuk</h2>
    <p class="mt-2 text-[14px] leading-[1.6] text-muted">Aktiviti log masuk terkini bagi akaun anda.</p>

    <div class="mt-[22px]">
        <x-login-history-list :history="$history" />
    </div>
</div>
