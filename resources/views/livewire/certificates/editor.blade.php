@php
    $field = fn (string $key, string $label) => ['key' => $key, 'label' => $label];
@endphp

<div>
    <x-ui.page-header title="Editor Sijil" subtitle="Sunting reka bentuk sijil · pratonton A5 langsung" :breadcrumb="['Sistem', 'Sijil']">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button variant="secondary" icon="arrow-counter-clockwise" wire:click="resetTemplate" wire:confirm="Set semula templat sijil kepada asal?" class="max-md:flex-1">Set Semula</x-ui.button>
                <x-ui.button icon="check" wire:click="save" class="max-md:flex-1">Simpan Templat</x-ui.button>
            @endif
            <x-ui.button variant="gold" icon="download-simple" wire:click="download" class="max-md:w-full">Muat Turun PDF (A5)</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 items-start gap-[22px] lg:grid-cols-[360px_1fr]">
        {{-- Form --}}
        <section class="flex flex-col gap-[15px] rounded-[12px] border border-border bg-surface px-[22px] py-5">
            <x-ui.field label="Tajuk Sijil" wire:model.live.debounce.250ms="form.title" error="form.title" />
            <x-ui.field label="Ayat Pembuka" wire:model.live.debounce.250ms="form.intro" error="form.intro" />
            <x-ui.field label="Nama Penerima" wire:model.live.debounce.250ms="form.name" />
            <x-ui.field label="Ayat Penghubung" wire:model.live.debounce.250ms="form.close" error="form.close" />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-ui.field label="Ibadah (emas)" wire:model.live.debounce.250ms="form.service" />
                <x-ui.field label="Butiran (cth. Lembu Bahagian 1)" wire:model.live.debounce.250ms="form.detail" />
                <x-ui.field label="Lokasi" wire:model.live.debounce.250ms="form.country" />
                <x-ui.field label="Tarikh" wire:model.live.debounce.250ms="form.date" />
            </div>
            <x-ui.field label="Ayat Penutup" wire:model.live.debounce.250ms="form.jazak" error="form.jazak" />
            <x-ui.field label="URL Daftar (QR)" wire:model.live.debounce.400ms="form.qr_url" error="form.qr_url" />
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <x-ui.field label="No. Sijil" wire:model.live.debounce.250ms="form.certificate_no" />
                <x-ui.field label="No. Tracking" wire:model.live.debounce.250ms="form.tracking_no" />
            </div>
            <div>
                <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Template Sijil Tersendiri (latar penuh)</span>
                <label @class(['flex min-h-11 items-center gap-[9px] rounded-[9px] border-[1.5px] border-dashed border-gold bg-[#FCFBF5] px-[14px] py-3 text-primary', 'cursor-pointer' => $canManage, 'opacity-60' => ! $canManage])>
                    <i class="ph ph-upload-simple text-[18px]"></i>
                    <span class="flex-1 text-[12.5px] font-semibold">{{ $hasBackground ? 'Template dimuat naik ✓' : 'Klik untuk muat naik imej template' }}</span>
                    <span wire:loading wire:target="background"><i class="ph ph-spinner-gap animate-spin"></i></span>
                    @if ($canManage)<input type="file" wire:model="background" accept="image/png,image/jpeg,image/webp" class="hidden">@endif
                </label>
                @error('background')<p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                @if ($hasBackground && $canManage)
                    <button type="button" wire:click="removeBackground" class="mt-2 inline-flex min-h-9 items-center gap-[5px] text-[12px] text-danger"><i class="ph ph-trash text-[14px]"></i> Buang template</button>
                @endif
            </div>
        </section>

        {{-- Live preview --}}
        <section class="flex min-w-0 flex-col items-center gap-3">
            <div class="self-start text-[12px] text-faint">Pratonton (A5 potret · 148 × 210 mm)</div>
            <div x-data="docScale(472)" class="w-full max-w-[472px]" :style="`height: ${669 * scale}px`">
                <div :style="{ transform: `scale(${scale})`, transformOrigin: 'top left' }" class="w-[472px] shadow-[0_12px_34px_rgba(0,0,0,.16)]" id="sijil-preview">
                    @include('pdf.partials.certificate', ['c' => $certificate])
                </div>
            </div>
        </section>
    </div>
</div>
