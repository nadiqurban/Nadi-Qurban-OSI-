@php
    $o = $order;
    $payment = $o->payment;
    $pill = $payment?->pill() ?? ['label' => $o->payment_method->label(), 'tone' => 'primary'];
    $proof = $payment?->proof();
    $info = 'text-[11.5px] tracking-[.4px] text-faint uppercase';
@endphp

<div>
    <a href="{{ route('orders.index') }}" wire:navigate class="mb-[18px] inline-flex min-h-11 items-center gap-[7px] text-[13.5px] font-semibold text-primary"><i class="ph ph-arrow-left text-[17px]"></i> Kembali ke senarai</a>

    <div class="mb-6 flex flex-wrap items-end gap-3 md:gap-4">
        <div class="mr-auto min-w-0 max-md:w-full">
            <h1 class="text-[20px] font-bold text-ink md:text-[23px]">{{ $o->order_no }}</h1>
            <p class="mt-1 text-[13.5px] text-muted">Tracking {{ $o->tracking_no }} · {{ $o->customer->name }}</p>
        </div>
        <x-ui.status-badge :status="$o->status" />
        <x-ui.button variant="secondary" icon="printer" target="_blank" :href="route('orders.receipt', $o)">Cetak Resit</x-ui.button>
        <x-ui.button variant="gold" icon="download-simple" :href="route('orders.receipt', [$o, 'muat-turun' => 1])">Muat Turun</x-ui.button>
        @if ($canManage)
            <x-ui.button icon="pencil-simple" wire:click="openEdit" mobile-block>Kemaskini</x-ui.button>
        @endif
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <div class="flex min-w-0 flex-col gap-5">
            {{-- Link Tracking Pelanggan --}}
            <section class="rounded-[12px] bg-primary px-5 py-5 md:px-6" x-data="{ copied: false }">
                <div class="mb-[10px] flex items-center gap-2"><i class="ph ph-link-simple text-[18px] text-gold"></i><h2 class="text-[14px] font-bold text-white">Link Tracking Pelanggan</h2></div>
                <p class="mb-3 text-[12px] leading-[1.5] text-[#cfe0d7]">Salin pautan ini dan hantar kepada pelanggan untuk semak status tempahan secara langsung.</p>
                <div class="flex flex-wrap items-center gap-[10px]">
                    <div class="flex min-w-0 flex-1 basis-[220px] items-center gap-[9px] rounded-[9px] border border-white/18 bg-white/10 px-[13px] py-[11px]">
                        <i class="ph ph-globe text-[16px] text-[#cfe0d7]"></i>
                        <span class="truncate text-[12.5px] text-white tabular-nums">{{ $o->trackingUrl() }}</span>
                    </div>
                    <button type="button" x-on:click="navigator.clipboard?.writeText(@js($o->trackingUrl())); copied = true; setTimeout(() => copied = false, 1800)"
                            :class="copied ? 'bg-success' : 'bg-gold'"
                            class="inline-flex min-h-11 items-center gap-[7px] rounded-[9px] px-4 py-[11px] text-[13px] font-bold text-white max-md:w-full max-md:justify-center">
                        <i class="text-[16px]" :class="copied ? 'ph-fill ph-check' : 'ph ph-copy'"></i> <span x-text="copied ? 'Disalin!' : 'Salin'">Salin</span>
                    </button>
                </div>
            </section>

            {{-- Maklumat Pelanggan / Butiran Tempahan --}}
            @foreach (['Maklumat Pelanggan' => \App\Support\OrderPresenter::customerInfo($o), 'Butiran Tempahan' => \App\Support\OrderPresenter::orderInfo($o)] as $heading => $rows)
                <x-ui.card :title="$heading">
                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach ($rows as $d)
                            <div class="min-w-0"><div class="{{ $info }}">{{ $d['k'] }}</div><div class="mt-1 text-[14px] font-semibold break-words text-ink">{{ $d['v'] }}</div></div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endforeach

            {{-- Maklumat Bayaran --}}
            <x-ui.card title="Maklumat Bayaran">
                <x-slot:actions><x-ui.badge :tone="$pill['tone']" variant="tag" class="text-[12px] font-semibold">{{ $pill['label'] }}</x-ui.badge></x-slot:actions>
                <div class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                    <div><div class="{{ $info }}">Jenis Bayaran</div><div class="mt-1 text-[14px] font-semibold text-ink">{{ $o->is_instalment ? 'Ansuran' : $o->payment_method->label() }}</div></div>
                    <div><div class="{{ $info }}">Jumlah</div><div class="mt-1 text-[14px] font-semibold text-ink">{{ rm($o->total_sen) }}</div></div>
                    @if ($payment)
                        <div><div class="{{ $info }}">Status Bayaran</div><div class="mt-1"><x-ui.badge :tone="$payment->status->tone()">{{ $payment->status->label() }}</x-ui.badge></div></div>
                        @if ($payment->rejection_reason)
                            <div><div class="{{ $info }}">Sebab Ditolak</div><div class="mt-1 text-[13px] text-danger">{{ $payment->rejection_reason }}</div></div>
                        @endif
                    @endif
                </div>
                <div class="mt-[18px] border-t border-divider pt-4">
                    <div class="{{ $info }} mb-[10px]">Bukti Bayaran</div>
                    @if ($proof)
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="button" wire:click="$set('showProof', true)" class="inline-flex min-h-11 items-center gap-2 rounded-[9px] bg-gold-soft px-[14px] py-[10px] text-[13px] font-semibold text-gold"><i class="ph ph-receipt text-[17px]"></i> Lihat Resit</button>
                            <span class="truncate text-[12.5px] text-muted">{{ $proof->file_name }}</span>
                            @if ($canManage)
                                <label class="inline-flex min-h-11 cursor-pointer items-center gap-[7px] rounded-[9px] bg-primary-soft px-[14px] py-[10px] text-[12.5px] font-semibold text-primary">
                                    <i class="ph ph-pencil-simple text-[15px]"></i> Tukar Resit
                                    <input type="file" wire:model="proof" accept="image/png,image/jpeg,application/pdf" class="hidden">
                                </label>
                            @endif
                        </div>
                        <button type="button" wire:click="$set('showProof', true)" class="mt-[14px] block w-full max-w-[360px] rounded-[10px] border border-border bg-bg p-3 text-left">
                            <x-proof-preview wire:key="proof-thumb-{{ $proof->id }}" :url="$payment->proofUrl()" :is-pdf="$payment->proofIsPdf()" max="220px" />
                        </button>
                    @elseif ($canManage)
                        <label class="flex min-h-11 max-w-[360px] cursor-pointer items-center gap-[9px] rounded-[9px] border-[1.5px] border-dashed border-gold bg-[#FCFBF5] px-[14px] py-3 text-primary">
                            <i class="ph ph-upload-simple text-[18px]" wire:loading.remove wire:target="proof"></i>
                            <i class="ph ph-circle-notch animate-spin text-[18px]" wire:loading wire:target="proof"></i>
                            <span class="flex-1 text-[12.5px] font-semibold">Muat naik bukti bayaran</span><span class="text-[11px] text-faint">PNG / PDF</span>
                            <input type="file" wire:model="proof" accept="image/png,image/jpeg,application/pdf" class="hidden">
                        </label>
                    @else
                        <p class="text-[12.5px] text-faint">Tiada bukti bayaran.</p>
                    @endif
                    @error('proof')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                </div>
            </x-ui.card>

            {{-- Senarai Peserta --}}
            @if ($o->quantity > 1 || count($participants) > 1)
                <x-ui.card title="Senarai Peserta" :subtitle="collect($participants)->filter()->count().' peserta untuk tempahan kuantiti '.$o->quantity">
                    @if ($canManage)
                        <x-slot:actions>
                            <button type="button" wire:click="addParticipant" class="inline-flex min-h-10 items-center gap-1.5 rounded-[8px] bg-primary-soft px-[13px] py-2 text-[12.5px] font-semibold text-primary"><i class="ph ph-plus text-[15px]"></i> Tambah Peserta</button>
                        </x-slot:actions>
                    @endif
                    <form wire:submit="saveParticipants" class="flex flex-col gap-[9px]">
                        @foreach ($participants as $i => $name)
                            <div class="flex items-center gap-[10px]" wire:key="participant-{{ $i }}">
                                <span class="flex size-[26px] shrink-0 items-center justify-center rounded-[7px] bg-bg text-[12px] font-bold text-primary">{{ $i + 1 }}</span>
                                <input wire:model="participants.{{ $i }}" placeholder="Nama peserta / bahagian" @disabled(! $canManage)
                                       class="min-w-0 flex-1 rounded-[8px] border border-border bg-surface px-3 py-[9px] text-[13px] text-ink outline-none focus:border-primary max-md:min-h-11">
                                @if ($canManage)
                                    <button type="button" wire:click="removeParticipant({{ $i }})" class="flex size-11 items-center justify-center rounded-[8px] border border-border text-danger md:size-8" aria-label="Buang peserta {{ $i + 1 }}"><i class="ph ph-trash text-[15px]"></i></button>
                                @endif
                            </div>
                        @endforeach
                        @if (count($participants) > $o->quantity)
                            <p class="text-[11.5px] text-warning">Bilangan peserta melebihi kuantiti tempahan ({{ $o->quantity }}).</p>
                        @endif
                        @if ($canManage)
                            <div class="mt-2 flex justify-end"><x-ui.button type="submit" size="sm" icon="floppy-disk">Simpan Peserta</x-ui.button></div>
                        @endif
                    </form>
                </x-ui.card>
            @endif
        </div>

        {{-- Aliran Kerja --}}
        <x-ui.card title="Aliran Kerja" subtitle="Status pelaksanaan tempahan" class="lg:sticky lg:top-[96px]">
            <x-ui.stepper :steps="\App\Support\OrderPresenter::workflow($o)" />
        </x-ui.card>
    </div>

    {{-- Bukti Bayaran viewer --}}
    @if ($proof)
        <x-ui.modal wire:model="showProof" title="Bukti Bayaran" :subtitle="$proof->file_name" icon="receipt" tone="gold" max-width="560px"
                    body-class="flex flex-col items-center bg-bg px-4 py-[18px] md:px-[22px]">
            <x-proof-preview wire:key="proof-modal-{{ $proof->id }}" :url="$payment->proofUrl()" :is-pdf="$payment->proofIsPdf()" :name="$proof->file_name" />
            <a href="{{ $payment->proofUrl() }}" target="_blank" rel="noopener" class="mt-4 inline-flex min-h-11 items-center gap-[7px] rounded-[8px] bg-primary-soft px-[15px] py-[9px] text-[12.5px] font-semibold text-primary hover:text-primary"><i class="ph ph-arrow-square-out text-[15px]"></i> Buka dalam tab baharu</a>
        </x-ui.modal>
    @endif

    {{-- Kemaskini --}}
    @if ($canManage)
        <x-ui.modal wire:model="showEdit" title="Kemaskini Tempahan" :subtitle="$o->order_no" icon="pencil-simple" max-width="640px" :footer-border="false">
            <form id="edit-order-form" wire:submit="saveEdit" class="grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
                <x-ui.field label="Nama Pelanggan" wire:model="edit.name" span />
                <x-ui.field label="Alamat (No. & Jalan)" wire:model="edit.address" span />
                <x-ui.field label="Poskod" wire:model="edit.postcode" inputmode="numeric" maxlength="5" />
                <x-ui.field label="Bandar" wire:model="edit.city" />
                <x-ui.field label="Negeri" as="select" wire:model="edit.state" span>
                    @foreach (\App\Enums\MalaysianState::cases() as $st)
                        <option value="{{ $st->value }}">{{ $st->label() }}</option>
                    @endforeach
                </x-ui.field>
                <x-ui.field label="No. Telefon" wire:model="edit.phone" inputmode="tel" />
                <x-ui.field label="Emel" type="email" wire:model="edit.email" />
                <x-ui.field label="Tahun Pelaksanaan" wire:model="edit.year" inputmode="numeric" maxlength="4" />
                <x-ui.field label="Tarikh Pelaksanaan" type="date" wire:model="edit.implementation_date" />
                <x-ui.field label="Catatan" as="textarea" rows="2" wire:model="edit.notes" span />
                <p class="col-span-full text-[11.5px] text-faint">Produk, kuantiti dan harga dikunci selepas tempahan dicipta. Batalkan dan cipta tempahan baharu jika perlu diubah.</p>
            </form>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button type="submit" form="edit-order-form" icon="check">Simpan</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
