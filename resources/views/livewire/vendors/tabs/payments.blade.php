@php
    $done = $payment?->status === \App\Enums\VendorPaymentStatus::Completed;
    $locked = ! $canManage || $done;
    $zone = 'flex cursor-pointer flex-col items-center rounded-[11px] border-[1.5px] border-dashed border-[#CBD5C0] bg-[#fbfcf9] px-4 py-[22px] text-center';
@endphp

<div>
    <div class="mb-4 flex items-center gap-[9px] rounded-[9px] bg-primary-soft px-[14px] py-[10px]"><i class="ph ph-lock-key text-[17px] text-primary"></i><span class="text-[12.5px] font-semibold text-primary">HQ Only &mdash; hanya pegawai HQ boleh urus &amp; luluskan bayaran vendor.</span></div>

    @if ($payments->isEmpty())
        <div class="rounded-[12px] border border-border bg-surface"><x-ui.empty-state icon="wallet" title="Belum ada rekod bayaran untuk vendor ini." /></div>
    @else
        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
            {{-- Records --}}
            <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
                <div class="px-[22px] pt-[18px] pb-3"><h2 class="text-[15px] font-bold text-ink">Rekod Bayaran</h2></div>
                @foreach ($payments as $p)
                    <button type="button" wire:click="select({{ $p->id }})" wire:key="pay-{{ $p->id }}"
                            @class(['flex min-h-14 w-full items-center gap-[10px] border-t border-divider px-[22px] py-[14px] text-left', 'bg-[#f7f8f2]' => $payment?->id === $p->id])>
                        <div class="min-w-0 flex-1"><div class="text-[12.5px] font-bold text-primary dark:text-[#c9ce93]">{{ $p->purchaseOrder->po_no }}</div><div class="mt-0.5 text-[12px] text-faint">{{ rm($p->amount_sen) }}</div></div>
                        <x-ui.badge :tone="$p->status->tone()" class="!text-[11px] !px-[11px]">{{ $p->status->label() }}</x-ui.badge>
                    </button>
                @endforeach
            </section>

            @if ($payment)
                <div class="flex flex-col gap-5">
                    {{-- Header + stepper --}}
                    <section class="rounded-[12px] border border-border bg-surface px-5 py-[22px] md:px-6">
                        <div class="mb-[18px] flex flex-wrap items-center justify-between gap-[10px]"><div><h2 class="text-[15.5px] font-bold text-ink">{{ $payment->purchaseOrder->po_no }}</h2><p class="mt-0.5 text-[12px] text-faint">Jumlah {{ rm($payment->amount_sen) }}</p></div><x-ui.badge :tone="$payment->status->tone()" class="!text-[11px] !px-[11px]">{{ $payment->status->label() }}</x-ui.badge></div>
                        <div class="flex items-start justify-between">
                            @foreach (\App\Enums\VendorPaymentStatus::cases() as $st)
                                @php $isDone = $st->step() < $payment->status->step() || ($done && $st === \App\Enums\VendorPaymentStatus::Completed); $isCur = $st === $payment->status && ! $done; @endphp
                                <div class="relative flex flex-1 flex-col items-center gap-2">
                                    @unless ($loop->last)<span @class(['absolute top-[19px] left-1/2 h-0.5 w-full', 'bg-primary' => $st->step() < $payment->status->step(), 'bg-border' => $st->step() >= $payment->status->step()])></span>@endunless
                                    <span @class(['relative z-[1] flex size-[38px] items-center justify-center rounded-full', 'bg-primary text-white' => $isDone, 'bg-gold text-white' => $isCur, 'bg-divider text-faint' => ! $isDone && ! $isCur])><i class="{{ $isDone ? 'ph-fill ph-check' : ($isCur ? 'ph-fill ph-circle' : 'ph ph-circle') }} text-[16px]"></i></span>
                                    <span @class(['text-center text-[11.5px] font-semibold', 'text-primary dark:text-[#c9ce93]' => $isDone, 'text-gold-ink' => $isCur, 'text-faint' => ! $isDone && ! $isCur])>{{ $st->label() }}</span>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    {{-- Payment Information --}}
                    <section class="rounded-[12px] border border-border bg-surface px-5 py-[22px] md:px-6">
                        <h2 class="mb-4 text-[15px] font-bold text-ink">Payment Information</h2>
                        <fieldset @disabled($locked) class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <x-ui.field label="Payment Date" type="date" wire:model="paymentDate" />
                            <x-ui.field label="Approved By (HQ PIC)" wire:model="approvedBy" />
                            <div>
                                <x-ui.field label="Bank" wire:model="bank" list="vendor-banks" />
                                <datalist id="vendor-banks">@foreach ($banks as $b)<option value="{{ $b }}"></option>@endforeach</datalist>
                                <div class="mt-[7px] flex flex-wrap gap-1.5">
                                    @foreach ($banks as $b)
                                        <button type="button" wire:click="setBank('{{ $b }}')" @class(['min-h-8 rounded-[7px] px-[10px] py-[5px] text-[11px] font-semibold max-md:min-h-10', 'bg-primary text-white' => $bank === $b, 'border border-border bg-bg text-muted' => $bank !== $b])>{{ $b }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <x-ui.field label="Transfer Reference Number" wire:model="reference" class="tabular-nums" />
                        </fieldset>
                        @unless ($locked)
                            <div class="mt-4 flex justify-end"><x-ui.button icon="floppy-disk" wire:click="save" wire:loading.attr="disabled" wire:target="save,receipt,advice" class="max-md:w-full">Simpan Maklumat</x-ui.button></div>
                        @endunless
                    </section>

                    {{-- Receipt upload --}}
                    <section class="rounded-[12px] border border-border bg-surface px-5 py-[22px] md:px-6">
                        <h2 class="mb-1 text-[15px] font-bold text-ink">Upload Payment Receipt</h2>
                        <p class="mb-4 text-[12px] text-faint">HQ upload gambar resit bank (JPG/PNG) atau PDF payment advice (optional)</p>
                        @unless ($locked)
                            <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2">
                                <label class="{{ $zone }}"><i class="ph ph-image text-[30px] text-primary"></i><span class="mt-2 text-[12.5px] font-semibold text-ink-2">{{ $receipt ? $receipt->getClientOriginalName() : 'Gambar Resit Bank' }}</span><span class="mt-[3px] text-[11px] text-faint">JPG / PNG &middot; maks 5MB</span><input type="file" wire:model="receipt" accept="image/png,image/jpeg" class="hidden"></label>
                                <label class="{{ $zone }}"><i class="ph ph-file-pdf text-[30px] text-danger"></i><span class="mt-2 text-[12.5px] font-semibold text-ink-2">{{ $advice ? $advice->getClientOriginalName() : 'PDF Payment Advice' }}</span><span class="mt-[3px] text-[11px] text-faint">PDF &middot; optional</span><input type="file" wire:model="advice" accept="application/pdf" class="hidden"></label>
                            </div>
                            @error('receipt')<p class="mt-2 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                            @error('advice')<p class="mt-2 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                            @if ($receipt || $advice)<p class="mt-2 text-[11.5px] text-muted">Klik <b>Simpan Maklumat</b> untuk memuat naik.</p>@endif
                        @endunless
                        @foreach (['receipt' => $receiptMedia, 'advice' => $adviceMedia] as $collection => $media)
                            @if ($media)
                                <div class="mt-[14px] flex items-center gap-[11px] rounded-[9px] border border-border bg-bg px-[14px] py-[11px]">
                                    <i class="{{ $media->mime_type === 'application/pdf' ? 'ph-fill ph-file-pdf text-danger' : 'ph-fill ph-file-image text-primary' }} text-[22px]"></i>
                                    <div class="min-w-0 flex-1"><div class="truncate text-[12.5px] font-semibold text-ink">{{ $media->file_name }}</div><div class="text-[11px] text-faint">{{ $media->human_readable_size }} &middot; dimuat naik {{ tarikh($media->created_at) }}</div></div>
                                    <a href="{{ \App\Models\VendorPayment::mediaUrl($media) }}" target="_blank" rel="noopener" class="flex size-[30px] items-center justify-center text-primary max-md:size-11" aria-label="Muat turun"><i class="ph ph-download-simple text-[18px]"></i></a>
                                    @unless ($locked)
                                        <button type="button" wire:click="removeFile('{{ $collection }}')" wire:confirm="Buang fail ini?" title="Batal / buang resit" class="flex size-[30px] shrink-0 items-center justify-center rounded-[8px] border border-[#F7CFCF] text-danger max-md:size-11" aria-label="Buang"><i class="ph ph-trash text-[16px]"></i></button>
                                    @endunless
                                </div>
                            @endif
                        @endforeach
                        @if (! $receiptMedia && ! $adviceMedia && $locked)
                            <p class="text-[12.5px] text-faint">Tiada resit dimuat naik.</p>
                        @endif
                    </section>

                    {{-- Sahkan Bayaran (HQ) --}}
                    <section @class(['rounded-[12px] border bg-surface px-5 py-[22px] md:px-6', 'border-[#BBE5C9]' => $done, 'border-border' => ! $done])>
                        <div class="mb-1.5 flex items-center gap-[10px]"><i class="ph ph-seal-check text-[20px] text-primary"></i><h2 class="text-[15px] font-bold text-ink">Sahkan Bayaran (HQ)</h2></div>
                        <p class="mb-[14px] text-[12.5px] leading-normal text-muted">Hanya Superadmin &amp; Admin HQ. Sahkan bayaran ini untuk tukar status kepada Payment Completed.</p>
                        @if ($done)
                            <div class="flex flex-wrap items-center gap-[10px]">
                                <div class="flex items-center gap-2 rounded-[9px] bg-success-soft px-[14px] py-3 text-[13px] font-semibold text-success"><i class="ph-fill ph-check-circle text-[17px]"></i> Bayaran disahkan — Payment Completed.</div>
                                @if ($canConfirm)<x-ui.button variant="secondary" icon="pencil-simple" wire:click="reopen" class="!border-primary !text-primary">Edit</x-ui.button>@endif
                            </div>
                        @elseif ($canConfirm)
                            <x-ui.button variant="success" icon="fill check-circle" wire:click="confirm" wire:confirm="Sahkan bayaran {{ rm($payment->amount_sen) }} untuk {{ $payment->purchaseOrder->po_no }}?" class="!font-bold max-md:w-full">Sah Bayaran</x-ui.button>
                        @else
                            <p class="text-[12.5px] text-faint"><i class="ph ph-lock-simple align-[-2px]"></i> Menunggu pengesahan HQ.</p>
                        @endif
                    </section>
                </div>
            @endif
        </div>
    @endif
</div>
