@props([
    'values' => [],
    'readonly' => false,
])
            {{-- BAGIAN 5: RENCANA PENANGANAN DENGAN PEMBAGIAN TANGGUNG JAWAB PIC TEGAS --}}
            <section class="space-y-4" aria-labelledby="section-action-plans">
                <div class="pb-1.5 border-b border-neutral-200">
                    <h3 id="section-action-plans" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                        5. Rencana Penanganan: Tindakan Koreksi (Sementara) vs Tindakan Korektif (Akar Masalah)
                    </h3>
                </div>

                {{-- Banner Tautan Rujukan Kausalitas Live --}}
                <div class="p-3.5 bg-brand-tint/35 border border-brand/25 rounded-md flex items-start gap-2.5 text-xs text-brand-dark shadow-2xs">
                    <svg class="w-4 h-4 text-brand shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                    <div class="min-w-0 flex-1">
                        <span class="font-bold block text-neutral-900">Tautan Rujukan Sasaran Tindakan Korektif:</span>
                        <span class="italic text-neutral-700 block mt-0.5 line-clamp-2"
                              @if ($readonly)>{{ $values['kesimpulanAkarMasalah'] ?: '(Menunggu pengisian Kesimpulan Akar Masalah pada Bagian 4)' }}</span>@else x-text="$wire.kesimpulanAkarMasalah ? $wire.kesimpulanAkarMasalah : '(Menunggu pengisian Kesimpulan Akar Masalah pada Bagian 4)'"></span>@endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {{-- Kartu A: Tindakan Koreksi Sementara (Containment) - Mandat Operator & SPV --}}
                    <div class="border border-amber-300/80 bg-amber-50/25 rounded-md p-4 sm:p-5 space-y-4 shadow-2xs flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between pb-2.5 border-b border-amber-200/80 flex-wrap gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                    <h4 class="text-xs font-bold text-neutral-900 font-sans tracking-tight">
                                        Tindakan Koreksi (Sementara)
                                    </h4>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium text-amber-900 bg-amber-100 border border-amber-300 rounded-badge">
                                    Containment &middot; Mandat Operator/SPV
                                </span>
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Tindakan darurat seketika di tempat untuk melokalisir dampak dan menghentikan meluasnya kerusakan (misal: mematikan mesin, isolasi batch produk).
                            </p>

                            <div>
                                <label for="koreksi_deskripsi" class="block text-xs font-semibold text-neutral-800 font-sans mb-1.5">
                                    Deskripsi Tindakan Koreksi <span class="text-red-500" title="Wajib diisi">*</span>
                                </label>
                                <textarea id="koreksi_deskripsi"
                                          @if ($readonly) readonly @else wire:model.live="koreksiDeskripsi" @endif
                                          rows="3"
                                          placeholder="Contoh: Mematikan mesin injeksi, mengisolasi batch produksi 2 jam terakhir, dan memasang label peringatan status HOLD..."
                                          class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-150 leading-relaxed">{{ $readonly ? $values['koreksiDeskripsi'] : '' }}</textarea>
                                @error('koreksiDeskripsi')
                                    <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Blok PIC & Target Waktu Pelaksanaan --}}
                        <div class="bg-white/80 border border-amber-200/80 rounded-md p-3 space-y-2.5">
                            <span class="text-[11px] font-bold text-amber-900 block uppercase tracking-wider font-mono">
                                Penanggung Jawab &amp; Target Darurat:
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="koreksi_pic" class="block text-xs font-medium text-neutral-700 font-sans mb-1 truncate">
                                        PIC Pelaksana (Operator/SPV)
                                    </label>
                                    <input type="text"
                                           id="koreksi_pic"
                                           @if ($readonly) value="{{ $values['koreksiPic'] }}" readonly @else wire:model.live="koreksiPic" @endif
                                           placeholder="Nama PIC"
                                           class="w-full h-9 px-2.5 py-1.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-150" />
                                </div>
                                <div>
                                    <label for="koreksi_waktu" class="block text-xs font-medium text-neutral-700 font-sans mb-1 truncate">
                                        Batas Waktu Pelaksanaan
                                    </label>
                                    <input type="text"
                                           id="koreksi_waktu"
                                           @if ($readonly) value="{{ $values['koreksiWaktu'] }}" readonly @else wire:model.live="koreksiWaktu" @endif
                                           placeholder="Contoh: Maks 1 Jam"
                                           class="w-full h-9 px-2.5 py-1.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-150" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Kartu B: Tindakan Korektif Permanen (Corrective Action) - Mandat Engineering/QC --}}
                    <div class="border border-brand/35 bg-brand-tint/20 rounded-md p-4 sm:p-5 space-y-4 shadow-2xs flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between pb-2.5 border-b border-brand/25 flex-wrap gap-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-brand shrink-0"></span>
                                    <h4 class="text-xs font-bold text-neutral-900 font-sans tracking-tight">
                                        Tindakan Korektif (Akar Masalah)
                                    </h4>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                                    Corrective &middot; Mandat Engineering/QC
                                </span>
                            </div>
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Tindakan perbaikan permanen untuk memodifikasi sistem/SOP sehingga akar masalah hilang dan ketidaksesuaian tidak terulang kembali.
                            </p>

                            <div>
                                <label for="korektif_deskripsi" class="block text-xs font-semibold text-neutral-800 font-sans mb-1.5">
                                    Deskripsi Tindakan Korektif <span class="text-red-500" title="Wajib diisi">*</span>
                                </label>
                                <textarea id="korektif_deskripsi"
                                          @if ($readonly) readonly @else wire:model.live="korektifDeskripsi" @endif
                                          rows="3"
                                          placeholder="Contoh: Merevisi lembar checklist serah terima shift harian, memasang saringan oli berkatup bypass otomatis, dan briefing SOP..."
                                          class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 leading-relaxed">{{ $readonly ? $values['korektifDeskripsi'] : '' }}</textarea>
                                @error('korektifDeskripsi')
                                    <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Blok PIC & Target Waktu Pelaksanaan --}}
                        <div class="bg-white/80 border border-brand/25 rounded-md p-3 space-y-2.5">
                            <span class="text-[11px] font-bold text-brand-dark block uppercase tracking-wider font-mono">
                                Penanggung Jawab &amp; Target Perbaikan:
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label for="korektif_pic" class="block text-xs font-medium text-neutral-700 font-sans mb-1 truncate">
                                        PIC Penanggung Jawab
                                    </label>
                                    <input type="text"
                                           id="korektif_pic"
                                           @if ($readonly) value="{{ $values['korektifPic'] }}" readonly @else wire:model.live="korektifPic" @endif
                                           placeholder="Nama PIC (QC/Eng)"
                                           class="w-full h-9 px-2.5 py-1.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                                </div>
                                <div>
                                    <label for="korektif_waktu" class="block text-xs font-medium text-neutral-700 font-sans mb-1 truncate">
                                        Target Tanggal Selesai
                                    </label>
                                    <input type="text"
                                           id="korektif_waktu"
                                           @if ($readonly) value="{{ $values['korektifWaktu'] }}" readonly @else wire:model.live="korektifWaktu" @endif
                                           placeholder="Contoh: Maks 3 Hari"
                                           class="w-full h-9 px-2.5 py-1.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
