@php
    $product = $form->product();
    $breakdown = $form->breakdown();
    $promoTyped = trim($form->promo) !== '';
    $promoOk = $promoTyped && $form->promoModel();
@endphp

{{-- Tempahan Baharu (max 640px) — product drives service/animal/package/country/price. --}}
<x-ui.modal wire:model="showForm" title="Tempahan Baharu" subtitle="No. auto-generate mengikut servis & haiwan" max-width="640px" :footer-border="false">
    <form id="order-form" wire:submit="save" class="grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
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
        <x-ui.field label="Emel" hint="(pilihan)" type="email" wire:model="form.email" placeholder="nama@email.com" />

        <x-ui.field label="Produk" as="select" wire:model.live="form.productId" span>
            <option value="">Pilih produk</option>
            @foreach ($this->products as $p)
                <option value="{{ $p->id }}">{{ $p->name }} — {{ rm($p->price_sen) }} ({{ $p->stock }} unit)</option>
            @endforeach
        </x-ui.field>

        @php $ro = 'bg-bg text-ink-3'; @endphp
        <x-ui.field label="Servis" :value="$product?->service->label() ?? '—'" readonly :class="$ro" />
        <x-ui.field label="Haiwan" :value="$product?->animal->label() ?? '—'" readonly :class="$ro" />
        <x-ui.field label="Jenis Pakej" :value="$product?->package->name ?? '—'" readonly :class="$ro" />
        <x-ui.field label="Kuantiti" type="number" min="1" wire:model.live.debounce.400ms="form.quantity" inputmode="numeric" />
        <x-ui.field label="Harga (RM)" hint="(auto)" :value="$breakdown ? rm($breakdown->subtotalSen) : '—'" readonly class="bg-bg font-bold !text-primary" />
        <x-ui.field label="Negara Pelaksanaan" :value="$product?->country->name ?? '—'" readonly :class="$ro" />
        <x-ui.field label="Tahun Pelaksanaan" wire:model="form.year" inputmode="numeric" maxlength="4" />
        <x-ui.field label="Tarikh Pelaksanaan" type="date" wire:model="form.implementationDate" />

        <div>
            <x-ui.field label="Kod Promosi" wire:model.live.debounce.500ms="form.promo" list="nq-promo-list" placeholder="cth. AWALQURBAN" class="uppercase" autocomplete="off" />
            <datalist id="nq-promo-list">
                @foreach ($promoCodes as $code)
                    <option value="{{ $code }}"></option>
                @endforeach
            </datalist>
            @if ($promoTyped && ! $errors->has('form.promo'))
                <p @class(['mt-1 text-[11.5px] font-medium', 'text-success' => $promoOk, 'text-danger' => ! $promoOk])>
                    {{ $promoOk ? 'Kod sah — diskaun '.$form->promoModel()->discountLabel() : 'Kod tidak sah atau telah tamat.' }}
                </p>
            @endif
        </div>

        {{-- Summary --}}
        <div class="col-span-full flex flex-col gap-1.5 rounded-[10px] bg-primary px-[18px] py-[14px]">
            <div class="flex items-center justify-between text-[12px] text-[#cdd0b0]"><span>Harga Produk</span><span>{{ $breakdown ? rm($breakdown->subtotalSen) : 'RM 0' }}</span></div>
            <div class="flex items-center justify-between text-[12px] text-[#cdd0b0]"><span>Diskaun Promosi</span><span>- {{ $breakdown ? rm($breakdown->discountSen) : 'RM 0' }}</span></div>
            <div class="mt-0.5 flex items-center justify-between gap-3 border-t border-white/20 pt-2">
                <span class="text-[13px] font-semibold text-[#e7d9a8]">Jumlah Keseluruhan</span>
                <span class="text-[19px] font-extrabold text-white">{{ $breakdown ? rm($breakdown->totalSen) : 'RM 0' }}</span>
            </div>
        </div>

        {{-- Jenis Bayaran + proof --}}
        <div class="col-span-full">
            <span class="mb-2 block text-[12px] font-semibold text-ink-2">Jenis Bayaran</span>
            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Jenis Bayaran">
                @foreach (\App\Enums\PaymentMethod::cases() as $pm)
                    @php $on = $form->paymentMethod === $pm->value; @endphp
                    <button type="button" wire:click="$set('form.paymentMethod', '{{ $pm->value }}')" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}"
                            @class(['inline-flex min-h-10 items-center gap-[7px] rounded-[9px] border px-[14px] py-[9px] text-[13px] font-semibold', 'border-primary bg-primary-soft text-primary' => $on, 'border-border bg-surface text-muted' => ! $on])>
                        <i class="ph ph-{{ $pm->icon() }} text-[16px]"></i>{{ $pm->label() }}
                    </button>
                @endforeach
            </div>
            <label class="mt-[10px] flex min-h-11 cursor-pointer items-center gap-[9px] rounded-[9px] border-[1.5px] border-dashed border-gold bg-[#FCFBF5] px-[14px] py-[11px] text-primary">
                <i class="ph ph-upload-simple text-[18px]" wire:loading.remove wire:target="form.proof"></i>
                <i class="ph ph-circle-notch animate-spin text-[18px]" wire:loading wire:target="form.proof"></i>
                <span class="flex-1 truncate text-[12.5px] font-semibold">{{ $form->proof ? 'Bukti bayaran: '.$form->proof->getClientOriginalName() : 'Muat naik bukti bayaran' }}</span>
                <span class="text-[11px] text-faint">PNG / PDF</span>
                <input type="file" wire:model="form.proof" accept="image/png,image/jpeg,application/pdf" class="hidden">
            </label>
            @if ($form->proof)
                <button type="button" wire:click="removeProof" class="mt-[7px] inline-flex items-center gap-[5px] text-[12px] text-danger"><i class="ph ph-trash text-[14px]"></i> Buang fail</button>
            @endif
            @error('form.proof')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
        </div>
    </form>
    <x-slot:footer>
        <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
        <x-ui.button type="submit" form="order-form" icon="check" wire:loading.attr="disabled" wire:target="save,form.proof">Simpan Tempahan</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
