<div>
    <a href="{{ route('login') }}" wire:navigate class="mb-[22px] inline-flex min-h-11 items-center gap-[7px] text-[13.5px] font-semibold text-primary"><i class="ph ph-arrow-left text-[17px]"></i> Kembali log masuk</a>
    <div class="flex size-[52px] items-center justify-center rounded-[14px] bg-primary-soft"><i class="ph ph-key text-[26px] text-primary"></i></div>
    <h2 class="mt-[18px] text-[24px] font-extrabold text-ink md:text-[26px]">Lupa Kata Laluan?</h2>
    <p class="mt-2 text-[14px] leading-[1.6] text-muted">Masukkan emel akaun anda. Kami akan hantar pautan tetapan semula kata laluan.</p>

    <form wire:submit="send" class="mt-6" novalidate>
        <x-ui.auth-input label="Emel" icon="envelope-simple" type="email" wire:model="email" error="email"
                         placeholder="nama@nadiqurban.com" autocomplete="email" autofocus />

        <button type="submit" wire:loading.attr="disabled"
                class="mt-[22px] flex w-full items-center justify-center gap-[9px] rounded-[9px] bg-primary p-[14px] text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70">
            <i class="ph ph-paper-plane-tilt text-[19px]"></i> Hantar Pautan Reset
        </button>
    </form>

    @if ($sent)
        <div class="mt-5 flex items-start gap-[10px] rounded-[9px] border border-[#BBE5C9] bg-success-soft p-[14px]" role="status">
            <i class="ph-fill ph-check-circle text-[20px] text-success"></i>
            <span class="text-[13px] leading-[1.5] text-[#15803D]">Pautan reset telah dihantar ke emel anda. Sila semak peti masuk (dan folder spam).</span>
        </div>
    @endif
</div>
