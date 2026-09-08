@props([
    'head' => null,
    'body' => null,
    'mobile' => null,
])

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    {{-- Tampilan Desktop & Tablet (>= 820px): Tabel Penuh dengan Hairline Divider --}}
    <div class="hidden tablet:block w-full overflow-x-auto border border-neutral-200 rounded-md bg-white">
        <table class="w-full text-left border-collapse">
            @if ($head)
                <thead class="bg-neutral-50/80 border-b border-neutral-200 text-xs font-sans font-medium text-neutral-500">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-neutral-200 text-sm font-sans text-neutral-900">
                {{ $body ?? $slot }}
            </tbody>
        </table>
    </div>

    {{-- Tampilan Mobile (< 820px / 375px): List Card Bertumpuk (1 card = 1 baris) --}}
    <div class="block tablet:hidden space-y-3">
        {{ $mobile ?? $slot }}
    </div>
</div>
