{{-- Senarai Peserta Mengikut Kumpulan (Tempahan & Agihan Negara). Needs $groups, $selectionQuery, wire:model showGroups / groupsDate. --}}
<x-ui.modal wire:model="showGroups" title="Senarai Peserta Mengikut Kumpulan"
            subtitle="Lembu 7 nama/kumpulan · Unta 7 nama/kumpulan · Kambing 1 nama/kumpulan" max-width="720px"
            body-class="flex flex-col gap-4 px-4 py-5 md:max-h-[60vh] md:overflow-y-auto md:px-6 md:py-[22px]">
    @forelse ($groups as $g)
        <div class="overflow-hidden rounded-[11px] border border-border">
            <div class="flex items-center gap-[10px] border-b border-border bg-bg px-4 py-3">
                <span class="flex size-[30px] items-center justify-center rounded-[8px] bg-primary-soft text-primary"><i class="ph ph-users-three text-[17px]"></i></span>
                <div class="min-w-0 flex-1">
                    <div class="text-[13.5px] font-bold text-ink">{{ $g['title'] }}</div>
                    <div class="text-[11.5px] text-faint">{{ $g['country'] }} · {{ $g['meta'] }}</div>
                </div>
                <span @class(['rounded-[20px] px-[10px] py-[3px] text-[10.5px] font-bold', 'bg-success-soft text-success' => $g['full'], 'bg-warning-soft text-warning' => ! $g['full']])>{{ $g['full'] ? 'Lengkap' : 'Belum penuh' }}</span>
            </div>
            <div class="grid grid-cols-1 gap-x-[18px] gap-y-[7px] px-4 py-3 sm:grid-cols-2">
                @foreach ($g['names'] as $i => $name)
                    <div class="flex items-center gap-[9px] text-[12.5px] text-ink-2">
                        <span class="flex size-5 shrink-0 items-center justify-center rounded-[6px] bg-bg text-[10.5px] font-bold text-primary">{{ $i + 1 }}</span>{{ $name }}
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <x-ui.empty-state icon="users-three" title="Tiada tempahan dipilih" />
    @endforelse
    <x-slot:footer>
        <label class="mr-auto flex items-center gap-[7px] rounded-[8px] border border-border bg-surface px-[11px] py-[7px] max-md:!flex-none max-md:w-full">
            <i class="ph ph-calendar-blank text-[16px] text-primary"></i>
            <input type="date" wire:model.live="groupsDate" class="flex-1 bg-transparent text-[13px] text-ink outline-none" aria-label="Tarikh pelaksanaan">
        </label>
        <x-ui.button variant="secondary" x-on:click="open = false">Tutup</x-ui.button>
        <x-ui.button variant="secondary" icon="file-pdf" class="!border-primary !text-primary" target="_blank"
                     :href="route('orders.participants.pdf').'?'.$selectionQuery.'&tarikh='.$groupsDate">Preview PDF A4</x-ui.button>
        <x-ui.button icon="download-simple" :href="route('orders.participants.pdf').'?'.$selectionQuery.'&tarikh='.$groupsDate.'&muat-turun=1'">Muat Turun Senarai</x-ui.button>
    </x-slot:footer>
</x-ui.modal>

