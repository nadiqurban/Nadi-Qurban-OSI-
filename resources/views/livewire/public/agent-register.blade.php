@php
    $input = 'w-full rounded-[9px] border bg-surface px-3 py-[10px] text-[13px] text-ink outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]';
    $label = 'mb-1.5 block text-[12px] font-semibold text-ink-2';
    $border = fn (string $f) => $errors->has($f) ? 'border-danger' : 'border-border';
    $section = 'mb-3 text-[11px] font-bold tracking-[.5px] text-primary uppercase';
@endphp

<div class="min-h-screen bg-bg">
    <header class="bg-primary px-5 pt-10 pb-[76px] text-center text-white">
        <div class="mx-auto size-[72px] overflow-hidden rounded-[18px] bg-primary shadow-[0_6px_18px_rgba(0,0,0,.18)]">
            <img src="{{ asset('images/logo-mark-512.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.08] object-cover">
        </div>
        <div class="mt-[18px] text-[11px] font-bold tracking-[2.5px] text-gold">PROGRAM EJEN</div>
        <h1 class="mt-1.5 text-[24px] leading-[1.3] font-extrabold tracking-[-.2px]">Pendaftaran Ejen<br>Nadi Qurban Sdn Bhd</h1>
        <p class="mx-auto mt-[10px] max-w-[420px] text-[14px] leading-[1.6] text-[#d9dcc4]">Jadilah Wakil Jualan Sah Nadi Qurban dan bersama kami<br class="max-sm:hidden"> menyampaikan manfaat ibadah kepada lebih ramai.</p>
    </header>

    <main class="mx-auto -mt-12 max-w-[680px] px-4 pb-12">
        @if (! $doneName)
            <section class="rounded-[14px] border border-border bg-surface p-6 shadow-[0_6px_24px_rgba(16,24,40,.06)] max-sm:p-5">
                <form wire:submit="submit" novalidate>
                    <div class="{{ $section }}">Maklumat Peribadi</div>
                    <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="r-name" class="{{ $label }}">Nama Penuh *</label>
                            <input id="r-name" wire:model="name" placeholder="Seperti dalam kad pengenalan" autocomplete="name" class="{{ $input }} {{ $border('name') }}">
                        </div>
                        <div>
                            <label for="r-email" class="{{ $label }}">Emel *</label>
                            <input id="r-email" type="email" wire:model="email" placeholder="nama@contoh.com" autocomplete="email" class="{{ $input }} {{ $border('email') }}">
                        </div>
                        <div>
                            <label for="r-phone" class="{{ $label }}">No. Telefon *</label>
                            <input id="r-phone" wire:model="phone" placeholder="01X-XXXXXXX" inputmode="tel" autocomplete="tel" class="{{ $input }} {{ $border('phone') }}">
                        </div>
                        <div>
                            <label for="r-gender" class="{{ $label }}">Jantina</label>
                            <select id="r-gender" wire:model="gender" class="{{ $input }} border-border">
                                @foreach (\App\Models\Agent::GENDERS as $g)<option>{{ $g }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="r-dob" class="{{ $label }}">Tarikh Lahir</label>
                            <input id="r-dob" type="date" wire:model="birthDate" class="{{ $input }} {{ $border('birthDate') }}">
                        </div>
                        <div>
                            <label for="r-district" class="{{ $label }}">Daerah</label>
                            <input id="r-district" wire:model="district" placeholder="cth. Petaling" class="{{ $input }} border-border">
                        </div>
                        <div>
                            <label for="r-state" class="{{ $label }}">Negeri</label>
                            <select id="r-state" wire:model="state" class="{{ $input }} border-border">
                                @foreach (\App\Models\Agent::STATES as $st)<option>{{ $st }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Gambar Terkini: cropped square & compressed in the browser before upload. --}}
                    <div class="{{ $section }} mt-[22px]">Gambar Terkini</div>
                    <div class="flex items-center gap-4"
                         x-data="{
                            preview: null, kb: 0, busy: false,
                            pick(e) {
                                const file = e.target.files && e.target.files[0];
                                if (!file) return;
                                const img = new Image();
                                img.onload = () => {
                                    const S = 320, side = Math.min(img.width, img.height);
                                    const c = document.createElement('canvas');
                                    c.width = S; c.height = S;
                                    const ctx = c.getContext('2d');
                                    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, S, S);
                                    ctx.drawImage(img, (img.width - side) / 2, (img.height - side) / 2, side, side, 0, 0, S, S);
                                    c.toBlob((blob) => {
                                        this.preview = c.toDataURL('image/jpeg', 0.72);
                                        this.kb = Math.max(1, Math.round(blob.size / 1024));
                                        this.busy = true;
                                        this.$wire.upload('photo', new File([blob], 'gambar.jpg', { type: 'image/jpeg' }), () => this.busy = false, () => this.busy = false);
                                    }, 'image/jpeg', 0.72);
                                    URL.revokeObjectURL(img.src);
                                };
                                img.src = URL.createObjectURL(file);
                                e.target.value = '';
                            },
                         }">
                        <div @class(['flex size-[88px] shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-dashed bg-divider', 'border-danger' => $errors->has('photo'), 'border-[#CBD5C0]' => ! $errors->has('photo')])>
                            {{-- wire:ignore: Livewire re-renders must not reset the browser-side preview. --}}
                            <div wire:ignore class="contents">
                                <img x-show="preview" x-bind:src="preview" alt="Gambar" class="block size-full object-cover" style="display:none">
                                <i x-show="!preview" class="ph ph-user text-[34px] text-faint"></i>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1" wire:ignore>
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-[9px] bg-primary px-[15px] py-[10px] text-[13px] font-semibold text-white max-md:min-h-11">
                                <i class="ph ph-camera text-[16px]"></i> <span x-text="preview ? 'Tukar Gambar' : 'Muat Naik Gambar'">Muat Naik Gambar</span>
                                <input type="file" accept="image/jpeg,image/png" x-on:change="pick($event)" class="hidden">
                            </label>
                            <div class="mt-[7px] text-[11.5px] leading-[1.5] text-faint">JPG atau PNG. Gambar berukuran pasport, wajah jelas, latar cerah.</div>
                            <div x-show="preview && !busy" style="display:none" class="mt-1.5 inline-flex items-center gap-[5px] rounded-[20px] bg-success-soft px-[9px] py-[3px] text-[11px] font-semibold text-success">
                                <i class="ph ph-check-circle text-[12px]"></i> Dimampat: <span x-text="kb"></span> KB
                            </div>
                            <div x-show="busy" style="display:none" class="mt-1.5 text-[11px] font-semibold text-muted">Memuat naik…</div>
                        </div>
                    </div>

                    <div class="{{ $section }} mt-[22px]">Maklumat Bank (Untuk Bayaran Komisen)</div>
                    <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2">
                        <div>
                            <label for="r-bank" class="{{ $label }}">Bank</label>
                            <select id="r-bank" wire:model="bankName" class="{{ $input }} border-border">
                                @foreach (\App\Models\Agent::BANKS as $b)<option>{{ $b }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label for="r-accname" class="{{ $label }}">Nama Akaun</label>
                            <input id="r-accname" wire:model="bankAccountName" placeholder="Nama pemegang akaun" class="{{ $input }} border-border">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="r-accno" class="{{ $label }}">Nombor Akaun</label>
                            <input id="r-accno" wire:model="bankAccountNo" placeholder="Nombor akaun bank" inputmode="numeric" class="{{ $input }} {{ $border('bankAccountNo') }}">
                        </div>
                    </div>

                    <div class="{{ $section }} mt-[22px]">Akaun Log Masuk Portal Ejen</div>
                    <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2">
                        <div>
                            <label for="r-pass" class="{{ $label }}">Kata Laluan *</label>
                            <input id="r-pass" type="password" wire:model="password" placeholder="Minimum 8 aksara" autocomplete="new-password" class="{{ $input }} {{ $border('password') }}">
                        </div>
                        <div>
                            <label for="r-pass2" class="{{ $label }}">Sahkan Kata Laluan *</label>
                            <input id="r-pass2" type="password" wire:model="passwordConfirmation" placeholder="Taip semula" autocomplete="new-password" class="{{ $input }} {{ $border('passwordConfirmation') }}">
                        </div>
                    </div>
                    <p class="mt-1.5 text-[11.5px] text-faint">Sekurang-kurangnya 8 aksara dengan huruf besar, huruf kecil dan nombor.</p>

                    <label class="mt-5 flex cursor-pointer items-start gap-[11px]">
                        <input type="checkbox" wire:model="agreed" class="peer sr-only">
                        <span class="mt-px flex size-[22px] shrink-0 items-center justify-center rounded-[6px] border-[1.5px] border-faint bg-surface peer-checked:border-primary peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary/30 [&>i]:hidden peer-checked:[&>i]:block">
                            <i class="ph-fill ph-check text-[13px] text-white"></i>
                        </span>
                        <span class="text-[13px] leading-[1.6] text-ink-3">Saya mengesahkan maklumat di atas adalah benar dan bersetuju dengan terma &amp; syarat program ejen Nadi Qurban Sdn Bhd.</span>
                    </label>

                    @if ($errors->any())
                        <div class="mt-4 flex items-center gap-2 rounded-[9px] bg-danger-soft px-[14px] py-[11px] text-[12.5px] font-semibold text-danger" role="alert">
                            <i class="ph ph-warning-circle shrink-0 text-[16px]"></i> {{ $errors->first() }}
                        </div>
                    @endif

                    <button type="submit" wire:loading.attr="disabled" wire:target="submit,photo"
                            class="mt-5 flex min-h-12 w-full items-center justify-center gap-[9px] rounded-[10px] bg-primary p-[14px] text-[15px] font-bold text-white hover:bg-primary-hover disabled:opacity-70">
                        <i class="ph ph-paper-plane-tilt text-[18px]"></i> Hantar Pendaftaran
                    </button>
                    <p class="mt-[14px] text-center text-[12.5px] text-faint">Sudah berdaftar? <a href="{{ route('agent.login') }}" class="font-semibold text-primary hover:text-primary-hover">Log masuk Portal Ejen</a></p>
                </form>
            </section>
        @else
            <section class="rounded-[14px] border border-border bg-surface px-[26px] py-9 text-center shadow-[0_6px_24px_rgba(16,24,40,.06)]">
                <div class="mx-auto flex size-[72px] items-center justify-center rounded-full bg-warning-soft"><i class="ph-fill ph-hourglass-medium text-[38px] text-warning"></i></div>
                <h2 class="mt-4 text-[20px] font-extrabold text-ink">Pendaftaran Anda Sedang Disahkan</h2>
                <p class="mt-2 text-[14px] leading-[1.6] text-muted">Terima kasih, <b class="text-ink">{{ $doneName }}</b>. Pendaftaran anda sedang disahkan oleh pegawai kami. Anda akan dimaklumkan melalui WhatsApp selepas akaun diaktifkan.</p>
                <div class="mt-[14px] inline-flex items-center gap-[7px] rounded-[20px] bg-primary px-[15px] py-[7px] text-[12px] font-bold text-white"><span class="size-[7px] rounded-full bg-gold"></span> Menunggu Pengesahan</div>
            </section>
        @endif

        <p class="mt-[22px] text-center text-[11.5px] text-faint">&copy; {{ now()->year }} Nadi Qurban Sdn. Bhd. Hak cipta terpelihara.</p>
    </main>
</div>
