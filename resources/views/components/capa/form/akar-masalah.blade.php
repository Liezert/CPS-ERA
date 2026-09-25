@props([
    'values' => [],
    'readonly' => false,
])
            {{-- BAGIAN 4: ANALISIS AKAR MASALAH (TANGGA KAUSALITAS 5 WHYS DENGAN PROGRESSIVE DISCLOSURE) --}}
            <section class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 sm:p-6 space-y-5" aria-labelledby="section-5whys">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-neutral-200 gap-2">
                    <div>
                        <h3 id="section-5whys" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                            4. Analisis Akar Masalah (5 Whys Causality Ladder)
                        </h3>
                    </div>
                </div>

                {{-- Tangga Kausalitas Vertikal (Progressive Reveal Flow dengan Alignment Presisi) --}}
                <div class="relative pl-7 sm:pl-8 space-y-3.5 before:content-[''] before:absolute before:left-3 sm:before:left-3.5 before:top-4 before:bottom-4 before:w-0.5 before:bg-neutral-300">
                    
                    {{-- Why 1: Penyebab Langsung (Wajib Selalu Tampil) --}}
                    <div class="relative">
                        <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full bg-brand border-2 border-white ring-2 ring-brand/30 shadow-2xs"></span>
                        <div class="bg-white p-3.5 sm:p-4 rounded-md border border-brand/40 shadow-2xs space-y-1.5 transition-all duration-150">
                            <div class="flex items-center gap-1.5">
                                <label for="why_1" class="block text-xs font-bold text-neutral-900 font-sans">
                                    Why 1 <span class="text-red-500" title="Wajib diisi">*</span>
                                </label>
                                <x-capa.field-hint field="why_1" />
                            </div>
                            <input type="text"
                                   id="why_1"
                                   @if ($readonly) value="{{ $values['why1'] }}" readonly @else wire:model.live="why1" @endif
                                   class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            <x-capa.field-helper field="why_1" />
                            @error('why1')
                                <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Why 2: Kondisi Fisik / Teknis --}}
                    <div x-show="visibleWhys >= 2 || {{ $readonly ? (filled($values['why2']) ? 'true' : 'false') : '$wire.why2' }}" x-cloak class="space-y-3.5">
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full border-2 border-white ring-1 transition-all duration-300"
                                  :class="{{ $readonly ? (filled($values['why2']) ? 'true' : 'false') : '$wire.why2' }} ? 'bg-emerald-500 ring-emerald-200' : 'bg-neutral-300 ring-neutral-200'"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border hover:border-neutral-300 space-y-1.5 transition-all duration-150 shadow-2xs"
                                 :class="{{ $readonly ? (filled($values['why2']) ? 'true' : 'false') : '$wire.why2' }} ? 'border-emerald-300 bg-emerald-50/20' : 'border-neutral-200'">
                                <label for="why_2" class="block text-xs font-medium text-neutral-800 font-sans">
                                    Why 2
                                </label>
                                <input type="text"
                                       id="why_2"
                                       @if ($readonly) value="{{ $values['why2'] }}" readonly @else wire:model.live="why2" @endif
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>

                    {{-- Why 3: Prosedur / Metode --}}
                    <div x-show="visibleWhys >= 3 || {{ $readonly ? (filled($values['why3']) ? 'true' : 'false') : '$wire.why3' }}" x-cloak class="space-y-3.5">
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full border-2 border-white ring-1 transition-all duration-300"
                                  :class="{{ $readonly ? (filled($values['why3']) ? 'true' : 'false') : '$wire.why3' }} ? 'bg-emerald-500 ring-emerald-200' : 'bg-neutral-300 ring-neutral-200'"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border hover:border-neutral-300 space-y-1.5 transition-all duration-150 shadow-2xs"
                                 :class="{{ $readonly ? (filled($values['why3']) ? 'true' : 'false') : '$wire.why3' }} ? 'border-emerald-300 bg-emerald-50/20' : 'border-neutral-200'">
                                <label for="why_3" class="block text-xs font-medium text-neutral-800 font-sans">
                                    Why 3
                                </label>
                                <input type="text"
                                       id="why_3"
                                       @if ($readonly) value="{{ $values['why3'] }}" readonly @else wire:model.live="why3" @endif
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>

                    {{-- Why 4: Pengawasan / Pemeliharaan --}}
                    <div x-show="visibleWhys >= 4 || {{ $readonly ? (filled($values['why4']) ? 'true' : 'false') : '$wire.why4' }}" x-cloak class="space-y-3.5">
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full border-2 border-white ring-1 transition-all duration-300"
                                  :class="{{ $readonly ? (filled($values['why4']) ? 'true' : 'false') : '$wire.why4' }} ? 'bg-emerald-500 ring-emerald-200' : 'bg-neutral-300 ring-neutral-200'"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border hover:border-neutral-300 space-y-1.5 transition-all duration-150 shadow-2xs"
                                 :class="{{ $readonly ? (filled($values['why4']) ? 'true' : 'false') : '$wire.why4' }} ? 'border-emerald-300 bg-emerald-50/20' : 'border-neutral-200'">
                                <label for="why_4" class="block text-xs font-medium text-neutral-800 font-sans">
                                    Why 4
                                </label>
                                <input type="text"
                                       id="why_4"
                                       @if ($readonly) value="{{ $values['why4'] }}" readonly @else wire:model.live="why4" @endif
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>

                    {{-- Why 5: Akar Masalah Fundamental --}}
                    <div x-show="visibleWhys >= 5 || {{ $readonly ? (filled($values['why5']) ? 'true' : 'false') : '$wire.why5' }}" x-cloak class="space-y-3.5">
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full border-2 border-white ring-1 transition-all duration-300"
                                  :class="{{ $readonly ? (filled($values['why5']) ? 'true' : 'false') : '$wire.why5' }} ? 'bg-emerald-500 ring-emerald-200' : 'bg-neutral-300 ring-neutral-200'"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border hover:border-neutral-300 space-y-1.5 transition-all duration-150 shadow-2xs"
                                 :class="{{ $readonly ? (filled($values['why5']) ? 'true' : 'false') : '$wire.why5' }} ? 'border-emerald-300 bg-emerald-50/20' : 'border-neutral-200'">
                                <label for="why_5" class="block text-xs font-medium text-neutral-800 font-sans">
                                    Why 5
                                </label>
                                <input type="text"
                                       id="why_5"
                                       @if ($readonly) value="{{ $values['why5'] }}" readonly @else wire:model.live="why5" @endif
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kontrol Penambahan / Pengurangan Tingkat Why (Progressive Disclosure) --}}
                <div class="flex items-center justify-between gap-3 pt-2 border-t border-neutral-200/80 flex-wrap">
                    @unless ($readonly)
                    <div class="flex items-center gap-2">
                        <button type="button"
                                x-show="visibleWhys < 5"
                                @click="visibleWhys = Math.min(5, visibleWhys + 1)"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans font-medium text-neutral-800 hover:bg-neutral-50 hover:border-neutral-400 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Tambah Tingkat Analisis Kausalitas (Why <span x-text="visibleWhys + 1"></span>)</span>
                        </button>

                        <button type="button"
                                x-show="visibleWhys > 1"
                                @click="if (visibleWhys === 5) $wire.why5 = ''; else if (visibleWhys === 4) $wire.why4 = ''; else if (visibleWhys === 3) $wire.why3 = ''; else if (visibleWhys === 2) $wire.why2 = ''; visibleWhys = Math.max(1, visibleWhys - 1);"
                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-red-700 hover:text-red-800 hover:bg-red-50 border border-transparent hover:border-red-200 rounded-md transition-colors duration-150 font-sans">
                            <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                            </svg>
                            <span>Hapus Why <span x-text="visibleWhys"></span></span>
                        </button>
                    </div>
                    @endunless
                </div>

                {{-- Kesimpulan Akar Masalah (Root Cause Synthesis) --}}
                <div class="pt-3 border-t border-neutral-200 bg-white p-4 sm:p-5 rounded-md border border-neutral-200 shadow-2xs space-y-2">
                    <div class="flex items-center gap-1.5">
                        <label for="kesimpulan_akar_masalah" class="block text-xs font-bold text-neutral-900 font-sans">
                            Kesimpulan Akar Masalah (Root Cause) <span class="text-red-500" title="Wajib diisi">*</span>
                        </label>
                        <x-capa.field-hint field="kesimpulan_akar_masalah" />
                    </div>
                    <textarea id="kesimpulan_akar_masalah"
                              @if ($readonly) readonly @else wire:model.live="kesimpulanAkarMasalah" @endif
                              rows="3"
                              class="w-full px-3 py-2.5 bg-neutral-50/50 border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 leading-relaxed">{{ $readonly ? $values['kesimpulanAkarMasalah'] : '' }}</textarea>
                    <x-capa.field-helper field="kesimpulan_akar_masalah" />
                    @error('kesimpulanAkarMasalah')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>
            </section>
