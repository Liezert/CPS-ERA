@props([
    'field',
    'title' => 'Panduan Pengisian',
])
@php($guide = \App\Models\BaIncident::FIELD_GUIDES[$field]['guide'])
{{--
    Ikon info + popover kerangka isian. Popover di-teleport ke <body> dan diposisikan x-anchor
    (flip/shift otomatis) supaya tidak terpotong overflow kartu form. Dibungkus .capa-scope agar
    tetap ber-style di panel admin, tempat CSS form CAPA di-scope ke kelas tersebut.
--}}
<span x-data="{ open: false }"
      @mouseenter="open = true"
      @mouseleave="open = false"
      @click.outside="open = false"
      @keydown.escape.window="open = false"
      class="inline-flex shrink-0">
    <button type="button"
            x-ref="trigger"
            @click="open = true"
            @focus="open = true"
            @blur="open = false"
            :aria-expanded="open"
            aria-label="{{ $title }}"
            class="inline-flex items-center justify-center w-4 h-4 rounded-full text-neutral-400 hover:text-brand focus:outline-none focus-visible:ring-2 focus-visible:ring-brand/30 transition-colors duration-150">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
    </button>

    <template x-teleport="body">
        <div class="capa-scope">
            <div x-show="open"
                 x-cloak
                 x-transition.opacity.duration.150ms
                 x-anchor.bottom-start.offset.6="$refs.trigger"
                 role="tooltip"
                 class="z-[60] w-72 max-w-[calc(100vw-2rem)] p-3 bg-white border border-neutral-200 rounded-md shadow-lg font-sans">
                <p class="text-xs font-semibold text-neutral-900 mb-1.5">{{ $title }}:</p>
                <ol class="list-decimal pl-4 space-y-1 text-[11px] text-neutral-600 leading-relaxed">
                    @foreach ($guide as $label => $text)
                        <li><span class="font-semibold text-neutral-800">{{ $label }}:</span> {{ $text }}</li>
                    @endforeach
                </ol>
            </div>
        </div>
    </template>
</span>
