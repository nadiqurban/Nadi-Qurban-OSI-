@props(['history'])

{{-- Login history rows (Login.dc.html history view) --}}
<div class="flex flex-col gap-[10px]">
    @forelse ($history as $h)
        @php
            $current = $h->isCurrentSession();
            $failed = $h->status !== \App\Enums\LoginStatus::Success;
            [$tone, $icon] = match (true) {
                $current => ['success', 'desktop'],
                $failed => ['danger', 'warning'],
                $h->device === 'desktop' => ['info', 'desktop'],
                default => ['info', 'device-mobile'],
            };
            $when = $h->created_at->isToday() ? 'Hari ini, '.$h->created_at->format('H:i')
                : ($h->created_at->isYesterday() ? 'Semalam, '.$h->created_at->format('H:i') : tarikh($h->created_at, true));
        @endphp
        <div class="flex items-center gap-[13px] rounded-[11px] border border-border px-[15px] py-[13px]">
            <span class="flex size-[38px] shrink-0 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes($tone) }}"><i class="ph ph-{{ $icon }} text-[19px]"></i></span>
            <div class="min-w-0 flex-1">
                <div class="truncate text-[13px] font-semibold text-ink">{{ $h->deviceLabel() }}</div>
                <div class="text-[11.5px] text-faint">{{ $h->location ?? 'Lokasi tidak dikenali' }} · {{ $h->maskedIp() }} · {{ $when }}</div>
            </div>
            <span @class([
                'shrink-0 rounded-[20px] px-[10px] py-1 text-[10.5px] font-bold',
                'bg-success-soft text-success' => $current,
                'bg-danger-soft text-danger' => ! $current && $failed,
                'bg-bg text-muted' => ! $current && ! $failed,
            ])>{{ $current ? 'Sesi Semasa' : $h->status->label() }}</span>
        </div>
    @empty
        <x-ui.empty-state icon="clock-counter-clockwise" title="Tiada rekod log masuk" />
    @endforelse
</div>
