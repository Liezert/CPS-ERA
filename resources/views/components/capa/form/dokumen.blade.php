@props([
    'values' => [],
    'readonly' => false,
    'divisions' => collect(),
])
            {{-- BAGIAN 1: INFORMASI DOKUMEN & UNIT KERJA --}}
            <section class="space-y-4" aria-labelledby="section-doc-info">
                <div class="pb-1.5 border-b border-neutral-200 flex items-center justify-between">
                    <h3 id="section-doc-info" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                        1. Informasi Dokumen &amp; Unit Kerja
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                        Data Identifikasi
                    </span>
                </div>

                {{-- Grid 3 Kolom Konsisten dengan Tinggi Input & Label Selaras --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4.5 items-start">
                    {{-- Kolom 1: Nomor BA / FTK (Read-only, IBM Plex Mono) --}}
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex items-center justify-between h-5">
                            <label for="nomor_ba" class="block text-xs font-semibold text-neutral-800 font-sans truncate">
                                No. FTK / Register BA
                            </label>
                        </div>
                        <div class="relative">
                            <input type="text"
                                   id="nomor_ba"
                                   value="{{ $values['nomorBaPreview'] }}"
                                   readonly
                                   disabled
                                   class="w-full h-10 pl-3 pr-8 py-2 bg-neutral-100/90 border border-neutral-200 rounded-md text-xs font-mono font-bold text-neutral-800 cursor-not-allowed select-all truncate" />
                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-neutral-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Kolom 2: Tanggal Pengisian --}}
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex items-center justify-between h-5">
                            <label for="tanggal_pengisian" class="block text-xs font-semibold text-neutral-800 font-sans truncate">
                                Tanggal Pengisian <span class="text-red-500" title="Wajib diisi">*</span>
                            </label>
                        </div>
                        <input type="date"
                               id="tanggal_pengisian"
                               @if ($readonly) value="{{ $values['tanggalPengisian'] }}" readonly @else wire:model.live="tanggalPengisian" @endif
                               class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                        @error('tanggalPengisian')
                            <span class="text-xs text-red-600 mt-0.5 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Kolom 3: Bagian / Divisi --}}
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex items-center justify-between h-5">
                            <label for="division_id" class="block text-xs font-semibold text-neutral-800 font-sans truncate">
                                Bagian / Divisi <span class="text-red-500" title="Wajib diisi">*</span>
                            </label>
                        </div>
                        <select id="division_id"
                                @if ($readonly) disabled @else wire:model.live="divisionId" @endif
                                class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 truncate cursor-pointer">
                            <option value="">-- Pilih Divisi (12 Opsi Resmi) --</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}" @selected($readonly && (string) $values['divisionId'] === (string) $div->id)>{{ $div->name }}</option>
                            @endforeach
                        </select>
                        @error('divisionId')
                            <span class="text-xs text-red-600 mt-0.5 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </section>
