@props([
    'label',              // e.g. "Servis" — shown when nothing selected; "Semua {label}" option resets
    'options' => [],      // [value => label]
    'icon' => null,       // optional leading Phosphor icon (e.g. "funnel")
])

{{--
    Custom dropdown from Tempahan & Pelanggan.dc.html (not a native select).
    Bind with wire:model.live="service" or x-model="service" (x-modelable).
    Inside the mobile "Tapis" sheet it becomes full width and the list opens inline.
--}}
<div x-data="{ open: false, value: '', options: @js((object) $options) }"
     x-modelable="value"
     {{ $attributes->whereStartsWith(['wire:model', 'x-model']) }}
     @click.outside="open = false" @keydown.escape="open = false"
     {{ $attributes->whereDoesntStartWith(['wire:model', 'x-model'])->merge(['class' => 'relative max-md:w-full']) }}>
    <button type="button" @click="open = !open" :aria-expanded="open"
            :class="value ? 'bg-primary-soft border-primary text-primary' : 'bg-surface border-border text-ink-2'"
            class="flex w-full min-w-[140px] items-center justify-between gap-[10px] rounded-[9px] border px-[14px] py-[10px] text-left text-[13.5px] max-md:min-h-11">
        <span class="flex items-center gap-[9px]">@if ($icon)<i class="ph ph-{{ $icon }} text-[16px] text-muted"></i>@endif<span x-text="value ? (options[value] ?? value) : @js($label)">{{ $label }}</span></span>
        <i class="ph ph-caret-down text-[14px] text-faint transition-transform" :class="open && 'rotate-180'"></i>
    </button>
    <div x-cloak x-show="open" x-transition.origin.top
         class="z-30 flex max-h-[240px] min-w-[170px] flex-col overflow-y-auto rounded-[10px] border border-border bg-surface p-1.5 shadow-pop md:absolute md:top-[46px] md:left-0 max-md:mt-2">
        <button type="button" @click="value = ''; open = false"
                :class="value === '' ? 'bg-primary-soft text-primary font-bold' : 'text-ink-2'"
                class="rounded-[7px] px-[11px] py-[9px] text-left text-[13px] max-md:min-h-11">Semua {{ $label }}</button>
        @foreach ($options as $value => $optionLabel)
            <button type="button" @click="value = @js((string) $value); open = false"
                    :class="value === @js((string) $value) ? 'bg-primary-soft text-primary font-bold' : 'text-ink-2'"
                    class="rounded-[7px] px-[11px] py-[9px] text-left text-[13px] max-md:min-h-11">{{ $optionLabel }}</button>
        @endforeach
    </div>
</div>
