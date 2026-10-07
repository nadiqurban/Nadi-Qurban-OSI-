@php
    $agent = $this->agent;
    $product = $this->product;
    $qty = $this->quantity();
    $unit = $product ? \Illuminate\Support\Str::after($product->unitLabel(), '1 ') : 'bahagian';
    $manual = $payType !== 'fpx';
    $canPay = $akad && (! $manual || $proof);
    $input = 'w-full rounded-[9px] border bg-surface px-[13px] py-[11px] text-[14px] text-ink outline-none focus:border-primary max-md:text-[16px]';
    $label = 'mb-1.5 block text-[12px] font-semibold text-ink-2';
    $err = fn (string $f) => $errors->has($f) ? 'border-danger' : 'border-border';
@endphp

<div>
@if (! $started)
    {{-- Skrin alu-aluan --}}
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 pt-10 pb-[72px]" style="background: radial-gradient(ellipse at 50% 35%, #565d2a 0%, #42481c 55%, #33381a 100%);">
        <div class="pointer-events-none absolute inset-3 rounded-[22px] border border-[rgba(201,162,39,.22)] md:inset-6"></div>
        <div class="absolute inset-x-0 bottom-[26px] px-5 text-center text-[11.5px] tracking-[.3px] text-white/75">&copy; {{ now()->year }} Nadi Qurban Sdn. Bhd. Hak cipta terpelihara.</div>
        <div class="relative w-full max-w-[440px] text-center">
            <div class="mx-auto size-[104px] overflow-hidden rounded-[24px] shadow-[0_16px_40px_rgba(0,0,0,.35),0_0_0_4px_rgba(201,162,39,.25)]">
                <img src="{{ asset('images/logo-mark-512.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover">
            </div>
            <h1 class="mt-[26px] text-[28px] leading-none font-extrabold tracking-[2px] text-white md:text-[32px]">NADI QURBAN</h1>
            <div class="mt-3 flex items-center justify-center gap-[10px]">
                <span class="h-px w-7 bg-gold"></span><span class="text-[11.5px] font-bold tracking-[2.5px] text-gold">NADI QURBAN SDN. BHD.</span><span class="h-px w-7 bg-gold"></span>
            </div>
            <p class="mx-auto mt-5 max-w-[340px] text-[15px] leading-[1.65] text-pretty text-white/92">Saluran ibadah Qurban &amp; Aqiqah untuk fakir Muslim di lebih dari 15 buah negara</p>
            @if ($agent)
                <p class="mt-4 text-[12.5px] text-[#d6d9bd]">Ejen anda: <b class="text-white">{{ $agent->user->name }}</b></p>
            @endif
            <button type="button" wire:click="start"
                    class="nq-pulse mt-[34px] inline-flex min-h-12 items-center gap-[9px] rounded-[12px] bg-gold px-8 py-[15px] text-[15.5px] font-bold text-white transition-transform hover:-translate-y-0.5 hover:scale-[1.03] hover:bg-[#d4ae35] active:scale-[.97]">
                Mulakan Tempahan <i class="ph ph-arrow-right nq-nudge text-[18px]"></i>
            </button>
        </div>
    </div>
@else
    <div class="min-h-screen bg-bg">
        <header class="bg-primary text-white">
            <div class="mx-auto flex max-w-[880px] items-center gap-3 px-4 py-[18px] md:px-5">
                <div class="size-10 shrink-0 overflow-hidden rounded-[10px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="Nadi Qurban" class="block size-full scale-[1.22] object-cover"></div>
                <div class="min-w-0 flex-1">
                    <div class="text-[16px] font-extrabold tracking-[.3px]">NADI QURBAN</div>
                    <div class="mt-0.5 text-[11px] text-[#d6d9bd]">Tempahan Ibadah Dalam Talian</div>
                </div>
                @if ($agent)
                    <div class="min-w-0 text-right text-[11.5px] text-[#d6d9bd]">Ejen anda<div class="truncate text-[13px] font-bold text-white">{{ $agent->user->name }}</div></div>
                @endif
            </div>
        </header>

        <main class="mx-auto max-w-[880px] px-4 pt-[26px] pb-[60px] md:px-5">
            <ol class="mb-5 flex flex-wrap items-center gap-2" aria-label="Langkah tempahan">
                @foreach (['Pilih Pakej', 'Maklumat', 'Bayar'] as $i => $stepLabel)
                    <li class="flex items-center gap-2" @if ($step === $i + 1) aria-current="step" @endif>
                        <span @class(['flex size-[26px] items-center justify-center rounded-full text-[12px] font-bold', 'bg-primary text-white' => $step >= $i + 1, 'bg-border text-muted' => $step < $i + 1])>{{ $i + 1 }}</span>
                        <span @class(['mr-[10px] text-[13px] font-semibold', 'text-ink' => $step >= $i + 1, 'text-faint' => $step < $i + 1])>{{ $stepLabel }}</span>
                    </li>
                @endforeach
            </ol>

            @if ($step === 1)
                <section class="rounded-[14px] border border-border bg-surface p-4 md:p-[22px]">
                    <h2 class="text-[17px] font-bold">Pilih Servis &amp; Pakej</h2>
                    <p class="mt-1 text-[13px] text-muted">Pilih jenis ibadah dan pakej yang dikehendaki.</p>
                    <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Servis">
                        @foreach ([\App\Enums\Service::Qurban, \App\Enums\Service::Aqiqah, \App\Enums\Service::Nazar, \App\Enums\Service::Dam] as $s)
                            <button type="button" role="tab" wire:click="setService('{{ $s->value }}')" aria-selected="{{ $service === $s->value ? 'true' : 'false' }}"
                                    @class(['min-h-10 rounded-[9px] px-4 py-[9px] text-[13px] font-semibold', 'bg-primary text-white' => $service === $s->value, 'border border-border bg-surface text-ink-3' => $service !== $s->value])>{{ $s === \App\Enums\Service::Nazar ? 'Nazar' : $s->label() }}</button>
                        @endforeach
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-[repeat(auto-fill,minmax(230px,1fr))]">
                        @forelse ($this->products as $p)
                            @php $on = $productId === $p->id; @endphp
                            <button type="button" id="bk-product-{{ $p->id }}" wire:key="bk-product-{{ $p->id }}" wire:click="pick({{ $p->id }})" aria-pressed="{{ $on ? 'true' : 'false' }}"
                                    @class(['rounded-[12px] p-4 text-left transition-[transform,box-shadow] duration-150', 'border-2 border-primary bg-[#FAFBF6]' => $on, 'border border-border bg-surface hover:border-[#CBD5C0]' => ! $on])>
                                <div class="flex min-h-5 items-center justify-between gap-2">
                                    <span class="inline-flex items-center gap-[5px] rounded-[20px] bg-primary-soft px-[10px] py-[3px] text-[11px] font-bold tracking-[.4px] text-primary">{{ $p->package->name }}</span>
                                    @if ($on)<i class="ph-fill ph-check-circle text-[20px] text-primary"></i>@endif
                                </div>
                                <div class="mt-3 flex items-center gap-3">
                                    <div class="flex size-[58px] shrink-0 items-center justify-center rounded-[14px] bg-primary-soft"><span class="{{ $p->animal->maskClass() }} size-10 text-primary" role="img" aria-label="{{ $p->animal->label() }}"></span></div>
                                    <div class="text-[14.5px] font-bold text-ink">{{ $p->name }}</div>
                                </div>
                                <div class="mt-[3px] text-[12px] text-muted">{{ $p->unitLabel() }}</div>
                                <div class="mt-[10px] text-[18px] font-extrabold text-primary">{{ rm($p->price_sen, true) }} / {{ \Illuminate\Support\Str::after($p->unitLabel(), '1 ') }}</div>
                            </button>
                        @empty
                            <p class="col-span-full py-8 text-center text-[13px] text-faint">Tiada pakej tersedia buat masa ini.</p>
                        @endforelse
                    </div>
                    @error('productId')<p class="mt-3 text-[12.5px] font-semibold text-danger">{{ $message }}</p>@enderror

                    <div class="mt-5 flex justify-end">
                        <button type="button" wire:click="toStep(2)" @disabled(! $product)
                                class="inline-flex min-h-11 items-center gap-2 rounded-[10px] bg-primary px-5 py-3 text-[14px] font-semibold text-white disabled:cursor-not-allowed disabled:bg-checkbox-border max-sm:w-full max-sm:justify-center">
                            Teruskan <i class="ph ph-arrow-right text-[16px]"></i>
                        </button>
                    </div>
                </section>
            @elseif ($step === 2)
                <section class="rounded-[14px] border border-border bg-surface p-4 md:p-[22px]">
                    <h2 class="text-[17px] font-bold">Maklumat Diri &amp; Peserta</h2>
                    <div class="mt-4 grid grid-cols-1 gap-[14px] sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="bk-name" class="{{ $label }}">Nama Penuh</label>
                            <input id="bk-name" wire:model.live.debounce.400ms="name" placeholder="Nama seperti dalam kad pengenalan" autocomplete="name" class="{{ $input }} {{ $err('name') }}">
                            @error('name')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="bk-phone" class="{{ $label }}">No. Telefon</label>
                            <input id="bk-phone" wire:model="phone" placeholder="01X-XXXXXXX" inputmode="tel" autocomplete="tel" class="{{ $input }} {{ $err('phone') }}">
                            @error('phone')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="bk-email" class="{{ $label }}">Emel</label>
                            <input id="bk-email" type="email" wire:model="email" placeholder="nama@emel.com" autocomplete="email" class="{{ $input }} {{ $err('email') }}">
                            @error('email')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="bk-address" class="{{ $label }}">Alamat (No. &amp; Jalan)</label>
                            <input id="bk-address" wire:model="address" placeholder="cth. No. 12, Jalan Melati 3, Taman Sri Indah" autocomplete="street-address" class="{{ $input }} {{ $err('address') }}">
                            @error('address')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="bk-postcode" class="{{ $label }}">Poskod</label>
                            <input id="bk-postcode" wire:model="postcode" placeholder="40150" inputmode="numeric" autocomplete="postal-code" class="{{ $input }} {{ $err('postcode') }}">
                            @error('postcode')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="bk-city" class="{{ $label }}">Bandar</label>
                            <input id="bk-city" wire:model="city" placeholder="Shah Alam" autocomplete="address-level2" class="{{ $input }} {{ $err('city') }}">
                        </div>
                        <div>
                            <label for="bk-state" class="{{ $label }}">Negeri</label>
                            <select id="bk-state" wire:model="state" class="{{ $input }} border-border">
                                @foreach (\App\Livewire\Public\Booking::STATES as $st)<option>{{ $st }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <span class="{{ $label }}">Kuantiti ({{ $unit }})</span>
                            <div class="flex h-[46px] items-center rounded-[9px] border border-border bg-bg px-[13px] text-[14px] font-semibold text-ink">{{ $qty }}</div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <div class="text-[13px] font-bold text-primary">Nama Peserta ({{ $qty }})</div>
                        <div class="mt-[10px] flex flex-col gap-2">
                            @foreach ($participants as $i => $pp)
                                <div wire:key="bk-participant-{{ $i }}" class="flex items-center gap-[10px]">
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-[8px] bg-primary-soft text-[12px] font-bold text-primary">{{ $i + 1 }}</span>
                                    <input wire:model.blur="participants.{{ $i }}" placeholder="Nama peserta" aria-label="Nama peserta {{ $i + 1 }}" class="{{ $input }} {{ $err('participants.'.$i) }} flex-1 py-[10px]">
                                    @if ($i > 0)
                                        <button type="button" wire:click="removeParticipant({{ $i }})" title="Buang peserta" aria-label="Buang peserta {{ $i + 1 }}"
                                                class="flex size-11 shrink-0 items-center justify-center rounded-[9px] border border-[#F7CFCF] text-danger md:size-9"><i class="ph ph-x text-[15px]"></i></button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @error('participants.*')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        @error('participants')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        @if ($qty < $this->maxQuantity())
                            <button type="button" wire:click="addParticipant" class="mt-[10px] inline-flex min-h-10 items-center gap-[7px] rounded-[9px] border-[1.5px] border-dashed border-primary bg-surface px-[15px] py-[9px] text-[13px] font-semibold text-primary">
                                <i class="ph ph-plus text-[15px]"></i> Tambah Peserta
                            </button>
                        @else
                            <div class="mt-[10px] text-[12px] text-faint">Maksimum {{ $this->maxQuantity() }} peserta bagi satu tempahan.</div>
                        @endif
                    </div>

                    <div class="mt-[22px] flex justify-between gap-[10px]">
                        <button type="button" wire:click="toStep(1)" class="min-h-11 rounded-[10px] border border-border bg-surface px-[18px] py-3 text-[14px] font-semibold text-ink-2">Kembali</button>
                        <button type="button" wire:click="toStep(3)" class="inline-flex min-h-11 items-center gap-2 rounded-[10px] bg-primary px-5 py-3 text-[14px] font-semibold text-white">Teruskan <i class="ph ph-arrow-right text-[16px]"></i></button>
                    </div>
                </section>
            @else
                <section class="rounded-[14px] border border-border bg-surface p-4 md:p-[22px]">
                    <h2 class="text-[17px] font-bold">Semak &amp; Bayar</h2>
                    <div class="mt-4 flex flex-col gap-[9px] rounded-[11px] bg-bg p-4">
                        @foreach ([
                            'Produk' => $product ? $product->name.' — '.$product->package->name : '-',
                            'Kuantiti' => $qty.' '.$unit,
                            'Nama Pelanggan' => $name ?: '-',
                            'Peserta' => collect($participants)->filter()->implode(', ') ?: '-',
                        ] as $k => $v)
                            <div class="flex justify-between gap-3 text-[13.5px]"><span class="shrink-0 text-muted">{{ $k }}</span><span class="text-right font-semibold">{{ $v }}</span></div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <label for="bk-promo" class="{{ $label }}">Kod Promosi</label>
                        <div class="flex gap-2">
                            <input id="bk-promo" wire:model="promo" wire:keydown.enter.prevent="applyPromo" placeholder="cth. AWALQURBAN" autocomplete="off" class="{{ $input }} border-border min-w-0 flex-1 uppercase">
                            <button type="button" wire:click="applyPromo" class="min-h-11 rounded-[9px] bg-primary-soft px-4 text-[13px] font-bold text-primary">Guna</button>
                        </div>
                        @if ($promoApplied !== '')
                            @if ($promoError)
                                <div class="mt-1.5 text-[12px] font-semibold text-danger">Kod promosi tidak sah.</div>
                            @elseif ($price)
                                <div class="mt-1.5 text-[12px] font-semibold text-success">Kod {{ $promoApplied }} digunakan — jimat {{ rm($price->discountSen, true) }}.</div>
                            @endif
                        @endif
                    </div>

                    <div class="mt-4">
                        <span class="mb-2 block text-[12px] font-semibold text-ink-2">Jenis Bayaran</span>
                        <div class="grid grid-cols-1 gap-[10px] sm:grid-cols-3" role="radiogroup" aria-label="Jenis bayaran">
                            @foreach (['fpx' => ['FPX Payment', 'bank'], 'pindahan_bank' => ['Pindahan Bank', 'arrows-left-right'], 'cek' => ['Cek', 'money']] as $key => [$ptLabel, $ptIcon])
                                <button type="button" role="radio" aria-checked="{{ $payType === $key ? 'true' : 'false' }}" wire:click="$set('payType', '{{ $key }}')"
                                        @class(['flex min-h-12 items-center gap-[10px] rounded-[10px] px-[14px] py-3', 'border-2 border-primary bg-[#FAFBF6] text-primary' => $payType === $key, 'border border-border bg-surface text-ink-3' => $payType !== $key])>
                                    <i class="ph ph-{{ $ptIcon }} text-[20px]"></i><span class="text-[13px] font-semibold">{{ $ptLabel }}</span>
                                </button>
                            @endforeach
                        </div>

                        @if (! $manual)
                            <div class="mt-[10px] flex items-center gap-[10px] rounded-[9px] bg-bg px-3 py-2 text-[12px] text-muted">
                                <span class="inline-flex shrink-0 items-center gap-1 rounded-[6px] bg-[#1D4ED8] px-2 py-1 text-[11px] font-extrabold tracking-[.5px] text-white">CHIP</span>
                                <span>Anda akan dibawa ke gerbang pembayaran CHIP untuk FPX, kad &amp; e-Wallet.</span>
                            </div>
                        @else
                            <div class="mt-3 overflow-hidden rounded-[11px] border border-border">
                                <div class="flex flex-col gap-[10px] border-b border-border bg-bg px-[14px] py-3">
                                    <div class="flex items-center gap-[9px]">
                                        <span class="flex size-[30px] shrink-0 items-center justify-center rounded-[6px] bg-[#FFC72C] text-[#1A1D21]"><i class="ph-fill ph-bank text-[17px]"></i></span>
                                        <div><div class="text-[13px] leading-[1.2] font-bold text-ink">{{ $bank['bank_name'] ?? 'Maybank' }}</div><div class="text-[11px] text-faint">Akaun Bank Rasmi</div></div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        <div class="rounded-[8px] border border-border bg-surface px-[11px] py-2"><div class="text-[10.5px] text-faint">Nama Akaun</div><div class="mt-0.5 text-[12.5px] font-bold text-ink">{{ $bank['bank_holder'] ?? 'Nadi Qurban Sdn Bhd' }}</div></div>
                                        <div class="rounded-[8px] border border-border bg-surface px-[11px] py-2"><div class="text-[10.5px] text-faint">No. Akaun</div><div class="mt-0.5 font-mono text-[12.5px] font-bold text-ink">{{ $bank['bank_account'] ?? '' }}</div></div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-[10px] px-4 py-[14px]">
                                    <div class="text-[11px] font-bold tracking-[.5px] text-faint uppercase">Penting</div>
                                    <div class="flex items-start gap-[10px]"><span class="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-white">1</span><span class="text-[12.5px] leading-[1.6] text-ink-3">Masukkan <b class="text-ink">nama pendaftar</b> di ruangan rujukan semasa membuat transaksi melalui Perbankan Internet.</span></div>
                                    <div class="flex items-start gap-[10px]"><span class="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-white">2</span><span class="text-[12.5px] leading-[1.6] text-ink-3">Ambil dan muat naik <b class="text-ink">gambar bukti pembayaran</b> jika membuat transaksi melalui Mesin Deposit Tunai (CDM).</span></div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="{{ $label }}">Muat Naik Bukti Bayaran <span class="font-normal text-faint">(JPG, PNG atau PDF)</span></span>
                                <label class="flex min-h-12 cursor-pointer items-center gap-[10px] rounded-[10px] border-[1.5px] border-dashed border-gold bg-[#FCFBF5] px-4 py-[14px] text-primary">
                                    <i class="ph{{ $proof ? '-fill ph-check-circle' : ' ph-upload-simple' }} text-[20px]" wire:loading.remove wire:target="proof"></i>
                                    <i class="ph ph-circle-notch animate-spin text-[20px]" wire:loading wire:target="proof"></i>
                                    <span class="min-w-0 flex-1 truncate text-[13px] font-semibold">{{ $proof ? $proof->getClientOriginalName() : 'Klik untuk muat naik bukti bayaran' }}</span>
                                    <input type="file" wire:model="proof" accept="image/jpeg,image/png,application/pdf" class="sr-only">
                                </label>
                                @error('proof')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                                <div class="mt-1.5 text-[11.5px] text-faint">Tempahan akan disahkan oleh pihak HQ selepas bukti bayaran disemak.</div>
                            </div>
                        @endif
                    </div>

                    <button type="button" wire:click="$toggle('akad')" role="checkbox" aria-checked="{{ $akad ? 'true' : 'false' }}"
                            @class(['mt-4 flex w-full items-start gap-3 rounded-[11px] px-4 py-[14px] text-left', 'border-[1.5px] border-primary bg-[#FAFBF6]' => $akad, 'border-[1.5px] border-dashed border-[#CBD5C0] bg-surface' => ! $akad])>
                        <span @class(['mt-px flex size-[22px] shrink-0 items-center justify-center rounded-[6px]', 'border border-primary bg-primary' => $akad, 'border-[1.5px] border-faint bg-surface' => ! $akad])>@if ($akad)<i class="ph-fill ph-check text-[13px] text-white"></i>@endif</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-[12px] font-bold tracking-[.4px] text-primary uppercase">Lafaz Akad</span>
                            <span class="mt-1 block text-[13px] leading-[1.6] text-ink-2">"Saya mewakilkan <b class="text-ink">Syarikat Nadi Qurban Sdn Bhd</b> bertanggungjawab atas pelaksanaan ibadah <b class="text-ink">Qurban / Aqiqah / Nazar / Dam</b> pada tahun ini di atas nama yang didaftarkan kerana Allah Ta'ala."</span>
                        </span>
                    </button>
                    @error('akad')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror

                    <div class="mt-4 flex flex-col gap-[7px] border-t border-border pt-[14px]">
                        <div class="flex justify-between text-[13.5px]"><span class="text-muted">Jumlah</span><span class="font-semibold">{{ rm($price?->subtotalSen ?? 0, true) }}</span></div>
                        <div class="flex justify-between text-[13.5px]"><span class="text-muted">Diskaun Promosi</span><span class="font-semibold text-success">{{ $price && $price->discountSen ? '- '.rm($price->discountSen, true) : rm(0, true) }}</span></div>
                        <div class="mt-1 flex justify-between text-[17px]"><span class="font-bold">Jumlah Bayaran</span><span class="font-extrabold text-primary">{{ rm($price?->totalSen ?? 0, true) }}</span></div>
                    </div>

                    @error('pay')<div class="mt-3 rounded-[9px] bg-danger-soft px-[14px] py-3 text-[13px] font-semibold text-danger" role="alert">{{ $message }}</div>@enderror
                    @foreach (['promo', 'quantity', 'productId'] as $f)
                        @error($f)<div class="mt-3 rounded-[9px] bg-danger-soft px-[14px] py-3 text-[13px] font-semibold text-danger" role="alert">{{ $message }}</div>@enderror
                    @endforeach

                    <div class="mt-[22px] flex justify-between gap-[10px]">
                        <button type="button" wire:click="toStep(2)" class="min-h-11 rounded-[10px] border border-border bg-surface px-[18px] py-3 text-[14px] font-semibold text-ink-2">Kembali</button>
                        <button type="button" wire:click="pay" wire:loading.attr="disabled" wire:target="pay" @disabled(! $canPay)
                                class="inline-flex min-h-11 items-center gap-2 rounded-[10px] bg-primary px-5 py-3 text-[14px] font-bold text-white disabled:cursor-not-allowed disabled:bg-checkbox-border">
                            <i class="ph {{ $manual ? 'ph-paper-plane-tilt' : 'ph-lock-simple' }} text-[16px]"></i>
                            {{ $manual ? 'Hantar Bukti Bayaran' : 'Bayar dengan CHIP '.rm($price?->totalSen ?? 0, true) }}
                        </button>
                    </div>
                    <p class="mt-3 text-right text-[11.5px] text-faint">Bayaran selamat melalui FPX, kad kredit/debit &amp; e-Wallet.</p>
                </section>
            @endif
        </main>

        {{-- Mengalihkan ke CHIP --}}
        <div wire:loading.flex wire:target="pay" class="fixed inset-0 z-[100] hidden items-center justify-center bg-[rgba(20,24,20,.6)] p-5">
            <div class="w-full max-w-[380px] rounded-[16px] bg-surface px-[26px] py-[34px] text-center shadow-[0_24px_60px_rgba(0,0,0,.3)]">
                <div class="mx-auto size-[52px] animate-spin rounded-full border-4 border-info-soft border-t-info"></div>
                <div class="mt-[18px] text-[15px] font-bold">{{ $manual ? 'Menghantar tempahan…' : 'Mengalihkan ke gerbang CHIP…' }}</div>
                <p class="mt-1.5 text-[12.5px] text-faint">Jumlah {{ rm($price?->totalSen ?? 0, true) }} &middot; Jangan tutup tetingkap ini.</p>
            </div>
        </div>
    </div>
@endif
</div>
