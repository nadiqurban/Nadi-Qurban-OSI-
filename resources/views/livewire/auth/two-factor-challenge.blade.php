<div>
    <a href="{{ route('login') }}" wire:navigate class="mb-[22px] inline-flex min-h-11 items-center gap-[7px] text-[13.5px] font-semibold text-primary"><i class="ph ph-arrow-left text-[17px]"></i> Kembali log masuk</a>
    <div class="flex size-[52px] items-center justify-center rounded-[14px] bg-primary-soft"><i class="ph ph-device-mobile-camera text-[26px] text-primary"></i></div>
    <h2 class="mt-[18px] text-[24px] font-extrabold text-ink md:text-[26px]">Pengesahan Dua Langkah</h2>
    <p class="mt-2 text-[14px] leading-[1.6] text-muted">Masukkan kod 6 digit daripada aplikasi pengesah (Google Authenticator, Authy dsb.).</p>

    <form wire:submit="verify" class="mt-6" novalidate>
        <x-ui.auth-input label="Kod Pengesahan" icon="shield-check" wire:model="code" error="code"
                         inputmode="numeric" maxlength="6" placeholder="123456" autocomplete="one-time-code" autofocus />
        <button type="submit" class="mt-[22px] flex w-full items-center justify-center gap-[9px] rounded-[9px] bg-primary p-[14px] text-[15px] font-bold text-white hover:bg-primary-hover">
            <i class="ph ph-check text-[18px]"></i> Sahkan
        </button>
    </form>
</div>
