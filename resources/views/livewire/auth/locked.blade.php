<div class="text-center"
     x-data="{ left: {{ $secondsLeft }}, get mmss() { const m = Math.floor(this.left / 60), s = this.left % 60; return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0'); } }"
     x-init="const t = setInterval(() => { left = Math.max(0, left - 1); if (!left) clearInterval(t); }, 1000)">
    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-danger-soft"><i class="ph-fill ph-lock-simple text-[34px] text-danger"></i></div>
    <h2 class="mt-[18px] text-[24px] font-extrabold text-ink">Akaun Dikunci</h2>
    <p class="mt-2 text-[14px] leading-[1.6] text-muted">Akaun anda telah dikunci sementara selepas 5 percubaan log masuk yang gagal.</p>

    <div class="mt-5 flex items-center justify-center gap-[10px] rounded-[11px] bg-bg p-4">
        <i class="ph ph-clock-countdown text-[20px] text-primary"></i>
        <span class="text-[14px] text-ink-2" x-show="left > 0">Cuba semula dalam <b class="text-primary" x-text="mmss">{{ sprintf('%02d:%02d', intdiv($secondsLeft, 60), $secondsLeft % 60) }}</b></span>
        <span class="text-[14px] text-ink-2" x-cloak x-show="left === 0">Anda boleh cuba log masuk semula sekarang.</span>
    </div>

    <p class="mt-4 text-[12.5px] leading-[1.6] text-faint">Perlukan akses segera? Hubungi pentadbir sistem atau tetapkan semula kata laluan anda.</p>
    <a href="{{ route('password.request') }}" wire:navigate class="mt-[18px] block w-full rounded-[9px] border border-primary bg-white p-[13px] text-[14px] font-bold text-primary hover:text-primary">Tetapkan Semula Kata Laluan</a>
    <a href="{{ route('login') }}" wire:navigate class="mt-[14px] inline-flex min-h-11 items-center text-[13.5px] font-semibold text-muted">Kembali ke Log Masuk</a>
</div>
