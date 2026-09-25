@props([
    'values' => [],
    'readonly' => false,
])
            {{-- BAGIAN 6: IDENTIFIKASI DAMPAK LANJUTAN & POTENSI --}}
            <section class="space-y-4" aria-labelledby="section-dampak">
                <div class="pb-1.5 border-b border-neutral-200 flex items-center justify-between">
                    <h3 id="section-dampak" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                        6. Identifikasi Dampak Lanjutan &amp; Potensi
                    </h3>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Kartu Potensi Risiko --}}
                    <label class="flex items-start gap-3 p-3.5 bg-white border rounded-md cursor-pointer select-none transition-all duration-200 ease-out {{ $values['isPotensiRisiko'] ? 'border-amber-400 ring-1 ring-amber-400 bg-amber-50/30 shadow-xs' : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 hover:shadow-2xs' }}">
                        <input type="checkbox"
                               @if ($readonly) disabled @checked($values['isPotensiRisiko']) @else wire:model.live="isPotensiRisiko" @endif
                               class="rounded-sm border-neutral-300 text-brand focus:ring-brand h-4 w-4 mt-0.5 shrink-0" />
                        <div class="space-y-0.5 min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                <span class="text-xs font-semibold text-neutral-900 block font-sans">
                                    Potensi Risiko Signifikan
                                </span>
                            </div>
                        </div>
                    </label>

                    {{-- Kartu Potensi Peluang --}}
                    <label class="flex items-start gap-3 p-3.5 bg-white border rounded-md cursor-pointer select-none transition-all duration-200 ease-out {{ $values['isPotensiPeluang'] ? 'border-brand ring-1 ring-brand bg-brand-tint/25 shadow-xs' : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 hover:shadow-2xs' }}">
                        <input type="checkbox"
                               @if ($readonly) disabled @checked($values['isPotensiPeluang']) @else wire:model.live="isPotensiPeluang" @endif
                               class="rounded-sm border-neutral-300 text-brand focus:ring-brand h-4 w-4 mt-0.5 shrink-0" />
                        <div class="space-y-0.5 min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                </svg>
                                <span class="text-xs font-semibold text-neutral-900 block font-sans">
                                    Potensi Peluang Improvement
                                </span>
                            </div>
                        </div>
                    </label>
                </div>
            </section>
