@props([
    'values' => [],
    'readonly' => false,
])
            {{-- BAGIAN 3: INFORMASI KEJADIAN & URAIAN MASALAH --}}
            <section class="space-y-4" aria-labelledby="section-incident-detail">
                <div class="pb-1.5 border-b border-neutral-200 flex items-center justify-between">
                    <h3 id="section-incident-detail" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                        3. Informasi Kejadian &amp; Rincian Masalah
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                        Fakta Lapangan
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4.5">
                    {{-- Tanggal Masalah --}}
                    <div class="space-y-1.5 min-w-0">
                        <label for="tanggal_masalah" class="block text-xs font-semibold text-neutral-800 font-sans truncate">
                            Tanggal Kejadian Masalah <span class="text-red-500" title="Wajib diisi">*</span>
                        </label>
                        <input type="date"
                               id="tanggal_masalah"
                               @if ($readonly) value="{{ $values['tanggalMasalah'] }}" readonly @else wire:model.live="tanggalMasalah" @endif
                               class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                        <p class="text-[11px] text-neutral-500">Waktu saat ketidaksesuaian terdeteksi.</p>
                        @error('tanggalMasalah')
                            <span class="text-xs text-red-600 mt-0.5 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Lokasi / Tempat Kejadian --}}
                    <div class="space-y-1.5 min-w-0">
                        <label for="lokasi" class="block text-xs font-semibold text-neutral-800 font-sans truncate">
                            Lokasi / Tempat Kejadian <span class="text-red-500" title="Wajib diisi">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                   id="lokasi"
                                   @if ($readonly) value="{{ $values['lokasi'] }}" readonly @else wire:model.live="lokasi" @endif
                                   placeholder="Contoh: Lini Injeksi Nozzle 02, Area Gedung B"
                                   class="w-full h-10 pl-3 pr-8 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 truncate" />
                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-neutral-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-[11px] text-neutral-500">Sebutkan nama lini, nomor mesin, atau area fisik.</p>
                        @error('lokasi')
                            <span class="text-xs text-red-600 mt-0.5 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                {{-- Uraian Masalah --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between mb-0.5">
                        <label for="deskripsi_masalah" class="block text-xs font-semibold text-neutral-800 font-sans">
                            Uraian Masalah / Ketidaksesuaian <span class="text-red-500" title="Wajib diisi">*</span>
                        </label>
                        <span class="text-xs text-neutral-500 font-sans">Jelaskan fakta 5W+1H secara spesifik</span>
                    </div>
                    <textarea id="deskripsi_masalah"
                              @if ($readonly) readonly @else wire:model.live="deskripsiMasalah" @endif
                              rows="3"
                              placeholder="Uraikan fakta spesifik kejadian: nama komponen/proses, nilai parameter yang menyimpang dari standar spesifikasi, serta dampak langsung terhadap lini produksi atau keselamatan..."
                              class="w-full px-3 py-2.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 leading-relaxed">{{ $readonly ? $values['deskripsiMasalah'] : '' }}</textarea>
                    @error('deskripsiMasalah')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>
            </section>
