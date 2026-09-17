@props([
    'label',
    'value',
    'unit' => null,
    'badge' => null,
    'meta' => null,
    'isTechnical' => false,
    'variant' => 'white', // white | neutral
])

@php
    $bgClass = $variant === 'neutral' ? 'bg-neutral-50' : 'bg-white';
@endphp

<div {{ $attributes->merge(['class' => "p-5 rounded-md border border-neutral-200 {$bgClass} flex flex-col justify-between transition-colors"]) }}>
    <div>
        {{-- Label kecil di atas: Inter 12px font-medium, teks netral sekunder per DS §2 --}}
        <p class="text-xs font-sans font-medium text-neutral-500">
            {{ $label }}
        </p>

        {{-- Angka besar: H2 22px-24px font-semibold teks utama dengan tabular-nums --}}
        <div class="mt-2 flex items-baseline gap-1.5 flex-wrap">
            @if ($value === '--' && $badge)
                <span class="inline-flex items-center px-2 py-1 rounded-badge text-xs font-mono font-medium bg-neutral-100 text-neutral-700 border border-neutral-200">
                    {{ $badge }}
                </span>
            @else
                <p class="text-2xl font-semibold text-neutral-900 tracking-tight font-mono tabular-nums">
                    {{ $value }}@if ($unit)<span class="text-xs font-sans font-medium text-neutral-500 ml-1">{{ $unit }}</span>@endif
                </p>
            @endif
        </div>
    </div>

    @if ($meta || $slot->isNotEmpty())
        <div class="mt-3 pt-3 border-t border-neutral-100 text-xs text-neutral-600 font-sans flex items-center justify-between">
            {{ $meta ?? $slot }}
        </div>
    @endif
</div>

