@props([
    'variant' => 'neutral',
])

@php
    $variantClasses = match ($variant) {
        'brand', 'active', 'closed' => 'border-brand text-brand-dark',
        'reviewed' => 'border-neutral-500 text-neutral-900',
        'danger' => 'border-red-600 text-red-700',
        default => 'border-neutral-200 text-neutral-900',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium border bg-transparent rounded-none tracking-normal {$variantClasses}"]) }}>
    {{ $slot }}
</span>
