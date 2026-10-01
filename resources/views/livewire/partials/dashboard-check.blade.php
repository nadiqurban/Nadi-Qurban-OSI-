@php $hidden = in_array($key, $collapsed, true); @endphp
{{-- Card collapse checkbox (design mkChk: 20×20, radius 6; ticked = card collapsed). Saved per user. --}}
<button type="button" id="chk-{{ $key }}" wire:click="toggleCard('{{ $key }}')" role="switch" aria-checked="{{ $hidden ? 'true' : 'false' }}"
        aria-label="{{ $hidden ? 'Tunjuk' : 'Sembunyi' }} {{ $label }}" title="{{ $hidden ? 'Tunjuk' : 'Sembunyi' }} kad"
        @class([
            'relative flex size-5 shrink-0 items-center justify-center rounded-[6px] before:absolute before:-inset-3 before:content-[\'\'] md:before:hidden',
            'border border-primary bg-primary text-white' => $hidden,
            'border-[1.5px] border-border text-transparent' => ! $hidden,
        ])>
    <i class="ph-fill ph-check text-[12px]"></i>
</button>
