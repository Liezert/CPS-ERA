@php
    $state = (int) ($getState() ?? 0);
    $max = (int) ($max ?? 100);
    $unit = $unit ?? '';
    $percent = $max > 0 ? min(100, (int) round(($state / $max) * 100)) : 0;

    $barColor = match (true) {
        $percent >= 100 => 'bg-emerald-500',
        $percent > 0 => 'bg-emerald-600',
        default => 'bg-gray-300 dark:bg-gray-600',
    };
@endphp

<div class="w-full min-w-[140px] max-w-[180px] py-1">
    <div class="mb-1 flex items-center justify-between text-xs">
        <span class="font-medium text-gray-900 dark:text-gray-100">
            {{ $state }} / {{ $max }} {{ $unit }}
        </span>
        <span class="font-mono text-[11px] font-semibold text-gray-500 dark:text-gray-400">
            {{ $percent }}%
        </span>
    </div>
    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div
            class="h-full rounded-full transition-all duration-500 {{ $barColor }}"
            style="width: {{ $percent }}%;"
        ></div>
    </div>
</div>
