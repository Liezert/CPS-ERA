@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brand text-sm font-sans font-medium leading-5 text-neutral-900 focus:outline-none focus:border-brand-dark transition-colors duration-150'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-sans font-medium leading-5 text-neutral-500 hover:text-neutral-900 hover:border-neutral-300 focus:outline-none focus:text-neutral-900 focus:border-neutral-300 transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
