@props([
    'label' => 'Tarikh',
])

{{-- Date filter pill (calendar icon + native date input) from AWB & Tempahan Selesai. --}}
<label class="inline-flex items-center gap-1.5 rounded-[8px] border border-border bg-surface px-[10px] py-1.5 max-md:min-h-11 max-md:w-full">
    <i class="ph ph-calendar-blank text-[15px] text-primary"></i>
    <input type="date" aria-label="{{ $label }}" {{ $attributes->class('bg-transparent text-[12.5px] text-ink-2 outline-none max-md:flex-1 max-md:text-[16px]') }}>
</label>
