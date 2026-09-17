@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-sans font-medium text-xs text-brand-dark bg-brand-tint border border-brand/20 rounded-md p-3']) }}>
        {{ $status }}
    </div>
@endif
