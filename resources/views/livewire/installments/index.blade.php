@php
    $plans = $this->plans;
    $hasFilters = $search !== '' || $service !== '' || $animal !== '' || $country !== '' || $status !== '';
    $iconBtn = 'inline-flex size-[30px] items-center justify-center rounded-[8px] border border-border max-md:size-11';
@endphp

<div>
    <x-ui.page-header title="Bayaran Ansuran" subtitle="Urus pelan ansuran pelanggan & jejak bayaran bulanan." :breadcrumb="['Operasi', 'Bayaran Ansuran']">
        <x-slot:actions>
            <x-ui.button variant="success" icon="microsoft-excel-logo" wire:click="export" class="max-md:flex-1">Eksport Excel</x-ui.button>
            @if ($canManage)
                <x-ui.button icon="plus" wire:click="create" class="max-md:flex-1">Pelan Baharu</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-5">
        @foreach ($this->stats() as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" @class(['max-lg:col-span-2' => $loop->last]) />
        @endforeach
    </div>

    <div class="mb-[14px] flex flex-wrap items-center gap-2">
        <x-ui.tabs wire-model="tab" :active="$tab" :items="collect(\App\Livewire\Installments\Index::TABS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => $this->counts[$key]])->values()->all()" />
        @if ($canManage && $selectedCount > 0)
            <div class="flex flex-wrap items-center gap-2 md:ml-auto max-md:w-full">
                @if ($tab !== 'batal')
                    <x-ui.button variant="danger-soft" icon="x-circle" wire:click="openCancel" class="max-md:flex-1">Batal ({{ $selectedCount }})</x-ui.button>
                @endif
                @if ($sendableCount > 0)
                    <x-ui.button variant="success" icon="paper-plane-tilt" wire:click="sendBulk" wire:confirm="Hantar {{ $sendableCount }} pelan selesai ke Pengesahan Bayaran?" class="max-md:flex-1">Hantar Pukal ({{ $sendableCount }})</x-ui.button>
                @endif
            </div>
        @endif
    </div>

    <x-ui.filter-bar plain class="!mb-4" :has-filters="$hasFilters" reset-action="$wire.clearFilters()" :active-count="collect([$service, $animal, $country, $status])->filter()->count()">
        <x-slot:search>
            <label class="flex min-w-[220px] flex-1 items-center gap-[9px] rounded-[9px] border border-border bg-surface px-[13px] py-[9px] max-md:min-h-11">
                <i class="ph ph-magnifying-glass text-[16px] text-faint"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cari nama, telefon, no. tempahan…" aria-label="Cari pelan ansuran" class="flex-1 bg-transparent text-[13px] text-ink outline-none max-md:text-[16px]">
            </label>
        </x-slot:search>
        <x-slot:filters>
            <x-ui.mini-select wire:model.live="service" placeholder="Semua Servis" :options="collect(\App\Enums\Service::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" aria-label="Servis" class="!py-[9px]" />
            <x-ui.mini-select wire:model.live="animal" placeholder="Semua Haiwan" :options="collect(\App\Enums\Animal::cases())->mapWithKeys(fn ($a) => [$a->value => $a->label()])->all()" aria-label="Haiwan" class="!py-[9px]" />
            <x-ui.mini-select wire:model.live="country" placeholder="Semua Negara" :options="$countries" aria-label="Negara" class="!py-[9px]" />
            <x-ui.mini-select wire:model.live="status" placeholder="Semua Status" :options="collect([\App\Enums\InstallmentPlanStatus::Ongoing, \App\Enums\InstallmentPlanStatus::Late, \App\Enums\InstallmentPlanStatus::Completed])->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" aria-label="Status" class="!py-[9px]" />
        </x-slot:filters>
    </x-ui.filter-bar>

    <x-ui.data-table cols="34px 1.25fr 1.4fr 1.15fr 0.9fr 1fr 0.75fr 0.85fr 1.35fr 0.95fr 0.95fr 0.9fr 0.95fr" min-width="1240px">
        <x-slot:head>
            <x-ui.select-all :checked="$this->allSelected()" />
            <span>No. Tempahan</span><span>Pelanggan</span><span>Servis</span><span>Pakej</span><span>Negara</span><span class="text-center">Kuantiti</span><span class="text-right">Jumlah</span><span>Kemajuan Ansuran</span><span>Ansuran/Bulan</span><span class="text-right">Baki Belum Bayar</span><span class="text-center">Status</span><span class="text-right">Link</span>
        </x-slot:head>
        @foreach ($plans as $p)
            @php
                $paid = $p->paidCount();
                $baki = $p->balanceSen();
                $cancelled = $p->status === \App\Enums\InstallmentPlanStatus::Cancelled;
                $done = $p->status === \App\Enums\InstallmentPlanStatus::Completed;
                $editable = $canManage && ! $cancelled && ! $p->sent_at;
            @endphp
            <x-ui.tr wire:key="plan-{{ $p->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    <x-ui.checkbox value="{{ $p->id }}" wire:model.live="selected" aria-label="Pilih {{ $p->order_no }}" />
                    <a href="{{ $p->portalUrl() }}" target="_blank" rel="noopener" class="text-[13px] font-bold text-primary md:hidden">{{ $p->order_no }}</a>
                    <span class="ml-auto md:hidden"><x-ui.badge :tone="$p->status->tone()" class="!text-[11px] !px-[11px]">{{ $p->status->label() }}</x-ui.badge></span>
                </x-ui.td>
                <x-ui.td mobile="hide" class="md:!min-w-max">
                    <a href="{{ $p->portalUrl() }}" target="_blank" rel="noopener" title="Buka Portal Ansuran pelanggan" class="font-semibold whitespace-nowrap text-primary hover:text-primary-hover dark:text-[#c9ce93]">{{ $p->order_no }}</a>
                    @if ($cancelled)<span class="mt-[3px] block text-[10.5px] font-semibold text-danger">Sebab: {{ $p->cancel_reason }}</span>@endif
                </x-ui.td>
                <x-ui.td span>
                    <span class="block truncate font-semibold text-ink">{{ $p->customer->name }}</span>
                    <span class="mt-0.5 block text-[11.5px] text-faint">{{ $p->customer->phone }}</span>
                    @if ($cancelled)<span class="mt-[3px] block text-[10.5px] font-semibold text-danger md:hidden">Sebab: {{ $p->cancel_reason }}</span>@endif
                </x-ui.td>
                <x-ui.td label="Servis" class="whitespace-nowrap text-ink-3 md:!min-w-max">{{ $p->ibadahLabel() }}</x-ui.td>
                <x-ui.td label="Pakej" class="text-ink-3">{{ $p->package_name }}</x-ui.td>
                <x-ui.td label="Negara" class="text-ink-3">{{ $p->country->name }}</x-ui.td>
                <x-ui.td label="Kuantiti" align="center" class="font-semibold text-ink">{{ $p->quantity }}</x-ui.td>
                <x-ui.td label="Jumlah" align="right" class="font-semibold text-ink">{{ rm($p->total_sen) }}</x-ui.td>
                <x-ui.td label="Kemajuan Ansuran" span>
                    <div class="mb-[5px] flex items-center justify-between text-[11.5px] text-muted"><span>{{ $paid }}/{{ $p->months }} bulan</span><span class="font-bold text-primary dark:text-[#c9ce93]">{{ $p->progressPct() }}%</span></div>
                    <div class="flex gap-[3px]">
                        @foreach ($p->installments as $i)
                            <button type="button" @if ($editable) wire:click="segment({{ $p->id }}, {{ $i->seq }})" @else disabled @endif
                                    title="Bulan {{ $i->seq }} — {{ $i->isPaid() ? 'dibayar'.($editable ? ' (klik untuk batal)' : '') : ($editable ? 'klik jika telah dibayar' : 'belum dibayar') }}"
                                    aria-label="Bulan {{ $i->seq }} {{ $p->order_no }}"
                                    @class(['h-3.5 flex-1 rounded-[3px] max-md:h-5', $p->status->barClass() => $i->isPaid(), 'bg-border' => ! $i->isPaid(), 'cursor-pointer' => $editable, 'cursor-default' => ! $editable])></button>
                        @endforeach
                    </div>
                </x-ui.td>
                <x-ui.td label="Ansuran/Bulan" class="text-ink-3">{{ rm($p->monthly_sen) }}</x-ui.td>
                <x-ui.td label="Baki Belum Bayar" align="right" @class(['font-semibold', 'text-danger' => $baki > 0, 'text-success' => $baki === 0])>{{ rm($baki) }}</x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.badge :tone="$p->status->tone()" class="!text-[11px] !px-[11px]">{{ $p->status->label() }}</x-ui.badge></x-ui.td>
                <x-ui.td align="right" span class="md:!min-w-max">
                    <span class="inline-flex flex-wrap items-center justify-end gap-[10px] md:flex-nowrap max-md:w-full">
                        @if ($cancelled && $canManage)
                            <button type="button" wire:click="restore({{ $p->id }})" class="inline-flex min-h-8 items-center gap-[5px] text-[12.5px] font-bold text-success"><i class="ph ph-arrow-counter-clockwise text-[15px]"></i> Pulih</button>
                        @endif
                        @if ($done && ! $p->sent_at && $canManage)
                            <button type="button" id="plan-send-{{ $p->id }}" wire:click="send({{ $p->id }})" class="inline-flex min-h-8 items-center gap-[5px] text-[12.5px] font-bold text-success"><i class="ph ph-paper-plane-tilt text-[15px]"></i> Hantar</button>
                        @endif
                        @if ($p->sent_at)
                            <span class="inline-flex items-center gap-[5px] text-[12px] font-semibold text-success"><i class="ph-fill ph-check-circle text-[14px]"></i> Dihantar</span>
                        @endif
                        @if (! $done && ! $cancelled)
                            <a href="{{ $p->whatsappUrl() }}" target="_blank" rel="noopener" title="Hantar peringatan WhatsApp" aria-label="WhatsApp {{ $p->order_no }}" class="{{ $iconBtn }} text-success hover:text-success"><i class="ph ph-whatsapp-logo text-[16px]"></i></a>
                            <button type="button" wire:click="openLink({{ $p->id }})" title="Salin link bayaran" aria-label="Link bayaran {{ $p->order_no }}" class="{{ $iconBtn }} text-primary"><i class="ph ph-link text-[16px]"></i></button>
                        @endif
                        <button type="button" wire:click="openDetail({{ $p->id }})" title="Butiran pelanggan" aria-label="Butiran {{ $p->order_no }}" class="{{ $iconBtn }} text-primary"><i class="ph ph-user-circle text-[16px]"></i></button>
                        <button type="button" wire:click="openReceipt({{ $p->id }})" title="Resit" aria-label="Resit {{ $p->order_no }}" class="{{ $iconBtn }} text-primary"><i class="ph ph-receipt text-[16px]"></i></button>
                    </span>
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        @if ($plans->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="calendar-check" title="Tiada pelan ansuran untuk tapisan ini." />
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    {{-- ===================== Sahkan Bayaran Diterima ===================== --}}
    <x-ui.modal wire:model="showPay" title="Sahkan Bayaran Diterima" :subtitle="$payPlan ? 'Ansuran bulan '.$payMonth.' · '.rm($payPlan->installments->firstWhere('seq', $payMonth)?->amount_sen) : null" icon="bank" tone="success" max-width="400px" :footer-border="false">
        <span class="mb-2 block text-[12px] font-semibold text-ink-2">Kaedah Bayaran Diterima</span>
        <div class="flex flex-col gap-[9px]" role="radiogroup" aria-label="Kaedah Bayaran Diterima">
            @foreach (['Perbankan Internet' => 'globe', 'Mesin Deposit Tunai' => 'money'] as $label => $icon)
                @php $on = $payMethod === $label; @endphp
                <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}" wire:click="$set('payMethod', '{{ $label }}')"
                        @class(['inline-flex min-h-11 items-center gap-[9px] rounded-[9px] border px-[14px] py-3 text-[13px] font-semibold', 'border-success bg-success-soft text-success' => $on, 'border-border bg-bg text-ink-3' => ! $on])>
                    <i class="ph ph-{{ $icon }} text-[18px]"></i>{{ $label }}
                    @if ($on)<i class="ph-fill ph-check-circle ml-auto text-[17px] text-success"></i>@endif
                </button>
            @endforeach
        </div>
        @error('payMethod')<p class="mt-2 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button variant="success" icon="check" wire:click="confirmPayment">Sahkan &amp; Tanda Dibayar</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Butiran Pelanggan ===================== --}}
    <x-ui.modal wire:model="showDetail" title="Butiran Pelanggan" :subtitle="$activePlan?->customer->name" icon="user-circle" max-width="480px" :footer-border="false">
        @if ($activePlan && $showDetail)
            @php $c = $activePlan->customer; @endphp
            <div class="-mt-2 flex flex-col">
                @foreach ([
                    'No. Tempahan' => $activePlan->order_no,
                    'Nama Pelanggan' => $c->name,
                    'No. Telefon' => $c->phone ?: '-',
                    'Emel' => $c->email ?: '-',
                    'Alamat' => collect([$c->address, trim($c->postcode.' '.$c->city), $c->state])->filter()->implode(', ') ?: '-',
                    'Servis' => $activePlan->ibadahLabel(),
                    'Pakej' => $activePlan->package_name,
                    'Negara Pelaksanaan' => $activePlan->country->name,
                    'Kuantiti' => $activePlan->quantity,
                    'Jumlah Pelan' => rm($activePlan->total_sen),
                    'Ansuran Bulanan' => rm($activePlan->monthly_sen),
                    'Kemajuan' => $activePlan->paidCount().' / '.$activePlan->months.' bulan',
                ] as $k => $v)
                    <div class="flex items-center justify-between gap-3 border-b border-divider py-[11px]"><span class="shrink-0 text-[12.5px] text-faint">{{ $k }}</span><span class="text-right text-[13px] font-semibold text-ink">{{ $v }}</span></div>
                @endforeach
            </div>
            @if ($activePlan->quantity > 1)
                <div class="mt-4">
                    <div class="mb-2 text-[12.5px] font-bold text-primary dark:text-[#c9ce93]">Senarai Peserta ({{ $activePlan->quantity }})</div>
                    <div class="flex flex-col gap-2">
                        @foreach ($detailNames as $n => $name)
                            <div class="flex items-center gap-[9px]" wire:key="dn-{{ $n }}">
                                <span class="flex size-6 shrink-0 items-center justify-center rounded-[7px] bg-primary-soft text-[11px] font-bold text-primary">{{ $n + 1 }}</span>
                                <input type="text" wire:model="detailNames.{{ $n }}" placeholder="Nama peserta" @disabled(! $canManage)
                                       class="flex-1 rounded-[8px] border border-border px-[11px] py-2 text-[13px] text-ink outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]">
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-2 text-[11px] text-faint"><i class="ph ph-info align-[-1px] text-[12px]"></i> Setiap bahagian boleh diisi dengan nama peserta yang berbeza.</p>
                </div>
            @endif
        @endif
        <x-slot:footer>
            @if ($canManage && $activePlan && $activePlan->quantity > 1)
                <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
                <x-ui.button icon="check" wire:click="saveNames">Simpan</x-ui.button>
            @else
                <x-ui.button x-on:click="open = false">Tutup</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Link Bayaran Ansuran ===================== --}}
    <x-ui.modal wire:model="showLink" title="Link Bayaran Ansuran" :subtitle="$activePlan ? $activePlan->order_no.' · '.$activePlan->customer->name : null" icon="link" max-width="460px">
        @if ($activePlan && $showLink)
            @php $link = $activePlan->portalUrl(); @endphp
            <div x-data="{ copied: false, copy() { navigator.clipboard?.writeText(@js($link)); this.copied = true; setTimeout(() => this.copied = false, 1500) } }">
                <p class="mb-[14px] text-[13px] leading-[1.6] text-muted">Kongsi <b class="text-ink-2">satu link kekal</b> ini kepada pelanggan. Link ini tidak berubah — pelanggan guna link yang sama untuk setiap ansuran bulanan. Status akan <b class="text-ink-2">dikemas kini automatik</b> oleh gerbang pembayaran apabila bayaran berjaya atau ditolak.</p>
                <div class="mb-3 flex items-center gap-2 rounded-[9px] border border-[#CDEBD6] bg-success-soft px-3 py-[9px]"><i class="ph-fill ph-shield-check text-[16px] text-success"></i><span class="text-[12px] font-semibold text-[#166534]">Auto-reconcile: Berjaya → tandakan Dibayar · Ditolak → kekal Belum Bayar</span></div>
                <a href="{{ $link }}" target="_blank" rel="noopener" title="Klik untuk buka Portal Bayaran Ansuran" class="flex items-center gap-[10px] rounded-[10px] border border-border bg-bg px-[14px] py-3">
                    <i class="ph ph-lock-simple shrink-0 text-[16px] text-success"></i>
                    <span class="flex-1 font-mono text-[12px] break-all text-ink">{{ $link }}</span>
                    <i class="ph ph-arrow-square-out text-[16px] text-primary"></i>
                </a>
                <div class="mt-[14px] flex gap-[10px]">
                    <x-ui.button icon="copy" class="flex-1" x-on:click="copy()"><span x-text="copied ? 'Disalin!' : 'Salin Link'">Salin Link</span></x-ui.button>
                    <x-ui.button variant="success" icon="whatsapp-logo" class="flex-1" target="_blank" :href="'https://wa.me/'.preg_replace('/^0/', '60', (string) preg_replace('/\D+/', '', $activePlan->customer->phone)).'?text='.rawurlencode('Assalamualaikum '.$activePlan->customer->name.', sila lengkapkan bayaran ansuran '.$activePlan->service->label().' anda di pautan ini: '.$link)">WhatsApp</x-ui.button>
                </div>
            </div>
        @endif
    </x-ui.modal>

    {{-- ===================== Resit A4 ===================== --}}
    <x-ui.modal wire:model="showReceipt" title="Resit Bayaran Ansuran · A4" :subtitle="$activePlan?->receiptNo()" icon="receipt" max-width="820px" body-class="bg-[#EEF1EC] p-3 md:p-6">
        @if ($activePlan && $showReceipt)
            <x-ui.doc-a4 padding="22mm 20mm">
                @include('pdf.partials.installment-receipt-a4', ['plan' => $activePlan])
            </x-ui.doc-a4>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            @if ($activePlan)
                <x-ui.button variant="gold" icon="download-simple" :href="route('installments.receipt', ['plan' => $activePlan, 'muat-turun' => 1])">Muat Turun PDF</x-ui.button>
                <x-ui.button icon="printer" target="_blank" :href="route('installments.receipt', $activePlan)">Cetak</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Batal (sebab) ===================== --}}
    <x-ui.modal wire:model="showCancel" title="Batalkan Tempahan Ansuran" :subtitle="$selectedCount.' pelan dipilih'" icon="x-circle" tone="danger" max-width="440px" :footer-border="false">
        <x-ui.field label="Sebab Pembatalan" as="textarea" rows="3" wire:model="cancelReason" placeholder="cth. Pelanggan menarik diri" />
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Kembali</x-ui.button>
            <x-ui.button variant="danger" icon="x-circle" wire:click="cancelSelected">Batalkan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Tempahan Baharu (Ansuran) ===================== --}}
    @include('livewire.installments.partials.plan-form')
</div>
