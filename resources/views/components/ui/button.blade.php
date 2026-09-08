@props([
    'variant' => 'primary', // primary | secondary | ghost | danger
    'size' => 'md',        // sm | md | lg
    'href' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-sans font-medium rounded-md transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $sizeClasses = match ($size) {
        'sm' => 'px-3 py-1.5 text-xs',
        'lg' => 'px-5 py-2.5 text-base',
        default => 'px-4 py-2 text-sm',
    };

    $variantClasses = match ($variant) {
        'secondary' => 'bg-white text-neutral-900 border border-neutral-200 hover:bg-neutral-50 active:bg-neutral-100 focus:ring-neutral-900/20',
        'ghost' => 'bg-transparent text-neutral-500 hover:text-neutral-900 hover:bg-neutral-100 focus:ring-neutral-900/10',
        'danger' => 'bg-white text-red-700 border border-red-200 hover:bg-red-50 focus:ring-red-500',
        // Primary: Fill brand #0B7840, teks putih, radius 8px, tanpa panah di teks
        default => 'bg-brand text-white hover:bg-brand-dark active:bg-brand-dark focus:ring-brand-dark border border-transparent shadow-none',
    };

    $classes = "{$baseClasses} {$sizeClasses} {$variantClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $disabled ? 'disabled' : '' }} {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
