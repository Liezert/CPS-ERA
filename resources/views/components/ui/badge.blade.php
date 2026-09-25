@props([
    'status' => null,   // created | reviewed | closed | unlocked | locked | draft | published
    'variant' => null,  // brand | neutral | muted | danger
])

@php
    $key = strtolower(trim((string) ($status ?? $variant ?? 'neutral')));

    // Aturan Design System §5:
    // Kotak bersudut tegas, border 1px neutral-200 (atau brand kalau status "aktif/disetujui"),
    // TANPA fill solid, radius 2px (rounded-badge). Font IBM Plex Mono (font-mono).
    $styleClasses = match ($key) {
        'approved', 'closed', 'unlocked', 'published', 'brand', 'active' => 'border-brand text-brand-dark',
        'submitted', 'reviewed', 'neutral', 'pending_supervisor', 'pending_hr' => 'border-neutral-500 text-neutral-900',
        'created', 'locked', 'draft', 'muted' => 'border-neutral-200 text-neutral-500',
        'rejected', 'danger', 'revision_requested' => 'border-red-500 text-red-700',
        default => 'border-neutral-200 text-neutral-900',
    };

    // Label default dari key: "pending_supervisor" -> "Pending Supervisor", "pending_hr" -> "Pending HR".
    $label = preg_replace('/\bHr\b/', 'HR', \Illuminate\Support\Str::headline($key));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium border bg-transparent rounded-badge tracking-tight whitespace-nowrap {$styleClasses}"]) }}>
    {{ $slot->isNotEmpty() ? $slot : $label }}
</span>
