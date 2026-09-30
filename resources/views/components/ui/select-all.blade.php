@props([
    'checked' => false,
    'size' => 20,   // 20 (chkBox) | 18 (table checkBox)
])

{{-- Header "pilih semua" checkbox for data-table heads (wire:click="toggleAll"). --}}
<span class="flex">
    <button type="button" aria-label="Pilih semua" aria-pressed="{{ $checked ? 'true' : 'false' }}"
        {{ $attributes->merge(['wire:click' => 'toggleAll'])->class([
            'flex shrink-0 items-center justify-center border',
            'size-5 rounded-[6px]' => (int) $size === 20,
            'size-[18px] rounded-[5px]' => (int) $size !== 20,
            'border-primary bg-primary text-white' => $checked,
            'border-checkbox-border bg-surface text-transparent' => ! $checked,
        ]) }}>
        <i class="ph-fill ph-check text-[12px]"></i>
    </button>
</span>
