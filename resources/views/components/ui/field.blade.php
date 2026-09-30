@props([
    'label' => null,
    'hint' => null,        // grey inline note after the label
    'error' => null,       // validation key (reads $errors) or message
    'as' => 'input',       // input | textarea | select
    'span' => false,       // grid-column: 1 / -1 in two-column modal forms
])

@php
    $name = $attributes->get('name') ?? $attributes->wire('model')->value();
    // `error` is a validation key (looked up in $errors) or, if it contains a space, a literal message.
    $message = match (true) {
        $error !== null && str_contains($error, ' ') => $error,
        $error !== null => isset($errors) ? ($errors->first($error) ?: null) : null,
        default => $name && isset($errors) ? ($errors->first($name) ?: null) : null,
    };
    $id = $attributes->get('id') ?? ($name ? 'f-'.str_replace(['.', '[', ']'], '-', $name) : null);
    $control = 'w-full rounded-[9px] border bg-surface px-3 py-[10px] text-[13px] text-ink outline-none placeholder:text-faint focus:border-primary max-md:min-h-11 '
        .($message ? 'border-danger' : 'border-border');
@endphp

{{-- Form field as in design modals: label 12px/600 #334155 (mb 6px), control 13px, radius 9, 10px 12px. --}}
<div @class(['min-w-0', 'col-span-full' => $span])>
    @if ($label)
        <label @if ($id) for="{{ $id }}" @endif class="mb-1.5 block text-[12px] font-semibold text-ink-2">
            {{ $label }} @if ($hint)<span class="font-normal text-faint">{{ $hint }}</span>@endif
        </label>
    @endif

    @if ($as === 'textarea')
        <textarea @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => $control.' min-h-[90px] resize-y']) }}>{{ $slot }}</textarea>
    @elseif ($as === 'select')
        <select @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => $control]) }}>{{ $slot }}</select>
    @else
        <input @if ($id) id="{{ $id }}" @endif {{ $attributes->merge(['class' => $control, 'type' => 'text']) }}>
    @endif

    @if ($message)
        <p class="mt-1 text-[11.5px] font-medium text-danger">{{ $message }}</p>
    @endif
</div>
