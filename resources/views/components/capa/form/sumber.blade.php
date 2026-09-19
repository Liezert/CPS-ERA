@props([
    'values' => [],
    'readonly' => false,
    'sumberOptions' => \App\Models\BaIncident::SUMBER_OPTIONS,
])
            {{-- BAGIAN 2: SUMBER KETIDAKSESUAIAN --}}
            <section class="space-y-4" aria-labelledby="section-sumber">
                <div class="pb-1.5 border-b border-neutral-200 flex flex-col sm:flex-row sm:items-center justify-between gap-1.5">
                    <div>
                        <h3 id="section-sumber" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                            2. Sumber Ketidaksesuaian <span class="text-red-500" title="Wajib diisi">*</span>
                        </h3>
                        <p class="text-xs text-neutral-600 mt-0.5 max-w-[68ch]">
                            Pilih salah satu kategori pemicu terjadinya ketidaksesuaian atau anomali operasional.
                        </p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge shrink-0 self-start sm:self-center">
                        Pilihan Tunggal
                    </span>
                </div>
                
                {{-- Grid Pilihan Radio Cards yang Seimbang --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                    @foreach($sumberOptions as $key => $label)
                        <label class="relative flex items-start gap-3 p-3.5 bg-white border rounded-md cursor-pointer select-none transition-all duration-200 ease-out {{ $values['sumberKetidaksesuaian'] === $key ? 'border-brand ring-1 ring-brand bg-brand-tint/25 text-neutral-900 shadow-xs' : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 hover:shadow-2xs text-neutral-800' }}">
                            <input type="radio"
                                   @if ($readonly) disabled @checked($values['sumberKetidaksesuaian'] === $key) @else wire:model.live="sumberKetidaksesuaian" @endif
                                   value="{{ $key }}"
                                   class="text-brand focus:ring-brand h-4 w-4 shrink-0 mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <span class="text-xs font-sans font-semibold leading-snug block truncate">
                                    {{ $label }}
                                </span>
                                <span class="text-[11px] text-neutral-500 block mt-0.5 leading-normal">
                                    @if($key === 'keluhan_pelanggan')
                                        Klaim atau keluhan langsung dari pelanggan internal/eksternal.
                                    @elseif($key === 'audit')
                                        Temuan audit mutu internal, audit sertifikasi, atau patroli manajemen.
                                    @elseif($key === 'laporan_ketidaksesuaian')
                                        Laporan anomali proses produksi rutin di lantai kerja.
                                    @elseif($key === 'pencapaian_sasaran_program')
                                        Penyimpangan sasaran mutu atau KPI operasional bulanan.
                                    @else
                                        Pemicu di luar 4 kategori di atas (wajib dijelaskan).
                                    @endif
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Input Kondisional Jika Opsi "Lain-lain" Dipilih --}}
                @if ($values['sumberKetidaksesuaian'] === 'lain_lain')
                    <div class="pt-1">
                        <label for="sumber_ketidaksesuaian_lainnya" class="block text-xs font-semibold text-neutral-800 font-sans mb-1.5">
                            Sebutkan Rincian Sumber Lainnya <span class="text-red-500" title="Wajib diisi">*</span>
                        </label>
                        <input type="text"
                               id="sumber_ketidaksesuaian_lainnya"
                               @if ($readonly) value="{{ $values['sumberKetidaksesuaianLainnya'] }}" readonly @else wire:model.live="sumberKetidaksesuaianLainnya" @endif
                               placeholder="Contoh: Temuan inspeksi patroli K3 harian / Laporan audit vendor material"
                               class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                        @error('sumberKetidaksesuaianLainnya')
                            <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </section>
