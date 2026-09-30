@props([
    'url',
    'isPdf' => false,
    'name' => 'Bukti Bayaran',
    'max' => '66vh',
])

{{-- Proof preview (Tempahan & Pelanggan.dc.html proofEl): image, or PDF page 1 via pdf.js. --}}
<div x-data="proofPreview(@js($url), @js((bool) $isPdf))" {{ $attributes->merge(['class' => 'flex w-full justify-center']) }} wire:ignore>
    @if ($isPdf)
        <div x-show="loading" class="py-10 text-[12.5px] text-faint"><i class="ph ph-circle-notch animate-spin"></i> Memuatkan PDF…</div>
        <div x-cloak x-show="failed" class="py-10 text-[12.5px] text-faint">Pratonton tidak tersedia — buka dalam tab baharu.</div>
        <canvas x-ref="canvas" x-show="!loading && !failed" class="max-w-full rounded-[10px] bg-white shadow-[0_4px_16px_rgba(0,0,0,.12)]" style="max-height: {{ $max }}"></canvas>
    @else
        <img src="{{ $url }}" alt="{{ $name }}" class="max-w-full rounded-[10px] object-contain shadow-[0_4px_16px_rgba(0,0,0,.12)]" style="max-height: {{ $max }}">
    @endif
</div>
