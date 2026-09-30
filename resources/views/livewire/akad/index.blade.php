@php $rows = $this->rows; $pendingTab = $tab === 'menunggu'; @endphp

<div>
    <x-ui.page-header title="Lafaz Akad Wakalah" subtitle="Rekod akad wakalah pelanggan sebelum pelaksanaan ibadah Qurban." :breadcrumb="['Operasi', 'Lafaz Akad']">
        <x-slot:note>
            <div class="inline-flex items-center gap-[7px] rounded-[8px] bg-success-soft px-3 py-1.5 text-[12px] font-semibold text-success">
                <i class="ph-fill ph-seal-check shrink-0 text-[15px]"></i> Senarai ini diambil daripada tempahan yang bayarannya telah diterima &amp; disahkan.
            </div>
        </x-slot:note>
    </x-ui.page-header>

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats() as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-ui.tabs wire-model="tab" :active="$tab" :items="[
            ['key' => 'menunggu', 'label' => 'Menunggu Akad', 'count' => $this->counts['pending']],
            ['key' => 'selesai', 'label' => 'Akad Selesai', 'count' => $this->counts['done']],
        ]" />
        @if ($canManage && $pendingTab && count($selected) > 0)
            <x-ui.button size="sm" icon="hand-heart" wire:click="bulkAkad" wire:confirm="Rekod akad pukal untuk {{ count($selected) }} tempahan?" class="md:ml-auto max-md:w-full">Akad Pukal ({{ count($selected) }})</x-ui.button>
        @endif
    </div>

    <x-ui.data-table cols="32px 1.3fr 1.5fr 1.1fr 1fr 0.9fr 1fr 1.1fr">
        <x-slot:head>
            @if ($canManage && $pendingTab)<x-ui.select-all :checked="$this->allSelected()" />@else<span></span>@endif<span>No. Tempahan</span><span>Pelanggan</span><span>Ibadah</span><span>Pakej</span><span class="text-center">Bhg</span><span class="text-center">Status Akad</span><span class="text-right">Tindakan</span>
        </x-slot:head>
        @foreach ($rows as $o)
            @php $done = (bool) $o->akad; @endphp
            <x-ui.tr wire:key="akad-{{ $o->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    @if ($canManage && $pendingTab)
                        <x-ui.checkbox value="{{ $o->id }}" wire:model.live="selected" aria-label="Pilih {{ $o->order_no }}" />
                    @endif
                    <span class="text-[13px] font-bold text-primary md:hidden">{{ $o->order_no }}</span>
                    <span class="ml-auto md:hidden"><x-ui.badge :tone="$done ? 'success' : 'warning'" variant="tag" class="text-[10.5px]">{{ $done ? 'Selesai' : 'Menunggu' }}</x-ui.badge></span>
                </x-ui.td>
                <x-ui.td mobile="hide" class="font-semibold text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</x-ui.td>
                <x-ui.td span>
                    <span class="block truncate font-semibold text-ink">{{ $o->customer->name }}</span>
                    <span class="text-[11.5px] text-faint">{{ $o->customer->phone }}</span>
                </x-ui.td>
                <x-ui.td label="Ibadah" class="text-ink-3">{{ $o->ibadahLabel() }}</x-ui.td>
                <x-ui.td label="Pakej" class="text-ink-3">{{ $o->package_name }}</x-ui.td>
                <x-ui.td label="Bhg" align="center" class="text-ink-3">{{ $o->quantity }}/{{ $o->animal->capacity() }}</x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.badge :tone="$done ? 'success' : 'warning'" variant="tag" class="text-[10.5px]">{{ $done ? 'Selesai' : 'Menunggu' }}</x-ui.badge></x-ui.td>
                <x-ui.td align="right" span class="flex items-center gap-2 md:justify-end">
                    <x-ui.button size="sm" variant="secondary" icon="user" class="!text-primary max-md:flex-1" wire:click="openDetail({{ $o->id }})">Butiran</x-ui.button>
                    @if (! $done && $canManage)
                        <x-ui.button size="sm" icon="hand-heart" id="akad-open-{{ $o->id }}" wire:click="openAkad({{ $o->id }})" class="md:!shrink md:!whitespace-normal md:text-left max-md:flex-1">Lafaz Akad</x-ui.button>
                    @elseif ($done)
                        <button type="button" wire:click="openAkad({{ $o->id }})" class="inline-flex min-h-10 items-center gap-1.5 text-[12.5px] font-semibold text-primary max-md:flex-1 max-md:justify-center"><i class="ph ph-file-text text-[15px]"></i> Lihat Rekod</button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        @if ($rows->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="hand-heart" :title="$pendingTab ? 'Tiada tempahan menunggu akad.' : 'Belum ada akad yang direkod.'" />
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    {{-- ===================== Lafaz Akad Wakalah ===================== --}}
    @php $record = $akadOrder?->akad; @endphp
    <x-ui.modal wire:model="showAkad" title="Lafaz Akad Wakalah" :subtitle="$akadOrder ? $akadOrder->order_no.' · '.$akadOrder->customer->name : null" icon="hand-heart" max-width="600px" :footer-border="false">
        @if ($akadOrder)
            <div class="rounded-[12px] border border-[#ece7cf] bg-[#FCFBF5] px-[22px] py-5">
                <div class="mb-3 text-center text-[11px] font-bold tracking-[.5px] text-gold uppercase">Mohon Peserta Mengikuti Bacaan Lafaz Akad Seperti Yang Dipaparkan</div>
                <p class="font-arabic text-center text-[21px] leading-[2] text-primary" dir="rtl">﷽</p>
                <p class="mt-[14px] text-justify text-[13.5px] leading-[1.85] text-ink-2">Saya <b class="text-primary">{{ $akadOrder->customer->name }}</b> mewakilkan Syarikat <b class="text-primary">Nadi Qurban Sdn Bhd</b> bertanggungjawab atas pelaksanaan ibadah <b class="text-primary">{{ $akadOrder->ibadahLabel() }}</b> pada tahun ini di atas nama yang didaftarkan kerana Allah Ta'ala.</p>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Kaedah Akad</span>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Kaedah Akad">
                        @foreach (\App\Enums\AkadMethod::selectable() as $m)
                            @php $on = $record ? $record->method === $m : $method === $m->value; @endphp
                            <button type="button" @if (! $record) wire:click="$set('method', '{{ $m->value }}')" @endif @disabled($record) role="radio" aria-checked="{{ $on ? 'true' : 'false' }}"
                                    @class(['inline-flex min-h-10 items-center gap-1.5 rounded-[8px] border px-[11px] py-2 text-[12px] font-semibold', 'border-primary bg-primary-soft text-primary' => $on, 'border-border bg-surface text-muted' => ! $on])>
                                <i class="ph ph-{{ $m->icon() }} text-[15px]"></i> {{ $m->label() }}
                            </button>
                        @endforeach
                        @if ($record?->method === \App\Enums\AkadMethod::Bulk)
                            <span class="inline-flex min-h-10 items-center gap-1.5 rounded-[8px] border border-primary bg-primary-soft px-[11px] text-[12px] font-semibold text-primary"><i class="ph ph-stack text-[15px]"></i> Pukal</span>
                        @endif
                    </div>
                </div>
                <x-ui.field label="Saksi / PIC" :value="$record?->witness?->name ?? auth()->user()->name" readonly class="bg-bg" />
            </div>

            @if ($record)
                <div class="mt-[18px] flex items-center gap-2 rounded-[10px] bg-success-soft px-[14px] py-3 text-[12.5px] font-semibold text-success">
                    <i class="ph-fill ph-check-circle text-[17px]"></i> Akad direkodkan pada {{ tarikh($record->recorded_at, true) }}
                </div>
            @else
                <div class="mt-[18px]">
                    <x-ui.checkbox id="akad-consent" wire:model.live="consent" label="Pelanggan telah faham & bersetuju dengan lafaz akad di atas." />
                    @error('consent')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                </div>
            @endif
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">{{ $record ? 'Tutup' : 'Batal' }}</x-ui.button>
            @if (! $record && $canManage)
                <x-ui.button icon="check-circle" wire:click="confirmAkad" :disabled="! $consent"
                             class="{{ $consent ? '!bg-success' : '!bg-[#9CA3AF]' }}">Sahkan Akad</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    {{-- ===================== Detail Pelanggan ===================== --}}
    <x-ui.modal wire:model="showDetail" title="Detail Pelanggan" :subtitle="$detailOrder?->order_no" icon="user" max-width="620px" :footer-border="false">
        @if ($detailOrder)
            @php $ro = ! $editing; @endphp
            @if ($ro && auth()->user()->can('orders.manage'))
                <div class="-mt-2 mb-3 flex justify-end">
                    <button type="button" wire:click="startEdit" class="inline-flex min-h-9 items-center gap-1.5 rounded-[8px] bg-primary-soft px-[13px] py-2 text-[12.5px] font-semibold text-primary"><i class="ph ph-pencil-simple text-[15px]"></i> Edit</button>
                </div>
            @endif
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field label="Nama Penuh" wire:model="draft.name" :disabled="$ro" span />
                <x-ui.field label="No. Telefon" wire:model="draft.phone" :disabled="$ro" />
                <x-ui.field label="Emel" wire:model="draft.email" :disabled="$ro" />
                <x-ui.field label="Alamat (No. & Jalan)" wire:model="draft.address" :disabled="$ro" span />
                <x-ui.field label="Poskod" wire:model="draft.postcode" :disabled="$ro" />
                <x-ui.field label="Bandar" wire:model="draft.city" :disabled="$ro" />
                <x-ui.field label="Ibadah" :value="$detailOrder->ibadahLabel()" disabled />
                <x-ui.field label="Negara Pelaksanaan" :value="$detailOrder->country->name" disabled />
            </div>

            <div class="mt-4 border-t border-divider pt-4">
                <div class="mb-[10px] text-[12px] font-bold text-ink-2">Ringkasan Bayaran</div>
                <div class="overflow-hidden rounded-[10px] border border-border text-[13px] text-ink-3">
                    <div class="flex items-center justify-between bg-bg px-[14px] py-[11px]"><span>{{ $detailOrder->ibadahLabel() }} · {{ $detailOrder->quantity }} bahagian</span><span>{{ rm($detailOrder->unit_price_sen) }} / bhg</span></div>
                    <div class="flex items-center justify-between border-t border-divider px-[14px] py-[11px]"><span>Subjumlah ({{ $detailOrder->quantity }} × {{ rm($detailOrder->unit_price_sen) }})</span><span>{{ rm($detailOrder->subtotal_sen) }}</span></div>
                    @if ($detailOrder->discount_sen > 0)
                        <div class="flex items-center justify-between border-t border-divider px-[14px] py-[11px]"><span>Diskaun ({{ $detailOrder->promo_code }})</span><span>- {{ rm($detailOrder->discount_sen) }}</span></div>
                    @endif
                    <div class="flex items-center justify-between border-t border-border bg-[#FCFBF5] px-[14px] py-3"><span class="text-[13px] font-bold text-primary">Jumlah Bayaran</span><span class="text-[16px] font-extrabold text-primary">{{ rm($detailOrder->total_sen) }}</span></div>
                    <div class="flex items-center justify-between border-t border-divider px-[14px] py-[11px]">
                        <span class="text-[12.5px] text-muted">Kaedah: <b class="text-ink-2">{{ $detailOrder->payment?->channel ?: $detailOrder->payment_method->label() }}</b></span>
                        @if ($detailOrder->payment)<x-ui.badge :tone="$detailOrder->payment->status->tone()" variant="tag">{{ $detailOrder->payment->status->label() }}</x-ui.badge>@endif
                    </div>
                </div>
            </div>

            @if ($detailOrder->quantity > 1)
                <div class="mt-4 border-t border-divider pt-4">
                    <div class="mb-[10px] flex items-center justify-between"><span class="text-[12px] font-bold text-ink-2">Senarai Peserta ({{ $detailOrder->quantity }})</span><span class="text-[11px] text-faint">1 bahagian = 1 peserta</span></div>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach ($detailOrder->participantNames() as $i => $name)
                            <div class="flex items-center gap-[9px] rounded-[8px] bg-bg px-[11px] py-[9px]">
                                <span class="flex size-[22px] shrink-0 items-center justify-center rounded-full bg-primary-soft text-[11px] font-bold text-primary">{{ $i + 1 }}</span>
                                <span class="truncate text-[12.5px] text-ink">{{ $name ?: 'Peserta '.($i + 1) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
        @if ($editing)
            <x-slot:footer>
                <x-ui.button variant="secondary" wire:click="$set('editing', false)">Batal</x-ui.button>
                <x-ui.button icon="check" wire:click="saveDetail">Simpan Perubahan</x-ui.button>
            </x-slot:footer>
        @endif
    </x-ui.modal>
</div>
