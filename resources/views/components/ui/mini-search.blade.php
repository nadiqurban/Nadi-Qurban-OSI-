@props([
    'placeholder' => 'Cari No. Tempahan…',
])

{{-- Compact search pill from the pipeline screens (icon + 170px input). --}}
<label class="inline-flex items-center gap-[7px] rounded-[8px] border border-border bg-surface px-[11px] py-[7px] max-md:min-h-11 max-md:flex-1">
    <i class="ph ph-magnifying-glass text-[15px] text-faint"></i>
    <input type="search" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
        {{ $attributes->class('w-[170px] bg-transparent text-[12.5px] text-ink outline-none max-md:w-full max-md:text-[16px]') }}>
</label>
