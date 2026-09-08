@props([
    'label',
    'value',
    'meta' => null,
    'isTechnical' => false,
    'variant' => 'white', // white | neutral
])

@php
    $bgClass = $variant === 'neutral' ? 'bg-neutral-50' : 'bg-white';
@endphp

<div {{ $attributes->merge(['class' => "p-5 rounded-md border border-neutral-200 {$bgClass} flex flex-col justify-between transition-colors"]) }}>
    <div>
        {{-- Label kecil di atas: Inter 12px font-medium, teks netral sekunder, normal case --}}
        <p class="text-xs font-sans font-medium text-neutral-500">
            {{ $label }}
        </p>

        {{-- Angka besar: H2 22px-24px font-semibold teks utama --}}
        <p class="mt-2 text-2xl font-semibold text-neutral-900 tracking-tight {{ $isTechnical ? 'font-mono' : 'font-sans' }}">
            {{ $value }}
        </p>
    </div>

    @if ($meta || $slot->isNotEmpty())
        <div class="mt-3 pt-3 border-t border-neutral-200/80 text-xs text-neutral-500 font-sans flex items-center justify-between">
            {{ $meta ?? $slot }}
        </div>
    @endif
</div>
