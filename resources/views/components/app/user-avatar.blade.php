@props(['user', 'size' => 40])

{{-- Current-user avatar: uploaded photo or olive circle with gold initials (design "MN"). --}}
@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: {{ $size }}px; height: {{ $size }}px"
         {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover']) }}>
@else
    <x-ui.avatar :name="$user->name" tone="brand" :size="$size" {{ $attributes }} />
@endif
