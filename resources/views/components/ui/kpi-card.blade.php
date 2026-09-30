@props([
    'icon',
    'value',
    'label',
    'tone' => 'primary',
    'delta' => null,
    'trend' => 'up',   // up (green) | down (red) | flat (neutral) | warn (amber) | info (blue)
])

@php
    $deltaClasses = [
        'up' => 'bg-success-soft text-success',
        'down' => 'bg-danger-soft text-danger',
        'flat' => 'bg-neutral-soft text-neutral dark:bg-divider dark:text-muted',
        'warn' => 'bg-warning-soft text-warning',
        'info' => 'bg-info-soft text-info',
    ][$trend] ?? '';
@endphp

{{-- Dashboard KPI card (Dashboard Operasi.dc.html) --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 rounded-[12px] border border-border bg-surface p-[14px] shadow-[0_1px_2px_rgba(16,24,40,.04)] md:gap-[14px] md:px-5 md:py-[18px]']) }}>
    <div class="flex items-center justify-between gap-2">
        <div class="flex size-10 shrink-0 items-center justify-center rounded-[10px] {{ \App\Support\Tone::classes($tone) }}">
            <i class="ph ph-{{ $icon }} block text-[18px] leading-none"></i>
        </div>
        @if ($delta)
            <span class="rounded-[20px] px-[9px] py-[3px] text-[11px] font-bold whitespace-nowrap md:text-[12px] {{ $deltaClasses }}">{{ $delta }}</span>
        @endif
    </div>
    <div>
        <div class="text-[20px] leading-none font-bold text-ink md:text-[27px]">{{ $value }}</div>
        <div class="mt-[7px] text-[12px] text-muted md:text-[12.5px]">{{ $label }}</div>
    </div>
</div>
