@props([
    'label',
    'icon',
    'type' => 'text',
    'toggle' => false,      // password field with eye toggle
    'error' => null,        // validation key
    'invalid' => false,     // Alpine expression → red border (e.g. mismatch)
])

@php
    $name = $attributes->wire('model')->value() ?: $attributes->get('name');
    $id = $attributes->get('id') ?? 'a-'.$name;
    $message = $error ? ($errors->first($error) ?: null) : null;
@endphp

{{-- Login.dc.html field: label 13px/600, box radius 9, 12px 14px, 18px icon, 14px input --}}
<div x-data="{ show: false }">
    <label for="{{ $id }}" class="mb-[7px] block text-[13px] font-semibold text-ink-2">{{ $label }}</label>
    <div @if ($invalid) :class="({{ $invalid }}) ? 'border-[#F3C9C9]' : 'border-border'" @endif
         @class([
             'flex items-center gap-[10px] rounded-[9px] border bg-white px-[14px] py-3 focus-within:border-primary max-md:min-h-12',
             'border-danger' => $message,
             'border-border' => ! $message && ! $invalid,
         ])>
        <i class="ph ph-{{ $icon }} text-[18px] text-faint"></i>
        <input id="{{ $id }}"
               @if ($toggle) :type="show ? 'text' : 'password'" type="password" @else type="{{ $type }}" @endif
               {{ $attributes->merge(['class' => 'min-w-0 flex-1 bg-transparent text-[14px] text-ink outline-none placeholder:text-faint']) }}
               @if ($message) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
        @if ($toggle)
            <button type="button" @click="show = !show" class="-mr-2 flex size-9 items-center justify-center text-faint" :aria-label="show ? 'Sembunyi kata laluan' : 'Tunjuk kata laluan'">
                <i class="ph text-[18px]" :class="show ? 'ph-eye-slash' : 'ph-eye'"></i>
            </button>
        @endif
    </div>
    @if ($message)
        <p id="{{ $id }}-error" class="mt-1.5 flex items-center gap-[5px] text-[11.5px] text-danger"><i class="ph ph-warning-circle text-[14px]"></i> {{ $message }}</p>
    @endif
    {{ $slot }}
</div>
