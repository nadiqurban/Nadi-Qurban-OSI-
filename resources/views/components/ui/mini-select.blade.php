@props([
    'options' => [],        // [value => label]
    'placeholder' => null,  // "Semua Servis" → value ''
    'block' => false,       // full width (table cells, modal forms)
])

{{-- Native select from the pipeline screens' filter rows (12.5px, radius 8, 8px 11px). --}}
<select {{ $attributes->class([
    'cursor-pointer rounded-[8px] border border-border bg-surface px-[11px] py-2 text-[12.5px] text-ink-2 outline-none focus:border-primary max-md:min-h-11 max-md:text-[16px]',
    'w-full' => $block,
    'max-md:w-full' => ! $block,
]) }}>
    @if ($placeholder !== null)
        <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
    {{ $slot }}
</select>
