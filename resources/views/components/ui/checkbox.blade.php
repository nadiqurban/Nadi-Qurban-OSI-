@props([
    'label' => null,
    'size' => 20,   // 20 (chkBox, default) | 18 (table checkBox in Tempahan list)
])

{{--
    Visual checkbox from the design (chkBox): 20×20, radius 6, border 1.5px #CBD5E1,
    checked = #42481c + ph-fill ph-check. A real <input> keeps it accessible and
    works with wire:model / x-model. Label area expands the touch target on phones.
--}}
<label class="relative inline-flex cursor-pointer items-center gap-2 max-md:min-h-11 max-md:min-w-11 max-md:justify-center">
    <input type="checkbox" {{ $attributes->merge(['class' => 'peer absolute inset-0 m-0 size-full cursor-pointer opacity-0']) }}>
    <span @class([
        'pointer-events-none flex shrink-0 items-center justify-center border-[1.5px] border-checkbox-border bg-surface text-transparent transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40 peer-indeterminate:border-primary peer-indeterminate:bg-primary peer-indeterminate:text-white',
        'size-5 rounded-[6px]' => $size == 20,
        'size-[18px] rounded-[5px]' => $size == 18,
    ])>
        <i class="ph-fill ph-check text-[12px]"></i>
    </span>
    @if ($label)
        <span class="text-[13px] text-ink-2">{{ $label }}</span>
    @endif
</label>
