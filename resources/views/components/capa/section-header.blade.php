@props([
    'title' => '',
    'badge' => '',
    'badgeVariant' => 'neutral',
])

<div {{ $attributes->merge([
    'class' => 'flex items-center justify-between w-full gap-2',
    'style' => 'display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 0.5rem;'
]) }}>
    <span style="font-weight: 700; font-size: 0.875rem; color: #18181b; line-height: 1.25rem;">
        {{ $title }}
    </span>

    @if($badge)
        <x-capa.section-badge :text="$badge" :variant="$badgeVariant" />
    @endif
</div>
