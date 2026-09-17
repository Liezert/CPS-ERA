@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-sans font-medium text-xs text-neutral-700 mb-1']) }}>
    {{ $value ?? $slot }}
</label>

