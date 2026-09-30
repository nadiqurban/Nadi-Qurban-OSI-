@php
    $rows = $this->rows;
    $hasFilters = $animal !== '' || $country !== '' || $vendor !== '';
    $status = fn ($o) => match (true) {
        $o->stage === \App\Enums\OrderStage::Executing => ['Menunggu Pelaksanaan', 'neutral'],
        $o->stage === \App\Enums\OrderStage::ReportUploaded => ['Menunggu Semakan HQ', 'warning'],
        default => ['Selesai', 'success'],
    };
@endphp

<div x-data="{ lb: null }" @keydown.escape.window="lb = null">
    <x-ui.page-header title="Pelaksanaan & Laporan" subtitle="Pantau pelaksanaan ibadah & sahkan laporan yang dimuat naik vendor." :breadcrumb="['Operasi', 'Pelaksanaan & Laporan']" />

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats() as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <div class="mb-[14px] flex flex-wrap items-center gap-2">
        <x-ui.tabs wire-model="tab" :active="$tab" :items="[
            ['key' => 'menunggu', 'label' => 'Menunggu Pelaksanaan', 'count' => $this->counts['pending']],
            ['key' => 'semakan', 'label' => 'Menunggu Semakan', 'count' => $this->counts['review']],
            ['key' => 'selesai', 'label' => 'Selesai', 'count' => $this->counts['done']],
        ]" />
        <x-ui.button variant="success" icon="microsoft-excel-logo" wire:click="export" class="md:ml-auto max-md:w-full">Eksport Excel</x-ui.button>
    </div>

    <x-ui.filter-bar plain class="!mb-4" :has-filters="$hasFilters" reset-action="$wire.clearFilters()" :active-count="collect([$animal, $country, $vendor])->filter()->count()">
        <x-slot:filters>
            <x-ui.mini-select wire:model.live="animal" placeholder="Semua Servis" :options="collect(\App\Enums\Animal::cases())->mapWithKeys(fn ($a) => [$a->value => $a->label()])->all()" aria-label="Servis" />
            <x-ui.mini-select wire:model.live="country" placeholder="Semua Negara" :options="$countries" aria-label="Negara" />
            <x-ui.mini-select wire:model.live="vendor" placeholder="Semua Vendor" :options="$vendorOptions" aria-label="Vendor" />
        </x-slot:filters>
    </x-ui.filter-bar>

    <x-ui.data-table cols="32px 1.3fr 1.4fr 1fr 1.2fr 1.2fr 1fr 1.1fr" min-width="1040px">
        <x-slot:head>
            <x-ui.select-all :checked="$this->allSelected()" />
            <span>No. Tempahan</span><span>Pelanggan</span><span>Pakej</span><span>Negara</span><span>Vendor</span><span class="text-center">Status</span><span class="text-right">Tindakan</span>
        </x-slot:head>
        @foreach ($rows as $o)
            @php [$label, $tone] = $status($o); @endphp
            <x-ui.tr wire:key="exec-{{ $o->id }}">
                <x-ui.td class="max-md:order-first max-md:col-span-2 max-md:flex max-md:items-center max-md:gap-3">
                    <x-ui.checkbox value="{{ $o->id }}" wire:model.live="selected" aria-label="Pilih {{ $o->order_no }}" />
                    <span class="text-[13px] font-bold text-primary md:hidden">{{ $o->order_no }}</span>
                    <span class="ml-auto md:hidden"><x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge></span>
                </x-ui.td>
                <x-ui.td mobile="hide" class="font-semibold text-primary dark:text-[#c9ce93]">{{ $o->order_no }}</x-ui.td>
                <x-ui.td span>
                    <span class="block truncate font-semibold text-ink">{{ $o->customer->name }}</span>
                    <span class="text-[11.5px] text-faint">{{ $o->ibadahLabel() }}</span>
                </x-ui.td>
                <x-ui.td label="Pakej" class="text-ink-3">{{ $o->package_name }}</x-ui.td>
                <x-ui.td label="Negara" class="text-ink-3">{{ $o->country->name }}</x-ui.td>
                <x-ui.td label="Vendor" class="text-ink-3">{{ $o->allocation?->vendor->name }} — {{ $o->allocation?->vendor->code }}</x-ui.td>
                <x-ui.td align="center" mobile="hide"><x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge></x-ui.td>
                <x-ui.td align="right" span>
                    @if ($o->stage === \App\Enums\OrderStage::Executing)
                        @if ($canManage)
                            <x-ui.button size="sm" icon="upload-simple" id="exec-open-{{ $o->id }}" wire:click="openReport({{ $o->id }})" class="max-md:w-full">Upload Laporan</x-ui.button>
                        @endif
                    @elseif ($o->stage === \App\Enums\OrderStage::ReportUploaded && $canVerify)
                        <x-ui.button size="sm" variant="gold" icon="magnifying-glass" id="exec-open-{{ $o->id }}" wire:click="openReport({{ $o->id }})" class="max-md:w-full">Semak</x-ui.button>
                    @else
                        <button type="button" id="exec-open-{{ $o->id }}" wire:click="openReport({{ $o->id }})" class="inline-flex min-h-10 items-center gap-1.5 text-[12.5px] font-semibold text-success max-md:w-full max-md:justify-center"><i class="ph-fill ph-check-circle text-[15px]"></i> Lihat Laporan</button>
                    @endif
                </x-ui.td>
            </x-ui.tr>
        @endforeach
        @if ($rows->isEmpty())
            <x-slot:empty>
                <x-ui.empty-state icon="shopping-bag" :title="match ($tab) { 'semakan' => 'Tiada laporan menunggu semakan.', 'selesai' => 'Belum ada laporan disahkan.', default => 'Tiada tempahan menunggu pelaksanaan.' }" />
            </x-slot:empty>
        @endif
    </x-ui.data-table>

    {{-- ===================== Upload / Semak Laporan ===================== --}}
    @php
        $uploadMode = $reportOrder?->stage === \App\Enums\OrderStage::Executing;
        $reviewMode = $reportOrder?->stage === \App\Enums\OrderStage::ReportUploaded;
        $vendorLabel = $reportOrder?->allocation ? $reportOrder->allocation->vendor->name.' — '.$reportOrder->allocation->vendor->code : '';
    @endphp
    <x-ui.modal wire:model="showReport" :title="$uploadMode ? 'Upload Laporan Pelaksanaan' : 'Semak Laporan Pelaksanaan'" :subtitle="$reportOrder ? $reportOrder->order_no.' · '.$vendorLabel : null" icon="shopping-bag" max-width="600px" :footer-border="false">
        @if ($reportOrder)
            <div class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-[10px] bg-bg px-[13px] py-[11px]"><div class="text-[11px] text-faint">Pelanggan</div><div class="mt-0.5 text-[13px] font-semibold text-ink">{{ $reportOrder->customer->name }}</div></div>
                    <div class="rounded-[10px] bg-bg px-[13px] py-[11px]"><div class="text-[11px] text-faint">Ibadah · Negara</div><div class="mt-0.5 text-[13px] font-semibold text-ink">{{ $reportOrder->ibadahLabel() }} · {{ $reportOrder->country->name }}</div></div>
                </div>

                @if ($uploadMode && $canManage)
                    <div>
                        <div class="mb-2 text-[12px] font-bold text-ink-2">Muat Naik Bukti Pelaksanaan</div>
                        <div class="grid grid-cols-2 gap-[10px]">
                            <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-[10px] border-[1.5px] border-dashed border-gold bg-[#FCFBF5] px-[10px] py-4 text-primary">
                                <i class="ph ph-image text-[22px]"></i><span class="text-[11.5px] font-semibold">Gambar (PNG)</span><span class="text-[10.5px] text-faint">{{ count($images) }} fail</span>
                                <input type="file" wire:model="images" accept="image/png,image/jpeg,image/webp" multiple class="hidden" id="exec-images">
                            </label>
                            <label class="flex cursor-pointer flex-col items-center gap-1.5 rounded-[10px] border-[1.5px] border-dashed border-purple bg-[#FBF9FE] px-[10px] py-4 text-purple">
                                <i class="ph ph-video text-[22px]"></i><span class="text-[11.5px] font-semibold">Video (WEBP)</span><span class="text-[10.5px] text-faint">{{ count($videos) }} fail</span>
                                <input type="file" wire:model="videos" accept="image/webp,video/webm,video/mp4,video/quicktime" multiple class="hidden" id="exec-videos">
                            </label>
                        </div>
                        <div wire:loading wire:target="images,videos" class="mt-2 text-[11.5px] text-muted"><i class="ph ph-spinner-gap animate-spin"></i> Memuat naik…</div>
                        @error('images')<p class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        @error('images.*')<p class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                        @error('videos.*')<p class="mt-1.5 text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                    </div>
                @endif

                @if ($gallery->isNotEmpty() || $images !== [] || $videos !== [])
                    <div>
                        <div class="mb-2 text-[12px] font-bold text-ink-2">Bukti Dimuat Naik</div>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-4">
                            @foreach ($gallery as $m)
                                @php $url = \App\Models\ExecutionReport::mediaUrl($m); $isVideo = str_starts_with((string) $m->mime_type, 'video/'); @endphp
                                @if ($isVideo)
                                    <button type="button" @click="lb = { src: @js($url), label: @js($m->file_name), kind: 'video' }" class="flex aspect-square flex-col items-center justify-center gap-1.5 rounded-[9px] border border-border bg-[#FBF9FE] text-purple">
                                        <i class="ph ph-video text-[26px]"></i><span class="line-clamp-2 px-1 text-center text-[10px] font-semibold">{{ $m->file_name }} · {{ $m->human_readable_size }}</span>
                                    </button>
                                @else
                                    <button type="button" @click="lb = { src: @js($url), label: @js($m->file_name), kind: 'image' }" class="relative block aspect-square overflow-hidden rounded-[9px] border border-border" aria-label="Buka {{ $m->file_name }}">
                                        <img src="{{ $url }}" alt="bukti" class="block size-full object-cover" loading="lazy">
                                        <span class="absolute right-[5px] bottom-[5px] flex size-[22px] items-center justify-center rounded-[6px] bg-[rgba(20,24,20,.6)] text-white"><i class="ph ph-magnifying-glass-plus text-[13px]"></i></span>
                                    </button>
                                @endif
                            @endforeach
                            @foreach (['images' => $images, 'videos' => $videos] as $collection => $files)
                                @foreach ($files as $i => $file)
                                    @php $previewable = in_array(strtolower($file->getClientOriginalExtension()), ['png', 'jpg', 'jpeg', 'webp'], true); @endphp
                                    <div class="relative aspect-square overflow-hidden rounded-[9px] border border-dashed border-gold bg-[#FCFBF5]" wire:key="up-{{ $collection }}-{{ $i }}">
                                        @if ($previewable)
                                            <img src="{{ $file->temporaryUrl() }}" alt="{{ $file->getClientOriginalName() }}" class="block size-full object-cover">
                                        @else
                                            <div class="flex size-full flex-col items-center justify-center gap-1.5 text-purple"><i class="ph ph-video text-[26px]"></i><span class="line-clamp-2 px-1 text-center text-[10px] font-semibold">{{ $file->getClientOriginalName() }}</span></div>
                                        @endif
                                        <button type="button" wire:click="removeUpload('{{ $collection }}', {{ $i }})" class="absolute top-1 right-1 flex size-6 items-center justify-center rounded-[6px] bg-[rgba(20,24,20,.6)] text-white" aria-label="Buang"><i class="ph ph-x text-[12px]"></i></button>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($uploadMode && $canManage)
                    <x-ui.field label="Nota Laporan" as="textarea" rows="3" wire:model="notes" placeholder="Catatan pelaksanaan, jumlah agihan, dsb." error="notes" />
                @elseif ($reportOrder->executionReport?->notes)
                    <div>
                        <span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Nota Laporan</span>
                        <p class="rounded-[9px] border border-border px-3 py-[10px] text-[13px] leading-relaxed text-ink">{{ $reportOrder->executionReport->notes }}</p>
                    </div>
                @endif

                @if (! $uploadMode)
                    @if ($reviewMode)
                        <div class="flex items-center gap-[10px] rounded-[10px] bg-bg px-[15px] py-[13px]"><i class="ph ph-info text-[17px] text-primary"></i><span class="text-[12.5px] text-ink-3">Semak semua bukti sebelum mengesahkan. Status akan bertukar kepada <b class="text-success">Selesai</b>.</span></div>
                    @elseif ($reportOrder->executionReport?->verified_at)
                        <div class="flex items-center gap-2 rounded-[10px] bg-success-soft px-[14px] py-3 text-[12.5px] font-semibold text-success"><i class="ph-fill ph-check-circle text-[17px]"></i> Laporan disahkan pada {{ tarikh($reportOrder->executionReport->verified_at, true) }}</div>
                    @endif
                    @error('images')<p class="text-[11.5px] font-medium text-danger">{{ $message }}</p>@enderror
                @endif
            </div>
        @endif
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
            @if ($uploadMode && $canManage)
                <x-ui.button icon="paper-plane-tilt" wire:click="submitReport" wire:loading.attr="disabled" wire:target="images,videos,submitReport">Hantar Laporan</x-ui.button>
            @elseif ($reviewMode && $canVerify)
                <x-ui.button variant="success" icon="check-circle" wire:click="verifyReport">Sahkan Selesai</x-ui.button>
            @endif
        </x-slot:footer>
    </x-ui.modal>

    {{-- Lightbox --}}
    <template x-teleport="body">
        <div x-cloak x-show="lb" x-transition.opacity @click="lb = null" class="fixed inset-0 z-[150] flex items-center justify-center bg-[rgba(12,14,12,.82)] p-4 md:p-8">
            <div class="w-full max-w-[860px] overflow-hidden rounded-[14px] bg-surface shadow-[0_24px_60px_rgba(0,0,0,.4)]" @click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-border px-[18px] py-[13px]">
                    <span class="truncate text-[13px] font-bold text-ink-2" x-text="lb?.label"></span>
                    <button type="button" @click="lb = null" class="flex size-[30px] shrink-0 items-center justify-center rounded-[8px] border border-border text-muted max-md:size-11" aria-label="Tutup"><i class="ph ph-x text-[15px]"></i></button>
                </div>
                <div class="flex max-h-[72vh] items-center justify-center overflow-auto bg-[#0f120f]">
                    <template x-if="lb?.kind === 'video'"><video :src="lb.src" controls autoplay class="block max-h-[72vh] max-w-full"></video></template>
                    <template x-if="lb?.kind === 'image'"><img :src="lb.src" alt="bukti" class="block max-h-[72vh] max-w-full"></template>
                </div>
            </div>
        </div>
    </template>
</div>
