@php
    $cats = \App\Enums\NotificationType::categories();
@endphp

<div>
    <x-ui.page-header title="Notifikasi" :breadcrumb="['Sistem', 'Notifikasi']">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="checks" id="btn-read-all" wire:click="markAllRead" class="!px-[14px] [&>i]:text-primary max-md:flex-1 dark:[&>i]:text-[#c9ce93]">Tanda semua dibaca</x-ui.button>
            <x-ui.button :variant="$settings ? 'primary' : 'secondary'" icon="gear-six" id="btn-settings" wire:click="$toggle('settings')" class="!px-[14px] max-md:flex-1">Tetapan</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
        <div class="min-w-0 max-lg:order-2">
            <div class="mb-4">
                <x-ui.tabs wire-model="tab" :active="$tab" :items="collect(\App\Livewire\Notifications\Index::TABS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'count' => ($tabCounts[$key] ?? null) ?: null])->values()->all()" />
            </div>

            <div class="overflow-hidden rounded-[12px] border border-border bg-surface">
                @forelse ($this->notifications as $n)
                    @php
                        $cat = $n->data['category'] ?? 'Sistem';
                        [$icon, $tone] = $cats[$cat] ?? $cats['Sistem'];
                        $unreadRow = $n->read_at === null;
                    @endphp
                    <div wire:key="n-{{ $n->id }}" @class(['flex items-start gap-3 border-b border-divider px-4 py-4 md:gap-[14px] md:px-5', 'bg-[#fbfcf9] dark:bg-bg' => $unreadRow])>
                        <button type="button" wire:click="open('{{ $n->id }}')" class="flex min-w-0 flex-1 items-start gap-3 text-left md:gap-[14px]">
                            <span class="flex size-[42px] shrink-0 items-center justify-center rounded-[11px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[21px]"></i></span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center gap-2"><span class="text-[13.5px] font-bold text-ink">{{ $n->data['title'] ?? '' }}</span>@if ($unreadRow)<span class="size-[7px] shrink-0 rounded-full bg-primary" aria-label="Belum dibaca"></span>@endif</span>
                                <span class="mt-[3px] block text-[12.5px] leading-[1.5] text-muted">{{ $n->data['body'] ?? '' }}</span>
                                <span class="mt-[7px] flex items-center gap-[10px]"><x-ui.badge :tone="$tone === 'gold' ? 'gold-ink' : $tone" variant="tag">{{ $cat }}</x-ui.badge><span class="text-[11.5px] text-faint">{{ masa_lalu($n->created_at) }}</span></span>
                            </span>
                        </button>
                        <div x-data="{ menu: false }" @click.outside="menu = false" class="relative">
                            <button type="button" @click="menu = !menu" class="flex size-8 items-center justify-center text-[#CBD5D0] max-md:size-11" aria-label="Menu notifikasi"><i class="ph ph-dots-three-vertical text-[18px]"></i></button>
                            <div x-cloak x-show="menu" x-transition.origin.top.right class="absolute top-9 right-0 z-20 flex w-48 flex-col rounded-[10px] border border-border bg-surface p-1.5 shadow-pop">
                                @if ($unreadRow)
                                    <button type="button" wire:click="markRead('{{ $n->id }}')" @click="menu = false" class="flex items-center gap-2 rounded-[7px] px-2.5 py-2 text-left text-[12.5px] text-ink-2 hover:bg-bg"><i class="ph ph-check text-[15px]"></i> Tanda dibaca</button>
                                @endif
                                <button type="button" wire:click="remove('{{ $n->id }}')" class="flex items-center gap-2 rounded-[7px] px-2.5 py-2 text-left text-[12.5px] text-danger hover:bg-danger-soft"><i class="ph ph-trash text-[15px]"></i> Padam</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="bell-slash" title="Tiada notifikasi." />
                @endforelse
            </div>
            @if ($this->notifications->hasPages())
                <div class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row"><x-ui.pagination :paginator="$this->notifications" noun="notifikasi" /></div>
            @endif
        </div>

        @if ($settings)
            <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px] max-lg:order-1">
                <h2 class="mb-1 text-[15px] font-bold text-ink">Keutamaan Notifikasi</h2>
                <p class="mb-[18px] text-[12.5px] text-muted">Pilih saluran untuk setiap jenis</p>
                <div class="grid grid-cols-[1.6fr_repeat(3,auto)] items-center gap-x-[14px] gap-y-[10px]">
                    <span></span>
                    @foreach (['App', 'Emel', 'WA'] as $h)<span class="text-center text-[11px] font-bold text-faint">{{ $h }}</span>@endforeach
                    @foreach (\App\Enums\NotificationType::cases() as $type)
                        <span class="text-[13px] text-ink-2">{{ $type->label() }}</span>
                        @foreach (['app' => 'App', 'mail' => 'Emel', 'wa' => 'WA'] as $ch => $chLabel)
                            @php $on = $prefs[$type->value][$ch] ?? false; @endphp
                            <span class="flex justify-center">
                                <button type="button" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $type->label() }} — {{ $chLabel }}"
                                        id="pref-{{ $type->value }}-{{ $ch }}" wire:click="togglePref('{{ $type->value }}', '{{ $ch }}')"
                                        class="flex items-center justify-center max-md:size-11">
                                    <span @class(['relative h-[22px] w-[38px] rounded-[20px] transition-colors', 'bg-primary' => $on, 'bg-[#CBD5D0]' => ! $on])>
                                        <span @class(['absolute top-0.5 size-[18px] rounded-full bg-white transition-all', 'left-[18px]' => $on, 'left-0.5' => ! $on])></span>
                                    </span>
                                </button>
                            </span>
                        @endforeach
                    @endforeach
                </div>
            </section>
        @else
            <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px] max-lg:order-1">
                <h2 class="mb-4 text-[15px] font-bold text-ink">Ringkasan</h2>
                <div class="flex flex-col gap-3">
                    @foreach ($this->summary() as $cat => $count)
                        @php [$icon, $tone] = $cats[$cat]; @endphp
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 items-center justify-center rounded-[9px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[18px]"></i></span>
                            <span class="flex-1 text-[13px] text-ink-2">{{ $cat }}</span>
                            <span class="text-[15px] font-bold text-ink">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5 flex items-start gap-[11px] rounded-[10px] bg-primary-soft p-4">
                    <i class="ph ph-info text-[19px] text-primary dark:text-[#c9ce93]"></i>
                    <span class="text-[12.5px] leading-[1.5] text-primary dark:text-[#c9ce93]">Anda ada <b>{{ $unread }} notifikasi belum dibaca</b>. Notifikasi kritikal dihantar juga melalui emel &amp; WhatsApp.</span>
                </div>
            </section>
        @endif
    </div>
</div>
