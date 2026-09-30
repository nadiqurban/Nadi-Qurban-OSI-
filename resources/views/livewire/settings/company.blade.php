<div>
    <x-ui.page-header title="Maklumat Syarikat" subtitle="Butiran rasmi syarikat, pendaftaran SSM, dan akaun bank untuk resit & sijil." :breadcrumb="['Tetapan', 'Maklumat Syarikat']" />

    <form wire:submit="save" class="flex max-w-[820px] flex-col gap-[22px]" novalidate>
        @if ($saved)
            <div class="flex items-center gap-2 rounded-[10px] bg-success-soft px-[14px] py-3 text-[13px] font-semibold text-success" role="status"><i class="ph-fill ph-check-circle text-[17px]"></i> Maklumat syarikat disimpan.</div>
        @endif

        <fieldset @disabled(! $canManage) class="flex flex-col gap-[22px]">
            <section class="rounded-[12px] border border-border bg-surface p-5 md:p-6">
                <div class="mb-[18px] flex items-center gap-[13px]">
                    <div class="size-[52px] shrink-0 overflow-hidden rounded-[12px]"><img src="{{ asset('images/logo-mark-128.png') }}" alt="logo" class="block size-full scale-[1.22] object-cover"></div>
                    <div>
                        <div class="text-[15px] font-bold text-primary dark:text-[#c9ce93]">Butiran Syarikat</div>
                        <div class="mt-0.5 text-[12px] text-faint">Dipaparkan pada resit, PO, waybill &amp; sijil</div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.field label="Nama Syarikat" wire:model="company.name" span />
                    <x-ui.field label="No. Pendaftaran (SSM)" wire:model="company.ssm" />
                    <x-ui.field label="No. SST" wire:model="company.sst" />
                    <x-ui.field label="No. Telefon" wire:model="company.phone" inputmode="tel" />
                    <x-ui.field label="Emel Rasmi" type="email" wire:model="company.email" />
                    <x-ui.field label="Alamat Berdaftar" as="textarea" rows="3" wire:model="company.address" span />
                    <x-ui.field label="Laman Web" wire:model="company.website" />
                </div>
            </section>

            <section class="rounded-[12px] border border-border bg-surface p-5 md:p-6">
                <div class="mb-4 text-[15px] font-bold text-ink">Akaun Bank</div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ui.field label="Nama Bank" wire:model="company.bank_name" />
                    <x-ui.field label="No. Akaun" wire:model="company.bank_account" inputmode="numeric" />
                    <x-ui.field label="Nama Pemegang Akaun" wire:model="company.bank_holder" span />
                </div>
                @if ($canManage)
                    <div class="mt-5 flex flex-wrap justify-end gap-[10px] max-md:[&>*]:flex-1">
                        <x-ui.button variant="secondary" :href="route('home')" wire:navigate>Batal</x-ui.button>
                        <x-ui.button type="submit" icon="check">Simpan Perubahan</x-ui.button>
                    </div>
                @endif
            </section>
        </fieldset>
    </form>
</div>
