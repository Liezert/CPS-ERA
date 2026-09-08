@props([
    'label',
    'value',
    'meta' => null,
    'isTechnical' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white p-5 border border-neutral-200 rounded-none flex flex-col justify-between']) }}>
    <div>
        <p class="text-xs font-sans font-medium text-neutral-500 uppercase tracking-normal">
            {{ $label }}
        </p>
        <p class="mt-2 text-2xl font-semibold text-neutral-900 tracking-tight {{ $isTechnical ? 'font-mono' : 'font-sans' }}">
            {{ $value }}
        </p>
    </div>

    @if ($meta || $slot->isNotEmpty())
        <div class="mt-3 pt-3 border-t border-neutral-200 text-xs text-neutral-500 font-sans">
            {{ $meta ?? $slot }}
        </div>
    @endif
</div>
