@props([
    'values' => [],
    'readonly' => false,
])
            {{-- Header Dokumen Kontrol Mutu --}}
            <div class="pb-4 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1.5 min-w-0">
                    <h2 class="text-base sm:text-lg font-bold text-neutral-900 font-sans tracking-tight">
                        Lembar Kerja Investigasi &amp; Tindakan Korektif (FTK)
                    </h2>
                </div>
                
                {{-- Metadata Register BA Unnested (Tanpa Pembungkus Nested Cards) --}}
                <div class="flex items-center gap-2 text-xs shrink-0 self-start sm:self-center">
                    <span class="text-neutral-500 font-sans font-medium">Nomor Register:</span>
                    <span class="font-mono text-xs font-bold text-neutral-900 tracking-wide select-all">
                        {{ $values['nomorBaPreview'] }}
                    </span>
                    <button type="button"
                            @click="copyBaNumber()"
                            title="Salin nomor FTK"
                            class="p-1 text-neutral-500 hover:text-neutral-900 rounded hover:bg-neutral-100 active:scale-95 transition-all duration-150">
                        <span x-show="!copiedBa">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75" />
                            </svg>
                        </span>
                        <span x-show="copiedBa" class="text-brand font-bold text-[10px]">✓</span>
                    </button>
                </div>
            </div>
