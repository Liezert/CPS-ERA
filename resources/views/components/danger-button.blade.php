<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center font-sans font-medium text-sm text-white bg-red-600 hover:bg-red-700 active:bg-red-700 px-4 py-2 border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>

