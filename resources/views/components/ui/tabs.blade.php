@props([
    'items' => [],        // [['key' => 'pending', 'label' => 'Belum Disahkan', 'count' => 5, 'href' => null], ...]
    'active' => null,
    'variant' => 'tab',   // tab (radius 9 + count bubble, Pengesahan Bayaran) | pill (radius 20, period chips)
    'wireModel' => null,  // Livewire property to $set on click
    'alpine' => null,     // Alpine variable to set on click (client-side tabs)
])

{{-- Horizontal scroll with snap on small screens; never wraps the page. --}}
<div {{ $attributes->merge(['class' => 'nq-scroll-x -mx-4 flex items-center gap-2 px-4 md:mx-0 md:px-0']) }} role="tablist">
    @foreach ($items as $item)
        @php
            $key = $item['key'] ?? $item['label'];
            $isActive = ! $alpine && (string) $active === (string) $key;
            $base = $variant === 'pill'
                ? 'shrink-0 rounded-[20px] px-[15px] py-[7px] text-[13px] font-semibold max-md:min-h-10'
                : 'inline-flex shrink-0 items-center gap-2 rounded-[9px] px-[15px] py-[9px] text-[13px] font-semibold max-md:min-h-11';
            $on = 'bg-primary text-white hover:text-white';
            $off = $variant === 'pill' ? 'bg-bg text-muted hover:text-muted' : 'border border-border bg-surface text-ink-3 hover:text-ink-3';
            $tag = isset($item['href']) ? 'a' : 'button';
        @endphp
        <{{ $tag }}
            @if ($tag === 'a') href="{{ $item['href'] }}" wire:navigate @else type="button" @endif
            role="tab"
            @if ($wireModel) wire:click="$set('{{ $wireModel }}', '{{ $key }}')" @endif
            @if ($alpine)
                @click="{{ $alpine }} = @js($key)"
                :class="{{ $alpine }} === @js($key) ? @js($on) : @js($off)"
                :aria-selected="{{ $alpine }} === @js($key)"
                class="{{ $base }}"
            @else
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                class="{{ $base }} {{ $isActive ? $on : $off }}"
            @endif
        >
            {{ $item['label'] }}
            @if (array_key_exists('count', $item) && $item['count'] !== null)
                @if ($alpine)
                    <span class="rounded-[20px] px-2 py-px text-[11px] font-bold"
                          :class="{{ $alpine }} === @js($key) ? 'bg-white/22 text-white' : 'bg-neutral-soft text-muted'">{{ $item['count'] }}</span>
                @else
                    <span @class(['rounded-[20px] px-2 py-px text-[11px] font-bold', 'bg-white/22 text-white' => $isActive, 'bg-neutral-soft text-muted' => ! $isActive])>{{ $item['count'] }}</span>
                @endif
            @endif
        </{{ $tag }}>
    @endforeach
</div>
