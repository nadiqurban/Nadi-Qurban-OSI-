<section class="overflow-hidden rounded-[12px] border border-border bg-surface">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-divider px-5 py-[18px] md:px-[22px]">
        <div class="flex items-center gap-[11px]"><span class="flex size-9 items-center justify-center rounded-[10px] bg-primary-soft text-primary"><i class="ph ph-clock-counter-clockwise text-[19px]"></i></span><div><h2 class="text-[15px] font-bold text-ink">Audit Log Vendor</h2><p class="mt-0.5 text-[12px] text-faint">Jejak penuh aktiviti PO, bayaran &amp; laporan</p></div></div>
        <span class="rounded-[20px] bg-primary-soft px-3 py-[5px] text-[12px] font-semibold text-primary">{{ $total }} rekod</span>
    </div>
    <div class="px-5 pt-2 pb-5 md:px-[22px]">
        @forelse ($entries as $a)
            @php [$icon, $tile] = \App\Livewire\Vendors\Tabs\AuditLog::style((string) $a->event); @endphp
            <div class="flex gap-[14px] border-b border-bg py-[14px] last:border-b-0">
                <div class="flex shrink-0 flex-col items-center">
                    <span class="flex size-[34px] items-center justify-center rounded-[9px] {{ $tile }}"><i class="ph ph-{{ $icon }} text-[17px]"></i></span>
                    <span class="mt-1.5 w-0.5 flex-1 bg-divider"></span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-0.5"><span class="text-[13.5px] font-bold text-ink">{{ \Illuminate\Support\Str::of((string) $a->event)->after('.')->replace('_', ' ')->title() }}</span><span class="text-[11.5px] whitespace-nowrap text-faint">{{ tarikh($a->created_at, true) }}</span></div>
                    <p class="mt-[3px] text-[12.5px] leading-normal text-muted">{{ $a->description }}</p>
                    <div class="mt-[7px] flex flex-wrap items-center gap-2">
                        <span class="text-[11px] text-faint"><i class="ph ph-user align-[-1px] text-[12px]"></i> {{ $a->causer?->name ?? 'Sistem' }}</span>
                        @if ($ref = data_get($a->properties, 'ref'))<span class="rounded-[5px] bg-bg px-[7px] py-0.5 text-[11px] font-semibold text-primary">{{ $ref }}</span>@endif
                    </div>
                </div>
            </div>
        @empty
            <div class="py-8 text-center text-[13px] text-faint">Belum ada aktiviti direkodkan untuk vendor ini.</div>
        @endforelse
        @if ($total > $entries->count())
            <div class="pt-3 text-center"><x-ui.button variant="secondary" size="sm" wire:click="more">Muat lebih</x-ui.button></div>
        @endif
    </div>
</section>
