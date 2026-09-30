<div>
    <a href="{{ route('password.request') }}" wire:navigate class="mb-[22px] inline-flex min-h-11 items-center gap-[7px] text-[13.5px] font-semibold text-primary"><i class="ph ph-arrow-left text-[17px]"></i> Kembali</a>
    <div class="flex size-[52px] items-center justify-center rounded-[14px] bg-primary-soft"><i class="ph ph-lock-key-open text-[26px] text-primary"></i></div>
    <h2 class="mt-[18px] text-[24px] font-extrabold text-ink md:text-[26px]">Tetapkan Kata Laluan Baharu</h2>
    <p class="mt-2 text-[14px] leading-[1.6] text-muted">Cipta kata laluan baharu yang selamat untuk akaun anda.</p>

    @if ($done)
        <div class="mt-7 text-center">
            <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-success-soft"><i class="ph-fill ph-check-circle text-[36px] text-success"></i></div>
            <h3 class="mt-[18px] text-[19px] font-extrabold text-ink">Kata Laluan Ditukar!</h3>
            <p class="mt-2 text-[13.5px] leading-[1.6] text-muted">Kata laluan anda telah dikemaskini. Sila log masuk semula dengan kata laluan baharu.</p>
            <a href="{{ route('login') }}" class="mt-6 block w-full rounded-[9px] bg-primary p-[14px] text-center text-[15px] font-bold text-white hover:bg-primary-hover hover:text-white">Ke Log Masuk</a>
        </div>
    @else
        <form wire:submit="resetPassword" x-data="passwordStrength()" novalidate>
            @include('livewire.auth.partials.new-password-fields')

            <button type="submit" :disabled="!canSubmit"
                    class="mt-5 flex w-full items-center justify-center gap-[9px] rounded-[9px] bg-primary p-[14px] text-[15px] font-bold text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:bg-[#CBD5D0]">
                <i class="ph ph-check text-[18px]"></i> Tukar Kata Laluan
            </button>
        </form>
    @endif
</div>
