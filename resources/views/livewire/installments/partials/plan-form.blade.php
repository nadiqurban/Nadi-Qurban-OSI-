@php
    $product = $showForm ? $form->product() : null;
    $promoTyped = trim($form->promo) !== '';
    $promoModel = $promoTyped ? \App\Models\PromoCode::findUsable($form->promo) : null;
    $qty = $form->quantityInt();
    $ro = 'bg-bg text-ink-3';
@endphp

{{-- Tempahan Baharu (Ansuran), 640px — product drives service/animal/package/price. --}}
<x-ui.modal wire:model="showForm" title="Tempahan Baharu (Ansuran)" subtitle="No. auto-generate mengikut servis & haiwan" max-width="640px" :footer-border="false">
    <form id="plan-form" wire:submit="save" class="grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
        <x-ui.field label="Nama Pelanggan" wire:model="form.name" placeholder="Nama penuh pelanggan" span autocomplete="off" />
        <x-ui.field label="Alamat (No. & Jalan)" wire:model="form.address" placeholder="cth. No. 12, Jalan Melati 3, Taman Sri Indah" span />
        <x-ui.field label="Poskod" wire:model="form.postcode" placeholder="40150" inputmode="numeric" maxlength="5" />
        <x-ui.field label="Bandar" wire:model="form.city" placeholder="Shah Alam" />
        <x-ui.field label="Negeri" as="select" wire:model="form.state" span>
            @foreach (\App\Enums\MalaysianState::cases() as $st)
                <option value="{{ $st->value }}">{{ $st->label() }}</option>
            @endforeach
        </x-ui.field>
        <x-ui.field label="No. Telefon" wire:model="form.phone" placeholder="01X-XXXXXXX" inputmode="tel" />
        <x-ui.field label="Emel" type="email" wire:model="form.email" placeholder="nama@gmail.com" />

        <x-ui.field label="Produk" as="select" wire:model.live="form.productId" span>
            <option value="">— Pilih Produk —</option>
            @foreach ($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }} — {{ rm($p->price_sen) }}</option>
            @endforeach
        </x-ui.field>

        <x-ui.field label="Servis" :value="$product?->service->label() ?? '—'" readonly :class="$ro" />
        <x-ui.field label="Haiwan" :value="$product?->animal->label() ?? '—'" readonly :class="$ro" />
        <x-ui.field label="Jenis Pakej" :value="$product?->package->name ?? '—'" readonly :class="$ro" />
        <x-ui.field label="Kuantiti" type="number" min="1" wire:model.live.debounce.400ms="form.quantity" inputmode="numeric" />
        <x-ui.field label="Negara Pelaksanaan" as="select" wire:model="form.countryId">
            <option value="">—</option>
            @foreach ($countries as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
            @endforeach
        </x-ui.field>
        <x-ui.field label="Tahun Pelaksanaan" wire:model="form.year" inputmode="numeric" maxlength="4" />
        <x-ui.field label="Tarikh Pelaksanaan" type="date" wire:model="form.implementationDate" />
        <x-ui.field label="Tempoh Ansuran" as="select" wire:model.live="form.months">
            @foreach (\App\Actions\Installments\CreatePlan::TENURES as $m)
                <option value="{{ $m }}">{{ $m }} bulan</option>
            @endforeach
        </x-ui.field>
        <x-ui.field label="Harga Produk (RM)" hint="(auto)" :value="$breakdown ? rm($breakdown->subtotalSen) : 'RM 0'" readonly class="bg-bg font-bold !text-primary" />
        <x-ui.field label="Jumlah Deposit (RM)" wire:model.live.debounce.500ms="form.deposit" placeholder="cth. 500" inputmode="decimal" />

        {{-- Summary --}}
        <div class="col-span-full flex flex-col gap-1.5 rounded-[10px] bg-primary px-[18px] py-[14px]">
            <div class="flex items-center justify-between text-[12px] text-[#cdd0b0]"><span>Harga Produk</span><span>{{ $breakdown ? rm($breakdown->subtotalSen) : 'RM 0' }}</span></div>
            <div class="flex items-center justify-between text-[12px] text-[#cdd0b0]"><span>Tolak Deposit</span><span>- {{ $breakdown ? rm($breakdown->depositSen) : 'RM 0' }}</span></div>
            <div class="flex items-center justify-between text-[12px] text-[#cdd0b0]"><span>Diskaun Promosi</span><span>- {{ $breakdown ? rm($breakdown->discountSen) : 'RM 0' }}</span></div>
            <div class="mt-0.5 flex items-center justify-between gap-3 border-t border-white/20 pt-2">
                <span class="text-[13px] font-semibold text-[#e7d9a8]">Baki Ansuran</span>
                <span class="text-[19px] font-extrabold text-white">{{ $breakdown ? rm($breakdown->balanceSen()) : 'RM 0' }}</span>
            </div>
            @if ($breakdown && $breakdown->balanceSen() > 0)
                @php $sched = $breakdown->instalments($form->monthsInt()); @endphp
                <div class="text-right text-[11.5px] text-[#cdd0b0]">{{ $form->monthsInt() }} × {{ rm($sched[0]) }}@if (end($sched) !== $sched[0]) (bulan akhir {{ rm(end($sched)) }})@endif</div>
            @endif
        </div>

        <div>
            <x-ui.field label="Kod Promosi" wire:model.live.debounce.500ms="form.promo" placeholder="cth. AWALQURBAN" class="uppercase" autocomplete="off" />
            @if ($promoTyped && ! $errors->has('form.promo'))
                <p @class(['mt-1 text-[11.5px] font-medium', 'text-success' => $promoModel, 'text-danger' => ! $promoModel])>
                    {{ $promoModel ? 'Kod sah — diskaun '.$promoModel->discountLabel() : 'Kod tidak sah atau telah tamat.' }}
                </p>
            @endif
        </div>

        @if ($qty > 1)
            <div class="col-span-full">
                <span class="mb-2 block text-[12px] font-semibold text-ink-2">Senarai Peserta <span class="font-normal text-faint">({{ $qty }} bahagian)</span></span>
                <div class="flex flex-col gap-2">
                    @for ($n = 0; $n < $qty; $n++)
                        <div class="flex items-center gap-[9px]" wire:key="pp-{{ $n }}">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-[7px] bg-primary-soft text-[11px] font-bold text-primary">{{ $n + 1 }}</span>
                            <input type="text" wire:model="form.participants.{{ $n }}" placeholder="Nama peserta {{ $n + 1 }}"
                                   class="flex-1 rounded-[8px] border border-border px-[11px] py-[9px] text-[13px] text-ink outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]">
                        </div>
                    @endfor
                </div>
            </div>
        @endif

        {{-- Kaedah Bayaran Ansuran --}}
        <div class="col-span-full">
            <span class="mb-2 block text-[12px] font-semibold text-ink-2">Kaedah Bayaran Ansuran</span>
            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Kaedah Bayaran Ansuran">
                @foreach (\App\Enums\InstallmentPayMethod::cases() as $pm)
                    @php $on = $form->paymentMethod === $pm->value; @endphp
                    <button type="button" wire:click="$set('form.paymentMethod', '{{ $pm->value }}')" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}"
                            @class(['inline-flex min-h-10 items-center gap-[7px] rounded-[9px] px-[13px] py-[9px] text-[12.5px] font-semibold', 'bg-primary text-white' => $on, 'border border-border bg-bg text-ink-3' => ! $on])>
                        <i class="ph ph-{{ $pm->icon() }} text-[16px]"></i>{{ $pm->label() }}
                    </button>
                @endforeach
            </div>
            @if ($form->paymentMethod === \App\Enums\InstallmentPayMethod::Manual->value)
                <span class="mt-3 mb-2 block text-[12px] font-semibold text-ink-2">Muat Naik Resit Bank <span class="font-normal text-faint">(PNG atau PDF)</span></span>
                <label class="flex min-h-11 cursor-pointer items-center gap-[10px] rounded-[10px] border-[1.5px] border-dashed border-gold bg-[#FCFBF5] px-4 py-[14px] text-primary">
                    <i class="ph ph-upload-simple text-[20px]" wire:loading.remove wire:target="form.proof"></i>
                    <i class="ph ph-circle-notch animate-spin text-[20px]" wire:loading wire:target="form.proof"></i>
                    <span class="flex-1 truncate text-[13px] font-semibold">{{ $form->proof ? $form->proof->getClientOriginalName().' ✓' : 'Klik untuk muat naik resit (PNG / PDF)' }}</span>
                    <input type="file" wire:model="form.proof" accept="image/png,application/pdf" class="hidden">
                </label>
                @error('form.proof')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
            @endif
        </div>
    </form>
    <x-slot:footer>
        <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
        <x-ui.button type="submit" form="plan-form" icon="check" wire:loading.attr="disabled" wire:target="save,form.proof">Simpan Pelan</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
