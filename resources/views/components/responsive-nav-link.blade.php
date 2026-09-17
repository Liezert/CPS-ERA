@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brand text-start text-sm font-sans font-medium text-brand-dark bg-brand-tint focus:outline-none transition-colors duration-150'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-sm font-sans font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 hover:border-neutral-300 focus:outline-none transition-colors duration-150';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
