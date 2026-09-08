@props([
    'href' => null,
])

@php
    $baseClasses = 'p-4 rounded-md border border-neutral-200 bg-white transition-colors duration-150 space-y-2 block';
    $interactiveClasses = $href ? 'hover:bg-neutral-50 active:bg-neutral-100 cursor-pointer' : '';
    $classes = "{$baseClasses} {$interactiveClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </div>
@endif
