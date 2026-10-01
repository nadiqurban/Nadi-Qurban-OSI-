@php
    $done = $lead->done_at !== null;
@endphp

<div>
    <a href="{{ route('crm.index') }}" wire:navigate class="mb-[18px] inline-flex items-center gap-[7px] text-[13.5px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">
        <i class="ph ph-arrow-left text-[17px]"></i> Kembali ke saluran
    </a>

    <div class="mb-5 flex flex-wrap items-center gap-4 rounded-[14px] border border-border bg-surface p-4 md:gap-5 md:p-6">
        <x-ui.avatar :name="$lead->name" :size="64" shape="rounded" class="!rounded-[16px] !text-[22px]" />
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-[20px] font-bold text-ink md:text-[22px]">{{ $lead->name }}</h1>
                <x-ui.badge :tone="$lead->stage->tone()">{{ $lead->stage->label() }}</x-ui.badge>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-[13px] text-muted">
                <span class="flex items-center gap-[5px]"><i class="ph ph-buildings text-[15px]"></i>{{ $lead->company ?: 'Individu' }}</span>
                @if ($lead->phone)<a href="tel:{{ $lead->phone }}" class="flex items-center gap-[5px] text-muted"><i class="ph ph-phone text-[15px]"></i>{{ $lead->phone }}</a>@endif
                @if ($lead->email)<a href="mailto:{{ $lead->email }}" class="flex items-center gap-[5px] break-all text-muted"><i class="ph ph-envelope-simple text-[15px]"></i>{{ $lead->email }}</a>@endif
            </div>
        </div>
        <div class="text-right max-md:text-left">
            <div class="text-[24px] font-extrabold text-primary dark:text-[#c9ce93]">{{ rm($lead->value_sen) }}</div>
            <div class="mt-0.5 text-[12px] text-faint">Anggaran nilai</div>
        </div>
        <div class="flex flex-wrap gap-2 max-md:w-full">
            @if ($lead->order)
                <x-ui.button variant="secondary" icon="shopping-cart-simple" :href="route('orders.show', $lead->order)" wire:navigate class="max-md:flex-1">{{ $lead->order->order_no }}</x-ui.button>
            @elseif ($canConvert)
                <x-ui.button variant="gold" icon="shopping-cart-simple" id="btn-convert" wire:click="convert" class="max-md:flex-1">Tukar ke Tempahan</x-ui.button>
            @endif
            @if ($canManage)
                <x-ui.button :variant="$done ? 'primary' : 'success'" :icon="$done ? 'fill check-circle' : 'check-circle'" id="btn-done" wire:click="toggleDone" class="[&>i]:!text-[16px] max-md:flex-1">{{ $done ? 'Lead Selesai' : 'Tandakan Selesai' }}</x-ui.button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
        <div class="flex flex-col gap-5">
            <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
                <h2 class="mb-[18px] text-[15px] font-bold text-ink">Maklumat Lead</h2>
                <div class="flex flex-col gap-[15px]">
                    @foreach ($info as $k => $v)
                        <div>
                            <div class="text-[11.5px] tracking-[.4px] text-faint uppercase">{{ $k }}</div>
                            <div class="mt-1 text-[14px] font-semibold text-ink">{{ $v }}</div>
                        </div>
                    @endforeach
                    <div>
                        <label for="lead-notes" class="mb-1.5 flex items-center justify-between text-[11.5px] tracking-[.4px] text-faint uppercase">
                            Catatan
                            <span wire:loading wire:target="leadNotes" class="text-[11px] tracking-normal normal-case">Menyimpan…</span>
                        </label>
                        <textarea id="lead-notes" rows="3" wire:model.live.debounce.800ms="leadNotes" @disabled(! $canManage) placeholder="Tulis catatan mengenai lead ini..."
                                  class="w-full resize-y rounded-[9px] border border-border bg-surface px-3 py-[10px] text-[13px] text-ink outline-none placeholder:text-faint focus:border-primary max-md:text-[16px]"></textarea>
                        @error('leadNotes')<p class="mt-1 text-[11.5px] text-danger">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>
            <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
                <h2 class="mb-4 text-[15px] font-bold text-ink">Kemajuan Peringkat</h2>
                <div class="flex flex-col gap-3">
                    @foreach ($stages as $st)
                        <div class="flex items-center gap-[11px]">
                            <span @class([
                                'flex size-[26px] shrink-0 items-center justify-center rounded-full',
                                'bg-primary text-white' => $st['state'] === 'done',
                                'bg-gold text-white' => $st['state'] === 'current',
                                'bg-divider text-faint' => $st['state'] === 'todo',
                            ])><i class="{{ $st['state'] === 'done' ? 'ph-fill ph-check' : ($st['state'] === 'current' ? 'ph ph-dot-outline' : 'ph ph-circle') }} text-[13px]"></i></span>
                            <span @class([
                                'text-[13.5px] font-semibold',
                                'text-ink' => $st['state'] === 'done',
                                'text-gold-ink' => $st['state'] === 'current',
                                'text-faint' => $st['state'] === 'todo',
                            ])>{{ $st['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="rounded-[12px] border border-border bg-surface px-4 py-5 md:px-6 md:py-[22px]">
            <div class="mb-[18px] flex items-center justify-between">
                <h2 class="text-[15px] font-bold text-ink">Aktiviti &amp; Nota</h2>
                @if ($canManage)
                    <button type="button" id="btn-add-note" wire:click="toggleNote" class="text-[12.5px] font-semibold text-primary max-md:min-h-11 dark:text-[#c9ce93]">+ Tambah Nota</button>
                @endif
            </div>
            @if ($noteOpen)
                <div class="mb-4 flex flex-col gap-[10px]">
                    <textarea id="note-text" rows="3" wire:model="noteText" placeholder="Tulis nota atau aktiviti…" class="w-full resize-y rounded-[9px] border border-border bg-surface px-3 py-[10px] text-[13px] text-ink outline-none placeholder:text-faint focus:border-primary max-md:text-[16px]"></textarea>
                    <div class="flex justify-end gap-2">
                        <x-ui.button variant="secondary" size="sm" wire:click="toggleNote">Batal</x-ui.button>
                        <x-ui.button size="sm" icon="check" id="btn-save-note" wire:click="saveNote">Simpan</x-ui.button>
                    </div>
                </div>
            @endif
            <div class="flex flex-col">
                @forelse ($lead->activities as $a)
                    @php [$icon, $tone] = \App\Models\LeadActivity::STYLES[$a->type] ?? ['note-pencil', 'primary']; @endphp
                    <div wire:key="act-{{ $a->id }}" class="flex gap-[13px]">
                        <div class="flex flex-col items-center">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-[9px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[16px]"></i></span>
                            <span @class(['my-[3px] min-h-3 w-0.5 flex-1', 'bg-border' => ! $loop->last, 'bg-transparent' => $loop->last])></span>
                        </div>
                        <div class="flex-1 pb-[18px]">
                            <div class="text-[13.5px] font-semibold text-ink">{{ $a->title }}</div>
                            @if ($a->description)<div class="mt-[3px] text-[12.5px] leading-[1.5] whitespace-pre-line text-muted">{{ $a->description }}</div>@endif
                            <div class="mt-1 text-[11.5px] text-faint">{{ $a->who() }} &middot; {{ masa_lalu($a->created_at) }}</div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state icon="note-pencil" title="Belum ada aktiviti." />
                @endforelse
            </div>
        </section>
    </div>
</div>
