<div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)]">
    <section class="rounded-[12px] border border-border bg-surface p-6 text-center">
        <div class="text-[12.5px] font-semibold text-muted">Vendor Ranking</div>
        <div class="mt-[10px] mb-1 flex items-baseline justify-center gap-1"><span class="text-[52px] leading-none font-extrabold text-primary dark:text-[#c9ce93]">{{ $rank }}</span><span class="text-[20px] font-bold text-faint">/10</span></div>
        <span class="inline-flex items-center gap-[5px] rounded-[20px] px-3 py-1 text-[11.5px] font-bold {{ $tier->badgeClasses() }}">{{ $tier->label() }}</span>

        <div class="mt-5 flex flex-wrap justify-center gap-1.5" role="radiogroup" aria-label="Ranking vendor">
            @for ($n = 1; $n <= 10; $n++)
                <button type="button" role="radio" aria-checked="{{ $n === $rank ? 'true' : 'false' }}"
                        @if ($canChange) wire:click="setRank({{ $n }})" @else disabled @endif
                        @class(['flex size-[34px] items-center justify-center rounded-[8px] text-[13.5px] font-bold max-md:size-11', 'bg-primary text-white' => $n === $rank, 'border border-border bg-bg text-muted' => $n !== $rank, 'cursor-pointer' => $canChange, 'cursor-not-allowed opacity-75' => ! $canChange])>{{ $n }}</button>
            @endfor
        </div>

        <div @class(['mt-5 flex items-start gap-[9px] rounded-[10px] border p-[14px] text-left', 'border-[#BBE5C9] bg-success-soft' => $canChange, 'border-border bg-bg' => ! $canChange])>
            <i @class(['mt-px text-[18px]', 'ph-fill ph-shield-check text-[#15803D]' => $canChange, 'ph ph-lock-key text-muted' => ! $canChange])></i>
            <span @class(['text-[12px] leading-normal', 'text-[#15803D]' => $canChange, 'text-muted' => ! $canChange])>
                {{ $canChange ? 'Anda log masuk sebagai Admin Sistem — anda boleh ubah ranking vendor (1–10). Tahap vendor dikemas kini mengikut ranking.' : 'Ranking hanya boleh diubah oleh Admin Sistem (Super Admin). Akses anda: baca sahaja.' }}
            </span>
        </div>
    </section>

    <div class="flex flex-col gap-5">
        <section class="rounded-[12px] border border-border bg-surface px-5 py-[22px] md:px-6">
            <h2 class="mb-[18px] text-[15px] font-bold text-ink">Metrik Prestasi</h2>
            <div class="flex flex-col gap-[18px]">
                @foreach ($metrics as $m)
                    <div>
                        <div class="mb-[7px] flex items-center justify-between gap-2"><span class="flex items-center gap-2 text-[13px] font-semibold text-ink-2"><i class="ph ph-{{ $m['icon'] }} text-[16px] {{ $m['color'] }}"></i>{{ $m['label'] }}</span><span class="text-[13.5px] font-bold text-ink">{{ $m['value'] }}</span></div>
                        <div class="h-[9px] overflow-hidden rounded-[20px] bg-primary-soft"><div class="h-full rounded-[20px] {{ $m['bar'] }}" style="width: {{ min(100, $m['pct']) }}%"></div></div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="overflow-hidden rounded-[12px] border border-border bg-surface">
            <div class="px-[22px] pt-[18px] pb-3"><h2 class="text-[15px] font-bold text-ink">Sejarah Ranking (Audit)</h2></div>
            @forelse ($history as $h)
                @php $up = $h->from_rank === null ? null : $h->to_rank > $h->from_rank; @endphp
                <div class="flex items-center gap-3 border-t border-divider px-[22px] py-3">
                    <span @class(['flex size-8 shrink-0 items-center justify-center rounded-[8px]', 'bg-success-soft text-success' => $up === true, 'bg-danger-soft text-danger' => $up === false, 'bg-primary-soft text-primary' => $up === null])><i class="ph ph-{{ $up === null ? 'flag' : ($up ? 'arrow-up' : 'arrow-down') }} text-[16px]"></i></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[13px] font-semibold text-ink">{{ $h->from_rank === null ? 'Ranking awal ditetapkan: '.$h->to_rank : 'Ranking '.($up ? 'dinaikkan' : 'diturunkan').' '.$h->from_rank.' → '.$h->to_rank }}</span>
                        <span class="text-[11.5px] text-faint">{{ $h->changedBy ? $h->changedBy->name.' (Admin Sistem)' : 'Sistem' }} &middot; {{ tarikh($h->created_at) }}</span>
                    </span>
                </div>
            @empty
                <div class="border-t border-divider px-[22px] py-6 text-center text-[12.5px] text-faint">Belum ada sejarah ranking.</div>
            @endforelse
        </section>
    </div>
</div>
