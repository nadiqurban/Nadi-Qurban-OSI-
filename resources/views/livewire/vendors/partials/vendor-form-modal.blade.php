@php
    /** @var \App\Livewire\Forms\VendorForm $vendorForm */
    $isEdit = $vendorModal === 'edit';
    $countryOptions = \App\Models\Country::query()->active()->orderBy('sort')->pluck('name', 'id');
    $section = 'col-span-full text-[11px] font-bold tracking-[.5px] text-primary uppercase dark:text-[#c9ce93]';
@endphp

{{-- Daftar Vendor Baharu (660px) / Edit Maklumat Vendor (560px) — olive header as in the design. --}}
<x-ui.modal wire:model="showVendorForm" variant="dark" :max-width="$isEdit ? '560px' : '660px'" :footer-border="false"
            :title="$isEdit ? 'Edit Maklumat Vendor' : 'Daftar Vendor Baharu'" :icon="$isEdit ? 'pencil-simple' : 'buildings'">
    <form id="vendor-form" wire:submit="saveVendor" class="grid grid-cols-1 gap-4 md:grid-cols-2" novalidate>
        @if ($isEdit)
            <div class="{{ $section }}">Identiti</div>
            <x-ui.field label="Vendor ID" wire:model="vendorForm.vendorNo" />
            <x-ui.field label="Status" as="select" wire:model="vendorForm.status">
                @foreach (\App\Enums\VendorStatus::cases() as $st)
                    <option value="{{ $st->value }}">{{ $st->label() }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="Kod Vendor" wire:model="vendorForm.code" />
            <x-ui.field label="Nama Vendor" wire:model="vendorForm.name" />
            <x-ui.field label="Nama Syarikat" wire:model="vendorForm.company" />
            <x-ui.field label="Nama Supplier" wire:model="vendorForm.supplier" />
            <x-ui.field label="Vendor Level" as="select" wire:model="vendorForm.level" span>
                @foreach (\App\Enums\VendorLevel::cases() as $lv)
                    <option value="{{ $lv->value }}">{{ $lv->label() }}</option>
                @endforeach
            </x-ui.field>

            <div class="{{ $section }} mt-1.5">Hubungan</div>
            <x-ui.field label="No. Telefon" wire:model="vendorForm.phone" />
            <x-ui.field label="Emel" type="email" wire:model="vendorForm.email" />
            <x-ui.field label="PIC Vendor" wire:model="vendorForm.picName" />
            <x-ui.field label="Negara" as="select" wire:model="vendorForm.countryId">
                @foreach ($countryOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.field>

            <div class="{{ $section }} mt-1.5">Maklumat Bank</div>
            <x-ui.field label="Nama Bank" wire:model="vendorForm.bankName" />
            <x-ui.field label="Nama Pemegang Akaun" wire:model="vendorForm.bankHolder" />
            <x-ui.field label="No. Akaun" wire:model="vendorForm.bankAccount" />
            <x-ui.field label="Kod Swift" wire:model="vendorForm.swift" />
            <x-ui.field label="Alamat Bank" as="textarea" rows="2" wire:model="vendorForm.bankAddress" span />
        @else
            <div class="col-span-full -mt-1 flex items-center gap-2 rounded-[9px] bg-primary-soft px-3 py-2">
                <span class="text-[12px] text-muted">Kod vendor:</span>
                <input type="text" wire:model="vendorForm.code" aria-label="Kod vendor" class="w-[96px] rounded-[6px] border border-border bg-surface px-2 py-1 text-[12px] font-bold text-primary outline-none focus:border-primary max-md:text-[16px]">
                @error('vendorForm.code')<span class="text-[11px] text-danger">{{ $message }}</span>@enderror
            </div>
            <x-ui.field label="Nama Syarikat" wire:model="vendorForm.name" placeholder="cth. Al-Barakah Livestock Ltd" span />
            <x-ui.field label="Nama Supplier" wire:model="vendorForm.supplier" placeholder="Nama supplier" />
            <x-ui.field label="Negara" as="select" wire:model="vendorForm.countryId">
                <option value="">Pilih negara</option>
                @foreach ($countryOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </x-ui.field>
            <x-ui.field label="No. Telefon" wire:model="vendorForm.phone" placeholder="+256 …" />
            <x-ui.field label="Emel" type="email" wire:model="vendorForm.email" placeholder="contact@vendor.com" />
            <div class="col-span-full">
                <span class="mb-2 block text-[12px] font-semibold text-ink-2">Jenis Haiwan <span class="font-normal text-faint">(boleh pilih lebih dari 1)</span></span>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\Vendor::ANIMALS as $a)
                        @php $on = in_array($a, $vendorForm->animals, true); @endphp
                        <button type="button" wire:click="toggleVendorAnimal('{{ $a }}')" aria-pressed="{{ $on ? 'true' : 'false' }}"
                                @class(['inline-flex min-h-10 items-center gap-1.5 rounded-[20px] px-[14px] py-2 text-[12.5px] font-semibold', 'bg-primary text-white' => $on, 'border border-border bg-bg text-ink-3' => ! $on])>
                            @if ($on)<i class="ph-fill ph-check text-[13px]"></i>@endif{{ $a }}
                        </button>
                    @endforeach
                </div>
            </div>
            <x-ui.field label="Tahap Vendor" as="select" wire:model="vendorForm.level" span>
                @foreach (\App\Enums\VendorLevel::cases() as $lv)
                    <option value="{{ $lv->value }}">{{ $lv->label() }}</option>
                @endforeach
            </x-ui.field>
            <div class="col-span-full mt-1.5 grid grid-cols-1 gap-4 rounded-[11px] border border-[#D8E0CC] p-4 md:grid-cols-2">
                <x-ui.field label="Nama Bank" wire:model="vendorForm.bankName" placeholder="cth. Maybank Berhad" span />
                <x-ui.field label="Nama Pemegang Akaun" wire:model="vendorForm.bankHolder" placeholder="Nama pemegang akaun" />
                <x-ui.field label="No. Akaun" wire:model="vendorForm.bankAccount" placeholder="No. akaun" />
                <x-ui.field label="Kod Swift" wire:model="vendorForm.swift" placeholder="cth. MBBEMYKL" />
                <x-ui.field label="Alamat Bank" as="textarea" rows="2" wire:model="vendorForm.bankAddress" placeholder="Alamat cawangan bank" span />
            </div>
        @endif
    </form>
    <x-slot:footer>
        <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
        <x-ui.button type="submit" form="vendor-form" icon="check">{{ $isEdit ? 'Simpan' : 'Simpan Vendor' }}</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
