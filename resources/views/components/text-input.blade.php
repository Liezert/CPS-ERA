@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-white border border-neutral-200 text-neutral-900 placeholder-neutral-400 focus:border-brand focus:ring-1 focus:ring-brand rounded-md text-sm font-sans shadow-none transition-colors disabled:bg-neutral-100 disabled:cursor-not-allowed']) }}>

