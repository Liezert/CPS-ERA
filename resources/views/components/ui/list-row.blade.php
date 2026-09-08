@props([
    'href' => null,
    'interactive' => false,
])

@php
    $baseClasses = 'py-3.5 px-4 border-b border-neutral-200 last:border-b-0 flex items-center justify-between text-sm transition-colors duration-150';
    $interactiveClasses = ($href || $interactive) ? 'hover:bg-neutral-50/80 active:bg-neutral-100/70 cursor-pointer' : '';
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
