@php
    $state = (int) ($getState() ?? 0);
    $max = (int) ($max ?? 100);
    $unit = $unit ?? '';
    $percent = $max > 0 ? min(100, (int) round(($state / $max) * 100)) : 0;

    $barHex = match (true) {
        $percent >= 100 => '#10b981', // emerald-500
        $percent > 0 => '#059669',    // emerald-600
        default => '#e5e7eb',         // gray-200
    };
@endphp

<div class="fi-ta-progress" style="min-width: 140px; max-width: 180px; padding: 4px 0;">
    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; margin-bottom: 5px; line-height: 1.2;">
        <span style="font-weight: 600; color: #111827;">
            {{ $state }} / {{ $max }} {{ $unit }}
        </span>
        <span style="font-family: monospace; font-size: 11px; font-weight: 700; color: #6b7280;">
            {{ $percent }}%
        </span>
    </div>
    <div style="height: 8px; width: 100%; background-color: #e5e7eb; border-radius: 9999px; overflow: hidden; position: relative;">
        <div
            style="height: 100%; width: {{ $percent }}%; background-color: {{ $barHex }}; border-radius: 9999px; transition: width 0.4s ease;"
        ></div>
    </div>
</div>
