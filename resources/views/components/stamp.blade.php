@props([
    'status' => 'created',
])

@php
    $statusKey = strtolower((string) $status);

    $variantClasses = match ($statusKey) {
        'closed', 'disetujui', 'approved' => 'border-brand text-brand-dark',
        'reviewed', 'ditinjau', 'in_review' => 'border-neutral-500 text-neutral-900',
        'danger', 'rejected', 'ditolak' => 'border-red-600 text-red-700',
        default => 'border-neutral-200 text-neutral-900',
    };

    $label = strtoupper(str_replace('_', ' ', (string) $status));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium border bg-transparent rounded-none tracking-normal {$variantClasses}"]) }}>
    {{ $label }}
</span>
