<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center font-sans font-medium text-sm text-neutral-900 bg-white border border-neutral-200 hover:bg-neutral-50 active:bg-neutral-50 px-4 py-2 rounded-none focus:outline-none focus:ring-2 focus:ring-neutral-900/10 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>

