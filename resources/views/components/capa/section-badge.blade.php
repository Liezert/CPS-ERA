@props([
    'text' => '',
    'variant' => 'neutral', // neutral | rca | warning | brand | danger
])

@php
    $variant = strtolower($variant);
    
    // Auto detect variant from text if default neutral
    if ($variant === 'neutral') {
        $lower = strtolower($text);
        if (str_contains($lower, 'rca') || str_contains($lower, 'kaizen')) {
            $variant = 'rca';
        } elseif (str_contains($lower, 'containment') || str_contains($lower, 'koreksi')) {
            $variant = 'warning';
        } elseif (str_contains($lower, 'corrective') || str_contains($lower, 'korektif')) {
            $variant = 'brand';
        } elseif (str_contains($lower, 'risiko') || str_contains($lower, 'danger')) {
            $variant = 'danger';
        }
    }

    $styles = match ($variant) {
        'rca' => [
            'class' => 'text-neutral-800 bg-white border-neutral-300 uppercase tracking-wider shadow-2xs',
            'inline' => 'color: #27272a; background-color: #ffffff; border: 1px solid #d4d4d8; text-transform: uppercase; letter-spacing: 0.05em;',
        ],
        'warning' => [
            'class' => 'text-amber-900 bg-amber-100 border-amber-300',
            'inline' => 'color: #78350f; background-color: #fef3c7; border: 1px solid #fcd34d;',
        ],
        'brand', 'success' => [
            'class' => 'text-brand-dark bg-brand-tint border-brand/30',
            'inline' => 'color: #085C30; background-color: #E8F5EC; border: 1px solid rgba(11, 120, 64, 0.3);',
        ],
        'danger' => [
            'class' => 'text-red-800 bg-red-50 border-red-200',
            'inline' => 'color: #991b1b; background-color: #fef2f2; border: 1px solid #fecaca;',
        ],
        default => [
            'class' => 'text-neutral-600 bg-neutral-100 border-neutral-200',
            'inline' => 'color: #52525b; background-color: #f4f4f5; border: 1px solid #e4e4e7;',
        ],
    };
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium rounded-badge border whitespace-nowrap {$styles['class']}",
    'style' => "display: inline-flex; align-items: center; padding: 2px 8px; font-size: 11px; font-family: 'IBM Plex Mono', monospace, ui-monospace; font-weight: 500; border-radius: 2px; white-space: nowrap; {$styles['inline']}"
]) }}>
    {{ $slot->isNotEmpty() ? $slot : $text }}
</span>
