@props([
    'placeholder' => 'Cari…',
])

<label class="flex min-w-0 flex-1 items-center gap-[10px] rounded-[9px] border border-border bg-bg px-[14px] py-[10px] md:min-w-[200px] max-md:min-h-11">
    <i class="ph ph-magnifying-glass text-[17px] text-faint"></i>
    <input type="search" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}"
           {{ $attributes->merge(['class' => 'min-w-0 flex-1 bg-transparent text-[13.5px] text-ink outline-none placeholder:text-faint']) }}>
</label>
