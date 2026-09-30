@php $canManage = $this->canManage(); @endphp

<div>
    <x-ui.page-header title="Kod Promosi" subtitle="Cipta & urus kod diskaun untuk tempahan pelanggan." :breadcrumb="['Jualan & Kewangan', 'Kod Promosi']">
        @if ($canManage)
            <x-slot:actions>
                <x-ui.button icon="plus" mobile-block wire:click="create">Kod Baharu</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <x-ui.tabs class="mb-4" wire-model="tab" :active="$tab" :items="[
        ['key' => 'aktif', 'label' => 'Aktif', 'count' => $this->counts['active']],
        ['key' => 'tamat', 'label' => 'Tamat Tempoh', 'count' => $this->counts['ended']],
    ]" />

    <x-ui.data-table cols="1.1fr 1.6fr 0.9fr 0.9fr 1fr 0.9fr 0.9fr" min-width="860px">
        <x-slot:head>
            <span>Kod</span><span>Keterangan</span><span>Diskaun</span><span class="text-center">Guna</span><span>Sah Hingga</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
        </x-slot:head>

        @foreach ($this->codes as $c)
            @php $usable = $c->isUsable(); @endphp
            <x-ui.tr wire:key="promo-{{ $c->id }}">
                <x-ui.td span class="flex items-center justify-between gap-2 md:block">
                    <span class="inline-block rounded-[7px] bg-primary-soft px-[9px] py-[5px] font-mono font-bold text-primary dark:text-[#c9ce93]">{{ $c->code }}</span>
                    <span @class(['rounded-[20px] px-[11px] py-1 text-[11px] font-semibold md:hidden', 'bg-success-soft text-success' => $usable, 'bg-neutral-soft text-neutral' => ! $usable])>{{ $usable ? 'Aktif' : 'Tamat' }}</span>
                </x-ui.td>
                <x-ui.td span class="text-ink-3">{{ $c->description ?? '-' }}</x-ui.td>
                <x-ui.td label="Diskaun" class="font-semibold text-ink">{{ $c->discountLabel() }}</x-ui.td>
                <x-ui.td label="Guna" align="center" class="text-ink-3">{{ number_format($c->used_count) }}@if ($c->usage_limit)<span class="text-faint"> / {{ number_format($c->usage_limit) }}</span>@endif</x-ui.td>
                <x-ui.td label="Sah Hingga" class="text-ink-3">{{ $c->expires_at ? tarikh($c->expires_at) : 'Tiada had' }}</x-ui.td>
                <x-ui.td align="center" mobile="hide">
                    <span @class(['inline-block rounded-[20px] px-[11px] py-1 text-[11px] font-semibold', 'bg-success-soft text-success' => $usable, 'bg-neutral-soft text-neutral' => ! $usable])>{{ $usable ? 'Aktif' : 'Tamat' }}</span>
                </x-ui.td>
                <x-ui.td align="right" span class="flex gap-2 md:justify-end">
                    <button type="button" x-data="{ copied: false }"
                            x-on:click="navigator.clipboard?.writeText(@js($c->code)); copied = true; setTimeout(() => copied = false, 1500)"
                            title="Salin kod" aria-label="Salin kod {{ $c->code }}"
                            class="flex size-11 items-center justify-center rounded-[8px] border border-border text-primary md:size-[30px]">
                        <i class="ph text-[15px]" :class="copied ? 'ph-check text-success' : 'ph-copy'"></i>
                    </button>
                    @if ($canManage)
                        <button type="button" wire:click="edit({{ $c->id }})" title="Edit" aria-label="Edit {{ $c->code }}"
                                class="flex size-11 items-center justify-center rounded-[8px] border border-border text-primary md:size-[30px]"><i class="ph ph-pencil-simple text-[15px]"></i></button>
                        <button type="button" wire:click="delete({{ $c->id }})" wire:confirm="Buang kod {{ $c->code }}?" title="Buang" aria-label="Buang {{ $c->code }}"
                                class="flex size-11 items-center justify-center rounded-[8px] border border-[#F7CFCF] text-danger md:size-[30px]"><i class="ph ph-trash text-[15px]"></i></button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach

        @if ($this->codes->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="ticket" :title="$tab === 'tamat' ? 'Tiada kod tamat tempoh' : 'Tiada kod aktif'" />
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    {{-- Kod Promosi Baharu / Edit --}}
    <x-ui.modal wire:model="showForm" :title="$editingId ? 'Edit Kod Promosi' : 'Kod Promosi Baharu'"
                :subtitle="$editingId ? $code : 'Cipta kod diskaun baharu'" icon="ticket" max-width="500px" :footer-border="false">
        <form id="promo-form" wire:submit="save" class="grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
            <div class="col-span-full">
                <label for="f-code" class="mb-1.5 block text-[12px] font-semibold text-ink-2">Kod</label>
                <div class="flex gap-2">
                    <input id="f-code" wire:model.blur="code" placeholder="cth. QURBAN2027" autocomplete="off"
                           @class(['min-w-0 flex-1 rounded-[9px] border bg-surface px-3 py-[10px] font-mono text-[13px] text-ink uppercase outline-none focus:border-primary max-md:min-h-11', 'border-danger' => $errors->has('code'), 'border-border' => ! $errors->has('code')])>
                    <button type="button" wire:click="autoCode" class="rounded-[9px] bg-primary-soft px-[14px] text-[12.5px] font-semibold text-primary max-md:min-h-11">Auto</button>
                </div>
                @error('code')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
            </div>
            <x-ui.field label="Keterangan" wire:model="description" placeholder="cth. Diskaun awal musim" span />
            <x-ui.field label="Jenis Diskaun" as="select" wire:model.live="type">
                @foreach (\App\Enums\DiscountType::cases() as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field :label="$type === 'tetap' ? 'Nilai (RM)' : 'Nilai (%)'" wire:model="value" :placeholder="$type === 'tetap' ? 'cth. 50' : 'cth. 10'" inputmode="decimal" />
            <x-ui.field label="Had Guna" type="number" min="1" wire:model="usageLimit" placeholder="cth. 100" inputmode="numeric" />
            <x-ui.field label="Sah Hingga" type="date" wire:model="expiresAt" />
            <div class="col-span-full">
                <x-ui.checkbox wire:model="isActive" label="Kod aktif" />
            </div>
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
            <x-ui.button type="submit" form="promo-form" icon="check">Simpan Kod</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
