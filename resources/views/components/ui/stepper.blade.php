@props([
    'steps' => [],   // [['label' => 'Tempahan Diterima', 'state' => 'done|current|pending', 'time' => '12 Jun, 09:14'], ...]
])

{{-- Vertical workflow ("Aliran Kerja", Tempahan & Pelanggan.dc.html) --}}
<ol {{ $attributes->merge(['class' => 'flex flex-col']) }}>
    @foreach ($steps as $i => $step)
        @php
            $state = $step['state'] ?? 'pending';
            $isLast = $loop->last;
        @endphp
        <li class="flex gap-[14px]">
            <div class="flex flex-col items-center">
                <span @class([
                    'flex size-7 shrink-0 items-center justify-center rounded-full',
                    'bg-primary text-white' => $state === 'done',
                    'bg-gold text-white' => $state === 'current',
                    'bg-neutral-soft text-faint' => $state === 'pending',
                ])>
                    <i @class([
                        'text-[14px]',
                        'ph-fill ph-check' => $state === 'done',
                        'ph ph-dot-outline' => $state === 'current',
                        'ph ph-circle' => $state === 'pending',
                    ])></i>
                </span>
                <span @class([
                    'my-1 min-h-[14px] w-0.5 flex-1',
                    'bg-transparent' => $isLast,
                    'bg-primary' => ! $isLast && $state === 'done',
                    'bg-border' => ! $isLast && $state !== 'done',
                ])></span>
            </div>
            <div class="pb-4">
                <div @class([
                    'text-[13px] font-semibold',
                    'text-ink' => $state === 'done',
                    'text-gold' => $state === 'current',
                    'text-faint' => $state === 'pending',
                ])>{{ $step['label'] }}</div>
                @php $time = $step['time'] ?? ($state === 'current' ? 'Sedang diproses' : null); @endphp
                @if ($time)
                    <div class="mt-0.5 text-[11.5px] text-faint">{{ $time }}</div>
                @endif
            </div>
        </li>
    @endforeach
</ol>
