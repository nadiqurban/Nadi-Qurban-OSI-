@php
    $isGrid = $view !== 'list';
    $counts = $this->counts;
    $storage = $this->storage;
    $cats = array_merge(
        [['key' => '', 'label' => 'Semua Fail', 'icon' => 'folder']],
        array_map(fn ($c) => ['key' => $c->value, 'label' => $c->label(), 'icon' => $c->icon()], \App\Enums\DocumentCategory::cases()),
    );
    $seg = 'flex h-[38px] w-10 items-center justify-center max-md:h-11 max-md:w-11';
    $sortLabels = ['terkini' => 'Terkini', 'lama' => 'Terlama', 'nama' => 'Nama (A–Z)', 'saiz' => 'Saiz terbesar'];
@endphp

<div>
    <x-ui.page-header title="Repositori Dokumen" :breadcrumb="['Operasi', 'Dokumen']">
        <x-slot:actions>
            <div class="flex items-center overflow-hidden rounded-[9px] border border-border bg-surface" role="tablist" aria-label="Paparan">
                <button type="button" id="view-grid" wire:click="$set('view', 'grid')" aria-label="Paparan grid" aria-selected="{{ $isGrid ? 'true' : 'false' }}" @class([$seg, 'bg-primary text-white' => $isGrid, 'text-muted' => ! $isGrid])><i class="ph ph-squares-four text-[16px]"></i></button>
                <button type="button" id="view-list" wire:click="$set('view', 'list')" aria-label="Paparan senarai" aria-selected="{{ $isGrid ? 'false' : 'true' }}" @class([$seg, 'bg-primary text-white' => ! $isGrid, 'text-muted' => $isGrid])><i class="ph ph-list-bullets text-[16px]"></i></button>
            </div>
            @if ($canManage)
                <x-ui.button icon="upload-simple" id="btn-upload" wire:click="openUpload" class="max-md:flex-1">Muat Naik</x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <div class="mb-[22px] grid grid-cols-2 gap-3 md:gap-4 lg:grid-cols-4">
        @foreach ($this->stats() as $s)
            <x-ui.stat-card :icon="$s['icon']" :tone="$s['tone']" :value="$s['value']" :label="$s['label']" />
        @endforeach
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
        {{-- Category rail (phones: horizontal chips + meter) --}}
        <aside class="min-w-0 rounded-[12px] border border-border bg-surface p-[14px] max-lg:p-3">
            <div class="px-[10px] pt-1.5 pb-[10px] text-[11px] font-bold tracking-[.6px] text-faint uppercase max-lg:hidden">Kategori</div>
            <div class="nq-scroll-x flex gap-1.5 lg:flex-col lg:gap-0">
                @foreach ($cats as $c)
                    @php $on = $category === $c['key']; @endphp
                    <button type="button" wire:click="$set('category', '{{ $c['key'] }}')" @class([
                        'my-px flex shrink-0 items-center gap-[11px] rounded-[9px] px-3 py-[10px] text-left text-[13px] max-lg:min-h-11 max-lg:border',
                        'bg-primary-soft font-bold text-primary max-lg:border-primary dark:text-[#c9ce93]' => $on,
                        'font-medium text-ink-3 hover:bg-bg max-lg:border-border' => ! $on,
                    ])>
                        <i class="ph ph-{{ $c['icon'] }} text-[18px]"></i>
                        <span class="whitespace-nowrap lg:flex-1 lg:whitespace-normal lg:leading-[1.3]">{{ $c['label'] }}</span>
                        <span @class(['rounded-[20px] px-2 py-px text-[11px] font-bold', 'bg-primary text-white' => $on, 'bg-divider text-muted' => ! $on])>{{ number_format($counts[$c['key']] ?? 0) }}</span>
                    </button>
                @endforeach
            </div>
            <div class="mt-[14px] border-t border-divider px-3 pt-[14px] pb-1.5 max-lg:mt-3 max-lg:px-1 max-lg:pt-3">
                <div class="mb-2 flex items-center justify-between text-[12px] text-muted"><span>Storan digunakan</span><span class="font-bold text-ink-2">{{ $storage['pct'] }}%</span></div>
                <div class="h-2 overflow-hidden rounded-[20px] bg-primary-soft"><div class="h-full rounded-[20px] bg-primary" style="width: {{ max(1, $storage['pct']) }}%"></div></div>
                <div class="mt-[7px] text-[11.5px] text-faint">{{ \App\Livewire\Documents\Index::bytes($storage['used']) }} / {{ \App\Livewire\Documents\Index::bytes($storage['quota']) }}</div>
            </div>
        </aside>

        <div class="min-w-0">
            {{-- Search / filter / sort --}}
            <div class="mb-[18px] flex flex-wrap items-center gap-3 rounded-[12px] border border-border bg-surface px-4 py-[14px] md:px-[18px]">
                <label class="flex min-w-[200px] flex-1 items-center gap-[10px] rounded-[9px] border border-border bg-bg px-[13px] py-[9px] max-md:min-h-11 max-md:w-full">
                    <i class="ph ph-magnifying-glass text-[16px] text-faint"></i>
                    <input type="search" wire:model.live.debounce.400ms="search" placeholder="Cari fail dalam kategori" aria-label="Cari fail dalam kategori" class="min-w-0 flex-1 bg-transparent text-[13px] text-ink outline-none max-md:text-[16px]">
                </label>
                <label class="flex items-center gap-[9px] rounded-[9px] border border-border bg-surface px-[13px] py-[9px] text-[13px] text-ink-2 max-md:min-h-11 max-md:flex-1">
                    <i class="ph ph-funnel text-[16px] text-muted"></i>
                    <select wire:model.live="service" aria-label="Servis" class="cursor-pointer bg-transparent text-[13px] text-ink-2 outline-none max-md:flex-1 max-md:text-[16px]">
                        <option value="">Semua Servis</option>
                        @foreach (\App\Models\Document::SERVICES as $svc)<option value="{{ $svc }}">{{ $svc }}</option>@endforeach
                    </select>
                </label>
                <label class="flex items-center gap-[9px] rounded-[9px] border border-border bg-surface px-[13px] py-[9px] text-[13px] text-ink-2 max-md:min-h-11 max-md:flex-1">
                    <i class="ph ph-sort-ascending text-[16px] text-muted"></i>
                    <select wire:model.live="sort" aria-label="Susunan" class="cursor-pointer bg-transparent text-[13px] text-ink-2 outline-none max-md:flex-1 max-md:text-[16px]">
                        @foreach ($sortLabels as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
            </div>

            @if ($this->documents->isEmpty())
                <div class="rounded-[12px] border border-border bg-surface"><x-ui.empty-state icon="folder-open" title="Tiada fail dalam kategori ini." /></div>
            @elseif ($isGrid)
                <div class="grid grid-cols-2 gap-3 md:grid-cols-[repeat(auto-fill,minmax(190px,1fr))] md:gap-4">
                    @foreach ($this->documents as $doc)
                        @php [$icon, $tone] = $doc->typeStyle(); @endphp
                        <div wire:key="doc-{{ $doc->id }}" class="relative flex flex-col overflow-hidden rounded-[12px] border border-border bg-surface">
                            <a href="{{ route('documents.open', $doc) }}" target="_blank" rel="noopener" class="flex h-[100px] items-center justify-center md:h-[120px] {{ \App\Support\Tone::bg($tone) }}" aria-label="Buka {{ $doc->name }}">
                                <i class="ph-fill ph-{{ $icon }} text-[44px] {{ \App\Support\Tone::fg($tone) }}"></i>
                            </a>
                            <div x-data="{ menu: false }" @click.outside="menu = false" class="absolute top-[10px] right-[10px]">
                                <button type="button" @click="menu = !menu" class="flex size-[26px] items-center justify-center rounded-[7px] bg-white/85 text-muted max-md:size-9" aria-label="Menu {{ $doc->name }}"><i class="ph ph-dots-three text-[16px]"></i></button>
                                <div x-cloak x-show="menu" x-transition.origin.top.right class="absolute top-8 right-0 z-20 flex w-40 flex-col rounded-[10px] border border-border bg-surface p-1.5 shadow-pop">
                                    <a href="{{ route('documents.open', $doc) }}" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-[7px] px-2.5 py-2 text-[12.5px] text-ink-2 hover:bg-bg"><i class="ph ph-arrow-square-out text-[15px]"></i> Buka</a>
                                    <a href="{{ route('documents.open', ['document' => $doc, 'muat-turun' => 1]) }}" class="flex items-center gap-2 rounded-[7px] px-2.5 py-2 text-[12.5px] text-ink-2 hover:bg-bg"><i class="ph ph-download-simple text-[15px]"></i> Muat Turun</a>
                                    @if ($canManage && $doc->source === 'upload')
                                        <button type="button" wire:click="delete({{ $doc->id }})" wire:confirm="Padam {{ $doc->name }}?" class="flex items-center gap-2 rounded-[7px] px-2.5 py-2 text-left text-[12.5px] text-danger hover:bg-danger-soft"><i class="ph ph-trash text-[15px]"></i> Padam</button>
                                    @endif
                                </div>
                            </div>
                            <div class="px-[14px] py-3">
                                <div class="truncate text-[13px] font-semibold text-ink" title="{{ $doc->name }}">{{ $doc->name }}</div>
                                <div class="mt-1.5 flex items-center justify-between gap-2"><span class="text-[11.5px] text-faint">{{ $doc->sizeLabel() }}</span><x-ui.badge :tone="$tone === 'gold' ? 'gold-ink' : $tone" variant="tag">{{ $doc->type() }}</x-ui.badge></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.data-table cols="2fr 1fr 1fr 1.2fr 0.6fr">
                    <x-slot:head>
                        <span>Nama Fail</span><span>Jenis</span><span>Saiz</span><span>Dikemaskini</span><span class="text-right">Buka</span>
                    </x-slot:head>
                    @foreach ($this->documents as $doc)
                        @php [$icon, $tone] = $doc->typeStyle(); @endphp
                        <x-ui.tr wire:key="row-{{ $doc->id }}" class="md:!py-[13px]">
                            <x-ui.td span class="flex items-center gap-[11px]">
                                <span class="flex size-[34px] shrink-0 items-center justify-center rounded-[8px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph-fill ph-{{ $icon }} text-[18px]"></i></span>
                                <span class="truncate text-[13px] font-semibold text-ink" title="{{ $doc->name }}">{{ $doc->name }}</span>
                            </x-ui.td>
                            <x-ui.td label="Jenis"><x-ui.badge :tone="$tone === 'gold' ? 'gold-ink' : $tone" variant="tag">{{ $doc->type() }}</x-ui.badge></x-ui.td>
                            <x-ui.td label="Saiz" class="text-[12.5px] text-muted">{{ $doc->sizeLabel() }}</x-ui.td>
                            <x-ui.td label="Dikemaskini" class="text-[12.5px] text-muted">{{ tarikh($doc->updated_at) }}</x-ui.td>
                            <x-ui.td align="right" span>
                                <a href="{{ route('documents.open', $doc) }}" target="_blank" rel="noopener" aria-label="Buka {{ $doc->name }}" class="flex size-[30px] items-center justify-center rounded-[8px] border border-border text-primary max-md:h-11 max-md:w-full max-md:gap-2 max-md:text-[13px] max-md:font-semibold dark:text-[#c9ce93]"><i class="ph ph-arrow-square-out text-[16px]"></i><span class="md:hidden">Buka Fail</span></a>
                            </x-ui.td>
                        </x-ui.tr>
                    @endforeach
                </x-ui.data-table>
            @endif

            @if ($this->documents->hasPages())
                <div class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row"><x-ui.pagination :paginator="$this->documents" noun="fail" /></div>
            @endif
        </div>
    </div>

    @if ($canManage)
        <x-ui.modal wire:model="showUpload" title="Muat Naik Fail" subtitle="Pilih satu atau lebih fail (maks 100MB setiap fail)" icon="upload-simple" max-width="560px" :footer-border="false">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field label="Kategori" as="select" wire:model="uploadCategory">
                    @foreach (\App\Enums\DocumentCategory::cases() as $c)<option value="{{ $c->value }}">{{ $c->label() }}</option>@endforeach
                </x-ui.field>
                <x-ui.field label="Servis" as="select" wire:model="uploadService">
                    @foreach (\App\Models\Document::SERVICES as $svc)<option value="{{ $svc }}">{{ $svc === 'Lain' ? 'Auto / Lain' : $svc }}</option>@endforeach
                </x-ui.field>
                <label class="col-span-full flex cursor-pointer flex-col items-center justify-center rounded-[12px] border-2 border-dashed border-checkbox-border bg-bg px-4 py-7 text-center hover:border-primary">
                    <i class="ph ph-cloud-arrow-up text-[34px] text-primary dark:text-[#c9ce93]"></i>
                    <span class="mt-2 text-[13px] font-semibold text-ink-2">Klik untuk pilih fail</span>
                    <span class="mt-1 text-[11.5px] text-faint">PDF, DOCX, XLSX, CSV, JPG, PNG, MP4 &middot; maks 20 fail</span>
                    <input type="file" id="upload-files" wire:model="files" multiple accept=".{{ str_replace(',', ',.', \App\Livewire\Documents\Index::MIMES) }}" class="hidden">
                </label>
                <div wire:loading wire:target="files" class="col-span-full text-[12px] text-muted">Memuat naik…</div>
                @if ($files)
                    <div class="col-span-full flex flex-col gap-2">
                        @foreach ($files as $i => $file)
                            <div wire:key="up-{{ $i }}" class="flex items-center gap-3 rounded-[9px] border border-border px-3 py-2">
                                <i class="ph ph-file text-[18px] text-muted"></i>
                                <span class="min-w-0 flex-1 truncate text-[12.5px] text-ink">{{ $file->getClientOriginalName() }}</span>
                                <span class="text-[11.5px] text-faint">{{ \App\Livewire\Documents\Index::bytes((int) $file->getSize()) }}</span>
                                <button type="button" wire:click="removeFile({{ $i }})" class="flex size-7 items-center justify-center text-danger max-md:size-11" aria-label="Buang"><i class="ph ph-x text-[14px]"></i></button>
                            </div>
                            @error('files.'.$i)<p class="text-[11.5px] text-danger">{{ $message }}</p>@enderror
                        @endforeach
                    </div>
                @endif
                @error('files')<p class="col-span-full text-[11.5px] text-danger">{{ $message }}</p>@enderror
            </div>
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="open = false">Batal</x-ui.button>
                <x-ui.button icon="upload-simple" id="btn-upload-save" wire:click="upload" wire:loading.attr="disabled" wire:target="upload,files">Muat Naik</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
