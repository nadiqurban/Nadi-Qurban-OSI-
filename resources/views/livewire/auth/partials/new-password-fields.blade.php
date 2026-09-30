{{-- New password + confirmation with strength meter, rules and mismatch hint (Login.dc.html reset view). --}}
<div class="mt-6">
    <x-ui.auth-input label="Kata Laluan Baharu" icon="lock-key" toggle wire:model="password" x-model="pw" error="password"
                     placeholder="Minimum 8 aksara" autocomplete="new-password">
        <x-ui.password-strength />
    </x-ui.auth-input>
</div>

<div class="mt-[18px]">
    <x-ui.auth-input label="Sahkan Kata Laluan" icon="lock-key" toggle wire:model="password_confirmation" x-model="confirm"
                     invalid="mismatch" placeholder="Taip semula kata laluan" autocomplete="new-password" />
    <div x-cloak x-show="mismatch" class="mt-1.5 flex items-center gap-[5px] text-[11.5px] text-danger"><i class="ph ph-warning-circle text-[14px]"></i> Kata laluan tidak sepadan.</div>
</div>

<x-ui.password-rules />
