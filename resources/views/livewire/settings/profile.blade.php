<div>
    <x-ui.page-header title="Profil & Akaun" subtitle="Urus maklumat peribadi, emel, dan kata laluan akaun anda." :breadcrumb="['Tetapan', 'Profil & Akaun']" />

    <div class="grid max-w-[940px] grid-cols-1 items-start gap-[22px] lg:grid-cols-[1fr_1.6fr]">
        {{-- avatar card --}}
        <section class="min-w-0 rounded-[12px] border border-border bg-surface px-6 py-[26px] text-center">
            @if ($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="mx-auto mb-4 size-24 rounded-full object-cover">
            @else
                <div class="mx-auto mb-4 flex size-24 items-center justify-center rounded-full bg-primary text-[34px] font-extrabold text-gold">{{ initials($user->name) }}</div>
            @endif
            <div class="text-[16px] font-bold text-ink">{{ $user->name }}</div>
            <div class="mt-[3px] text-[12.5px] text-faint">{{ $user->role_label }}</div>
            <span @class(['mt-3 inline-block rounded-[20px] px-3 py-1 text-[11.5px] font-semibold', 'bg-success-soft text-success' => ! $user->isSuspended(), 'bg-danger-soft text-danger' => $user->isSuspended()])>
                {{ $user->isSuspended() ? 'Akaun Digantung' : 'Akaun Aktif' }}
            </span>
            <label class="mt-[18px] flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-[9px] border-[1.5px] border-dashed border-gold p-[10px] text-[12.5px] font-semibold text-primary">
                <i class="ph ph-camera text-[16px]" wire:loading.remove wire:target="photo"></i>
                <i class="ph ph-circle-notch animate-spin text-[16px]" wire:loading wire:target="photo"></i>
                Tukar Foto
                <input type="file" wire:model="photo" accept="image/png,image/jpeg,image/webp" class="hidden">
            </label>
            @error('photo')<p class="mt-2 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
        </section>

        {{-- form card --}}
        <form wire:submit="save" class="flex min-w-0 flex-col gap-5 rounded-[12px] border border-border bg-surface p-5 md:p-6" x-data="passwordStrength()" novalidate>
            @if ($saved)
                <div class="flex items-center gap-2 rounded-[10px] bg-success-soft px-[14px] py-3 text-[13px] font-semibold text-success" role="status"><i class="ph-fill ph-check-circle text-[17px]"></i> {{ $saved }}</div>
            @endif

            <div>
                <div class="mb-[14px] text-[13px] font-bold text-ink">Maklumat Peribadi</div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.field label="Nama Penuh" wire:model="name" autocomplete="name" />
                    <x-ui.field label="No. Telefon" wire:model="phone" inputmode="tel" autocomplete="tel" />
                    <x-ui.field label="Emel" type="email" wire:model="email" autocomplete="email" />
                    <x-ui.field label="Jawatan" :value="$user->role_label" readonly class="bg-bg text-faint" />
                </div>
            </div>

            <div class="h-px bg-divider"></div>

            <div>
                <div class="mb-[14px] text-[13px] font-bold text-ink">Tukar Kata Laluan</div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.field label="Kata Laluan Semasa" type="password" wire:model="current_password" placeholder="••••••••" autocomplete="current-password" span />
                    <div>
                        <x-ui.field label="Kata Laluan Baharu" type="password" wire:model="password" x-model="pw" placeholder="••••••••" autocomplete="new-password" />
                        <div x-show="pw.length" x-cloak><x-ui.password-strength /></div>
                    </div>
                    <x-ui.field label="Sahkan Kata Laluan" type="password" wire:model="password_confirmation" x-model="confirm" placeholder="••••••••" autocomplete="new-password" />
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-[10px] max-md:[&>*]:flex-1">
                <x-ui.button variant="secondary" :href="route('home')" wire:navigate>Batal</x-ui.button>
                <x-ui.button type="submit" icon="check">Simpan Perubahan</x-ui.button>
            </div>
        </form>
    </div>
</div>
