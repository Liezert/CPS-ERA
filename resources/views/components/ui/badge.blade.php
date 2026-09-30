@props([
    'status' => null,   // created | reviewed | closed | unlocked | locked | draft | published
    'variant' => null,  // brand | neutral | muted | danger
])

@php
    $key = strtolower(trim((string) ($status ?? $variant ?? 'neutral')));

    // Aturan Design System §5:
    // Kotak bersudut tegas, border 1px neutral-200 (atau brand kalau status "aktif/disetujui"),
    // TANPA fill solid, radius 2px (rounded-badge). Font IBM Plex Mono (font-mono).
    // Status menunggu review memakai tint amber, sama dengan antrean review di dashboard.
    $styleClasses = match ($key) {
        'approved', 'closed', 'unlocked', 'published', 'brand', 'active' => 'border-brand text-brand-dark bg-transparent',
        'pending_supervisor', 'pending_hr' => 'border-amber-500 text-amber-900 bg-amber-50',
        'submitted', 'reviewed', 'neutral' => 'border-neutral-500 text-neutral-900 bg-transparent',
        'created', 'locked', 'draft', 'muted' => 'border-neutral-200 text-neutral-500 bg-transparent',
        'rejected', 'danger', 'revision_requested' => 'border-red-500 text-red-700 bg-transparent',
        default => 'border-neutral-200 text-neutral-900 bg-transparent',
    };

    // Status CAPA memakai label resmi enum (Bahasa Indonesia); key lain: "pending_hr" -> "Pending HR".
    $label = \App\Enums\BaIncidentStatus::tryFrom($key)?->getLabel()
        ?? preg_replace('/\bHr\b/', 'HR', \Illuminate\Support\Str::headline($key));
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium border rounded-badge tracking-tight whitespace-nowrap {$styleClasses}"]) }}>
    {{ $slot->isNotEmpty() ? $slot : $label }}
</span>
