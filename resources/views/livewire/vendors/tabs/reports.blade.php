@php
    $status = $report?->status;
    $verified = $status === \App\Enums\VendorReportStatus::Verified;
    $zone = 'flex cursor-pointer flex-col items-center rounded-[11px] border-[1.5px] border-dashed border-[#CBD5C0] bg-[#fbfcf9] px-3 py-[22px] text-center';
@endphp

<div x-data="{ prev: null }" @keydown.escape.window="prev = null">
    @if ($orders->isEmpty())
        <div class="rounded-[12px] border border-border bg-surface"><x-ui.empty-state icon="file-text" title="Tiada PO yang diterima untuk laporan. Laporan boleh dimuat naik selepas vendor menerima PO." /></div>
    @else
        <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
            <div class="flex flex-col gap-5">
                {{-- Upload --}}
                <section class="rounded-[12px] border border-border bg-surface px-5 py-[22px] md:px-6">
                    <div class="mb-1.5 flex flex-wrap items-center justify-between gap-[10px]">
                        <h2 class="text-[15.5px] font-bold text-ink">Report Submission</h2>
                        <label class="flex items-center gap-[7px]"><span class="text-[12px] text-faint">PO</span>
                            <select wire:model.live="poId" aria-label="Pilih PO" class="cursor-pointer rounded-[8px] border border-border bg-surface px-[10px] py-[7px] text-[12.5px] font-semibold text-primary outline-none max-md:min-h-11 max-md:text-[16px]">
                                @foreach ($orders as $o)
                                    <option value="{{ $o->id }}" @selected($o->id === $po?->id)>{{ $o->po_no }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <p class="mb-[18px] text-[12.5px] text-muted">Vendor muat naik bukti pelaksanaan untuk pengesahan HQ.</p>

                    @if ($canUpload && ! $verified)
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="text-[12px] font-semibold text-ink-2">Haiwan:</span>
                            @foreach ($animals as $a)
                                <button type="button" wire:click="setAnimal('{{ $a }}')" @class(['min-h-9 rounded-[20px] px-[13px] py-1.5 text-[12px] font-semibold max-md:min-h-10', 'bg-primary text-white' => $animal === $a, 'border border-border bg-bg text-ink-3' => $animal !== $a])>{{ $a }}</button>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-1 gap-[14px] sm:grid-cols-2">
                            <label class="{{ $zone }}"><i class="ph ph-images text-[28px] text-primary"></i><span class="mt-2 text-[12.5px] font-semibold text-ink-2">Images</span><span class="mt-[3px] text-[11px] text-faint">JPG / PNG / PDF · pelbagai</span><input type="file" wire:model="files" multiple accept="image/png,image/jpeg,application/pdf" class="hidden"></label>
                            <label class="{{ $zone }}"><i class="ph ph-video text-[28px] text-purple"></i><span class="mt-2 text-[12.5px] font-semibold text-ink-2">Videos</span><span class="mt-[3px] text-[11px] text-faint">WEBP · maks 200MB</span><input type="file" wire:model="files" multiple accept="image/webp,video/webm,video/mp4,video/quicktime" class="hidden"></label>
                        </div>
                        <div wire:loading wire:target="files" class="mt-2 text-[11.5px] text-muted"><i class="ph ph-spinner-gap animate-spin"></i> Memuat naik…</div>
                        @if ($files !== [])
                            <div class="mt-3 flex flex-col gap-1.5">
                                @foreach ($files as $i => $f)
                                    <div class="flex items-center gap-2 rounded-[8px] bg-bg px-3 py-2 text-[12.5px]" wire:key="up-{{ $i }}"><i class="ph ph-paperclip text-[15px] text-primary"></i><span class="min-w-0 flex-1 truncate">{{ $f->getClientOriginalName() }}</span><span class="text-[11px] text-faint">{{ $animal }}</span><button type="button" wire:click="removeUpload({{ $i }})" class="text-danger" aria-label="Buang"><i class="ph ph-x text-[14px]"></i></button></div>
                                @endforeach
                            </div>
                        @endif
                        @error('files')<p class="mt-2 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                        @error('files.*')<p class="mt-2 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                        <div class="mt-4"><x-ui.field label="Notes" as="textarea" rows="3" wire:model="notes" placeholder="Catatan sembelihan & agihan…" /></div>
                    @else
                        <div class="mt-4"><span class="mb-1.5 block text-[12px] font-semibold text-ink-2">Notes</span><div class="min-h-[70px] rounded-[9px] border border-border bg-bg px-[14px] py-3 text-[13px] leading-[1.6] text-ink-3">{{ $report?->notes ?: '—' }}</div></div>
                    @endif

                    @if ($status === \App\Enums\VendorReportStatus::Revision)
                        <div class="mt-3 flex items-start gap-2 rounded-[9px] border border-[#F3C9C9] bg-danger-soft px-3 py-2.5 text-[12.5px] text-danger"><i class="ph ph-warning-circle mt-0.5 text-[16px]"></i><span><b>Semakan semula diminta:</b> {{ $report->revision_note }}</span></div>
                    @endif

                    <div class="mt-4 flex justify-end">
                        @if ($verified)
                            <span class="inline-flex items-center gap-[7px] rounded-[9px] bg-success-soft px-[15px] py-[10px] text-[13px] font-semibold text-success"><i class="ph-fill ph-check-circle text-[16px]"></i> Rekod sembelihan disimpan</span>
                        @elseif ($canUpload)
                            <x-ui.button icon="floppy-disk" wire:click="submit" wire:loading.attr="disabled" wire:target="submit,files" class="max-md:w-full">Simpan Rekod Sembelihan</x-ui.button>
                        @endif
                    </div>
                </section>

                {{-- Files --}}
                <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
                    <div class="flex items-center justify-between px-[22px] pt-[18px] pb-3"><h2 class="text-[15px] font-bold text-ink">Fail Dimuat Naik</h2><span class="text-[12px] text-faint">{{ $groups->flatten()->count() }} fail</span></div>
                    @forelse ($groups as $animalName => $media)
                        <div class="border-t border-divider bg-bg px-[22px] pt-[10px] pb-1.5"><span class="text-[11px] font-bold tracking-[.4px] text-primary uppercase dark:text-[#c9ce93]">{{ $animalName }}</span><span class="ml-2 text-[11px] text-faint">{{ $media->count() }} fail</span></div>
                        @foreach ($media as $m)
                            @php
                                $url = \App\Models\VendorPayment::mediaUrl($m);
                                $kind = str_starts_with((string) $m->mime_type, 'video/') || $m->mime_type === 'image/webp' ? 'video' : ($m->mime_type === 'application/pdf' ? 'pdf' : 'image');
                            @endphp
                            <div class="flex items-center gap-3 border-t border-divider px-[22px] py-3">
                                <span @class(['flex size-9 shrink-0 items-center justify-center rounded-[9px]', 'bg-purple-soft text-purple' => $kind === 'video', 'bg-danger-soft text-danger' => $kind === 'pdf', 'bg-primary-soft text-primary' => $kind === 'image'])><i class="ph-fill ph-{{ $kind === 'video' ? 'file-video' : ($kind === 'pdf' ? 'file-pdf' : 'file-image') }} text-[18px]"></i></span>
                                <span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold text-ink">{{ $m->file_name }}</span><span class="text-[11.5px] text-faint">{{ $m->human_readable_size }} · {{ tarikh($m->created_at) }}</span></span>
                                <span class="hidden rounded-[20px] bg-primary-soft px-[9px] py-[3px] text-[10.5px] font-bold whitespace-nowrap text-primary sm:inline">{{ $po?->po_no }}</span>
                                @if ($kind === 'pdf')
                                    <a href="{{ $url }}" target="_blank" rel="noopener" class="flex size-[30px] items-center justify-center rounded-[8px] border border-border text-primary max-md:size-11" aria-label="Lihat {{ $m->file_name }}"><i class="ph ph-eye text-[16px]"></i></a>
                                @else
                                    <button type="button" @click="prev = { src: @js($url), name: @js($m->file_name), meta: @js($m->human_readable_size), kind: @js($kind) }" class="flex size-[30px] items-center justify-center rounded-[8px] border border-border text-primary max-md:size-11" aria-label="Lihat {{ $m->file_name }}"><i class="ph ph-eye text-[16px]"></i></button>
                                @endif
                                <a href="{{ $url }}" download="{{ $m->file_name }}" class="flex size-[30px] items-center justify-center text-primary max-md:size-11" aria-label="Muat turun {{ $m->file_name }}"><i class="ph ph-download-simple text-[17px]"></i></a>
                            </div>
                        @endforeach
                    @empty
                        <div class="border-t border-divider px-[22px] py-7 text-center text-[12.5px] text-faint">Tiada fail untuk PO ini.</div>
                    @endforelse
                </section>
            </div>

            {{-- HQ Verify --}}
            <section @class(['rounded-[12px] border bg-surface px-5 py-[22px] md:px-6', 'border-[#BBE5C9]' => $verified, 'border-border' => ! $verified])>
                <div class="mb-1.5 flex items-center gap-[10px]"><i @class(['text-[24px]', 'ph-fill ph-seal-check text-success' => $verified, 'ph ph-hourglass-medium text-warning' => ! $verified])></i><h2 class="text-[15.5px] font-bold text-ink">HQ Verify</h2></div>
                <x-ui.badge :tone="$status?->tone() ?? 'neutral'" class="!text-[11px] !px-[11px]">{{ $status?->label() ?? 'Belum Dihantar' }}</x-ui.badge>
                <div class="my-4 h-px bg-divider"></div>
                <div class="flex flex-col gap-3">
                    @foreach (['images' => 'Gambar pelaksanaan lengkap', 'videos' => 'Video sembelihan disertakan', 'pdf' => 'PDF laporan penuh dimuat naik'] as $key => $label)
                        <div class="flex items-center gap-[10px]"><i @class(['text-[18px]', 'ph-fill ph-check-circle text-success' => $checklist[$key], 'ph ph-circle text-faint' => ! $checklist[$key]])></i><span class="text-[13px] text-ink-2">{{ $label }}</span></div>
                    @endforeach
                </div>
                @if ($verified)
                    <div class="mt-[18px] flex items-start gap-[10px] rounded-[10px] bg-success-soft p-[14px]"><i class="ph-fill ph-check-circle text-[20px] text-success"></i><span class="text-[12.5px] leading-normal text-[#15803D]">Laporan disahkan oleh {{ $report->verifier?->name ?? 'HQ' }} pada {{ tarikh($report->verified_at, true) }}. Status PO bertukar kepada <b>Completed</b>.</span></div>
                @elseif ($status === \App\Enums\VendorReportStatus::Submitted && $canVerify)
                    <div class="mt-[18px] flex flex-col gap-[9px]">
                        <x-ui.button icon="check-circle" wire:click="verify" wire:confirm="Sahkan laporan {{ $po?->po_no }} dan tandakan PO Completed?" class="w-full !py-3 !font-bold">Sahkan &amp; Tandakan Completed</x-ui.button>
                        <x-ui.button variant="secondary" wire:click="$set('showRevision', true)" class="w-full !border-[#F3C9C9] !py-3 !font-bold !text-danger">Minta Semakan Semula</x-ui.button>
                    </div>
                @endif
            </section>
        </div>
    @endif

    {{-- File preview --}}
    <template x-teleport="body">
        <div x-cloak x-show="prev" x-transition.opacity @click="prev = null" class="fixed inset-0 z-[150] flex items-center justify-center bg-[rgba(20,24,20,.62)] p-4 md:p-8">
            <div class="w-full max-w-[560px] overflow-hidden rounded-[14px] bg-surface shadow-[0_24px_60px_rgba(0,0,0,.4)]" @click.stop>
                <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-[14px]">
                    <div class="min-w-0"><div class="truncate text-[13px] font-bold text-ink" x-text="prev?.name"></div><div class="text-[11px] text-faint"><span x-text="prev?.meta"></span> &middot; {{ $po?->po_no }}</div></div>
                    <button type="button" @click="prev = null" class="flex size-[30px] shrink-0 items-center justify-center rounded-[8px] border border-border text-muted max-md:size-11" aria-label="Tutup"><i class="ph ph-x text-[15px]"></i></button>
                </div>
                <div class="flex min-h-[300px] items-center justify-center bg-[#0f120f]">
                    <template x-if="prev?.kind === 'video'"><video :src="prev.src" controls class="block max-h-[70vh] max-w-full"></video></template>
                    <template x-if="prev?.kind === 'image'"><img :src="prev.src" alt="pratonton" class="block max-h-[70vh] max-w-full"></template>
                </div>
                <div class="flex justify-end gap-[10px] px-5 py-[14px]">
                    <x-ui.button variant="secondary" size="sm" @click="prev = null">Tutup</x-ui.button>
                    <a :href="prev?.src" :download="prev?.name" class="inline-flex min-h-10 items-center gap-[7px] rounded-[9px] bg-primary px-4 py-[10px] text-[13px] font-semibold text-white hover:bg-primary-hover hover:text-white"><i class="ph ph-download-simple text-[16px]"></i> Muat Turun</a>
                </div>
            </div>
        </div>
    </template>

    <x-ui.modal wire:model="showRevision" title="Minta Semakan Semula" :subtitle="$po?->po_no" icon="arrow-counter-clockwise" tone="danger" max-width="440px" :footer-border="false">
        <x-ui.field label="Catatan kepada vendor" as="textarea" rows="3" wire:model="revisionNote" placeholder="cth. Video sembelihan lembu ke-3 tiada." />
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="open = false">Kembali</x-ui.button>
            <x-ui.button variant="danger" icon="paper-plane-tilt" wire:click="requestRevision">Hantar Permintaan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
