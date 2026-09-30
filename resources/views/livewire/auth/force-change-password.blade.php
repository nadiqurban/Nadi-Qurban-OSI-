<div>
    <div class="flex size-[52px] items-center justify-center rounded-[14px] bg-warning-soft"><i class="ph ph-shield-warning text-[26px] text-warning"></i></div>
    <h2 class="mt-[18px] text-[24px] font-extrabold text-ink md:text-[26px]">Tukar Kata Laluan Diperlukan</h2>
    <p class="mt-2 text-[14px] leading-[1.6] text-muted">Atas sebab keselamatan, anda perlu menukar kata laluan sementara sebelum meneruskan.</p>

    <form wire:submit="change" x-data="passwordStrength()" novalidate>
        <div class="mt-6">
            <x-ui.auth-input label="Kata Laluan Semasa" icon="lock" toggle wire:model="current_password" error="current_password"
                             placeholder="Kata laluan sementara" autocomplete="current-password" />
        </div>

        @include('livewire.auth.partials.new-password-fields')

        <button type="submit" :disabled="!canSubmit"
                class="mt-[22px] flex w-full items-center justify-center gap-[9px] rounded-[9px] bg-primary p-[14px] text-[15px] font-bold text-white hover:bg-primary-hover disabled:cursor-not-allowed disabled:bg-[#CBD5D0]">
            <i class="ph ph-check text-[18px]"></i> Tukar &amp; Teruskan
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="min-h-11 text-[13.5px] font-semibold text-muted">Log Keluar</button>
    </form>
</div>
