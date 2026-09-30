@props([
    'value' => 'RM',
    'action' => 'setDisplay',   // Livewire method receiving 'RM' | 'USD'
])

{{-- RM / USD segmented toggle (Vendor.dc.html currencyTabs). --}}
<div {{ $attributes->class('flex w-fit items-center overflow-hidden rounded-[8px] border border-border bg-bg') }} role="radiogroup" aria-label="Mata wang">
    @foreach (['RM', 'USD'] as $code)
        <button type="button" role="radio" aria-checked="{{ $value === $code ? 'true' : 'false' }}" wire:click="{{ $action }}('{{ $code }}')"
                @class(['min-h-8 px-[14px] py-[7px] text-[12px] font-bold max-md:min-h-10', 'bg-primary text-white' => $value === $code, 'text-muted' => $value !== $code])>{{ $code }}</button>
    @endforeach
</div>
