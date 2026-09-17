<div x-data="{
        visibleWhys: {{ max(1, (!empty($why5) ? 5 : (!empty($why4) ? 4 : (!empty($why3) ? 3 : (!empty($why2) ? 2 : 1))))) }},
        copiedBa: false,
        copyBaNumber() {
            navigator.clipboard.writeText('{{ $nomorBaPreview }}');
            this.copiedBa = true;
            setTimeout(() => { this.copiedBa = false; }, 2000);
        },
        scrollToFirstError() {
            this.$nextTick(() => {
                const firstError = document.querySelector('.text-red-600, [aria-invalid=\'true\']');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const focusable = firstError.closest('div')?.querySelector('input, select, textarea');
                    if (focusable) focusable.focus();
                }
            });
        }
    }"
    class="max-w-4xl mx-auto space-y-6 pb-12">

    {{-- =========================================================================
         1. CORPORATE DOCUMENT HEADER & METADATA BAR
         - Standar dokumen resmi kendali mutu PT Catur Pilar Sejahtera
         ========================================================================= --}}
    <header class="bg-white border border-neutral-200 rounded-md p-5 sm:p-6 shadow-2xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-2 min-w-0 flex-1">
                {{-- Breadcrumbs Navigasi Kontekstual --}}
                <nav class="flex items-center gap-2 text-xs font-sans text-neutral-500 font-medium" aria-label="Breadcrumb">
                    <a href="{{ route('ba.index') }}" class="hover:text-neutral-900 transition-colors duration-150 truncate">
                        Laporan CAPA
                    </a>
                    <svg class="w-3 h-3 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                    <span class="text-neutral-900 font-semibold truncate">
                        Formulir CAPA / FTK
                    </span>
                </nav>

                {{-- Judul Halaman --}}
                <div class="space-y-1.5 pt-0.5">
                    <h1 class="font-sans font-bold text-xl sm:text-2xl text-neutral-900 tracking-tight">
                        {{ $baIncidentId ? 'Revisi Formulir Tindakan Korektif (CAPA / FTK)' : 'Pelaporan Tindakan Korektif (CAPA)' }}
                    </h1>
                </div>

                <p class="font-sans text-xs sm:text-sm text-neutral-600 leading-relaxed max-w-[68ch]">
                    Formulir digital resmi penyelidikan ketidaksesuaian operasional, analisis 5 Whys, dan penetapan tindakan koreksi sementara maupun tindakan perbaikan permanen.
                </p>
            </div>

            {{-- Action Tombol Batal --}}
            <div class="flex items-center gap-2 shrink-0 self-start sm:self-center">
                <a href="{{ route('ba.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 hover:text-neutral-900 hover:border-neutral-300 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-neutral-900/15 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Batal</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Alert Notifikasi Sukses / Draf Tersimpan --}}
    @if (session()->has('success'))
        <div class="p-4 bg-brand-tint/60 border border-brand/30 rounded-md flex items-center justify-between gap-3 text-xs font-sans text-brand-dark shadow-2xs" role="status">
            <div class="flex items-center gap-2.5 min-w-0">
                <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span class="font-medium truncate">{{ session('success') }}</span>
            </div>
            <span class="text-[10px] font-mono text-brand font-semibold shrink-0">Tersimpan di Sistem</span>
        </div>
    @endif

    {{-- =========================================================================
         2. CONNECTED STEPPER PROGRESS PIPELINE (LANGKAH 1 & LANGKAH 2)
         - Navigasi dua arah dengan visual state yang dinamis & responsif
         ========================================================================= --}}
    <nav class="bg-white border border-neutral-200 rounded-md p-4 sm:p-5 shadow-2xs" aria-label="Alur Progres Formulir">
        {{-- Progress Bar Keseluruhan (Tinggi 2px) --}}
        <div class="w-full bg-neutral-100 rounded-full h-1.5 mb-4 overflow-hidden">
            <div class="bg-brand h-1.5 rounded-full transition-all duration-300 ease-out {{ $step === 1 ? 'w-1/2' : 'w-full' }}"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 items-center">
            {{-- Stepper Langkah 1: Klik untuk kembali jika sedang di Step 2 --}}
            <button type="button"
                    @if($step === 2) wire:click="previousStep" @endif
                    class="w-full text-left flex items-center gap-3.5 p-3 rounded-md border transition-all duration-200 ease-out {{ $step === 1 ? 'border-brand/40 bg-brand-tint/25 shadow-2xs cursor-default' : 'border-neutral-200/80 bg-neutral-50/50 hover:border-neutral-300 hover:bg-white hover:shadow-xs cursor-pointer active:scale-[0.99]' }}">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-mono text-xs font-bold shrink-0 transition-all duration-200 {{ $step === 1 ? 'bg-brand text-white ring-4 ring-brand-tint' : 'bg-brand-tint text-brand-dark border border-brand/40' }}">
                    @if($step > 1)
                        <svg class="w-4 h-4 text-brand-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    @else
                        1
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold font-sans {{ $step === 1 ? 'text-neutral-900' : 'text-neutral-700' }}">
                            Langkah 1: Formulir CAPA
                        </span>
                        @if($step === 1)
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                                Sedang Diisi
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                                Draf Tersimpan (Klik untuk Edit)
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-neutral-600 truncate mt-0.5">
                        Identifikasi ketidaksesuaian &amp; investigasi 5 Whys
                    </p>
                </div>
            </button>

            {{-- Stepper Langkah 2 --}}
            <div class="flex items-center gap-3.5 p-3 rounded-md border transition-all duration-200 {{ $step === 2 ? 'border-brand/40 bg-brand-tint/25 shadow-2xs' : 'border-neutral-200/80 bg-neutral-50/50' }}">
                <div class="w-9 h-9 rounded-full flex items-center justify-center font-mono text-xs font-bold shrink-0 transition-all duration-200 {{ $step === 2 ? 'bg-brand text-white ring-4 ring-brand-tint' : 'bg-neutral-100 text-neutral-500 border border-neutral-300' }}">
                    2
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold font-sans {{ $step === 2 ? 'text-neutral-900' : 'text-neutral-600' }}">
                            Langkah 2: Video Penanganan
                        </span>
                        @if($step === 2)
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                                Wajib Diisi
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                                Tahap Berikutnya
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-neutral-600 truncate mt-0.5">
                        Unggah berkas video (maks 100MB) atau tautan eksternal
                    </p>
                </div>
            </div>
        </div>
    </nav>

    {{-- =========================================================================
         3. BANNER RINGKASAN PESAN VALIDASI ERROR
         ========================================================================= --}}
    @if ($errors->any())
        <div id="error-summary-banner" class="p-4 bg-red-50 border border-red-200 rounded-md flex items-start gap-3 shadow-2xs" role="alert">
            <div class="w-5 h-5 text-red-600 shrink-0 mt-0.5">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <div class="space-y-1 text-xs font-sans">
                <p class="font-bold text-red-900">
                    Mohon lengkapi atau perbaiki beberapa data berikut sebelum melanjutkan:
                </p>
                <ul class="list-disc list-inside space-y-0.5 text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         4. LANGKAH 1: FORMULIR INVESTIGASI CAPA / FTK
         ========================================================================= --}}
    @if ($step === 1)
        <div class="bg-white border border-neutral-200 rounded-md p-5 sm:p-7 space-y-8 shadow-2xs">
            
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
                        {{ $nomorBaPreview }}
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
                            <span class="text-[10px] font-mono text-neutral-600 bg-neutral-100 border border-neutral-200 px-1.5 py-0.2 rounded-badge shrink-0">
                                Otomatis
                            </span>
                        </div>
                        <div class="relative">
                            <input type="text"
                                   id="nomor_ba"
                                   value="{{ $nomorBaPreview }}"
                                   readonly
                                   disabled
                                   class="w-full h-10 pl-3 pr-8 py-2 bg-neutral-100/90 border border-neutral-200 rounded-md text-xs font-mono font-bold text-neutral-800 cursor-not-allowed select-all truncate" />
                            <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-neutral-500">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                            </div>
                        </div>
                        <p class="text-[11px] text-neutral-500 leading-tight">Terbit otomatis berurutan tahunan.</p>
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
                               wire:model.live="tanggalPengisian"
                               class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                        <p class="text-[11px] text-neutral-500 leading-tight">Tanggal resmi pencatatan berkas.</p>
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
                                wire:model.live="divisionId"
                                class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 truncate cursor-pointer">
                            <option value="">-- Pilih Divisi (13 Opsi Resmi) --</option>
                            @foreach($divisions as $div)
                                <option value="{{ $div->id }}">{{ $div->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-neutral-500 leading-tight">Divisi penanggung jawab area kejadian.</p>
                        @error('divisionId')
                            <span class="text-xs text-red-600 mt-0.5 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </section>

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
                        <label class="relative flex items-start gap-3 p-3.5 bg-white border rounded-md cursor-pointer select-none transition-all duration-200 ease-out {{ $sumberKetidaksesuaian === $key ? 'border-brand ring-1 ring-brand bg-brand-tint/25 text-neutral-900 shadow-xs' : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 hover:shadow-2xs text-neutral-800' }}">
                            <input type="radio"
                                   wire:model.live="sumberKetidaksesuaian"
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
                @if ($sumberKetidaksesuaian === 'lain_lain')
                    <div class="pt-1">
                        <label for="sumber_ketidaksesuaian_lainnya" class="block text-xs font-semibold text-neutral-800 font-sans mb-1.5">
                            Sebutkan Rincian Sumber Lainnya <span class="text-red-500" title="Wajib diisi">*</span>
                        </label>
                        <input type="text"
                               id="sumber_ketidaksesuaian_lainnya"
                               wire:model.live="sumberKetidaksesuaianLainnya"
                               placeholder="Contoh: Temuan inspeksi patroli K3 harian / Laporan audit vendor material"
                               class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                        @error('sumberKetidaksesuaianLainnya')
                            <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </section>

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
                               wire:model.live="tanggalMasalah"
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
                                   wire:model.live="lokasi"
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
                              wire:model.live="deskripsiMasalah"
                              rows="3"
                              placeholder="Uraikan fakta spesifik kejadian: nama komponen/proses, nilai parameter yang menyimpang dari standar spesifikasi, serta dampak langsung terhadap lini produksi atau keselamatan..."
                              class="w-full px-3 py-2.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 leading-relaxed"></textarea>
                    @error('deskripsiMasalah')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>
            </section>

            {{-- BAGIAN 4: ANALISIS AKAR MASALAH (TANGGA KAUSALITAS 5 WHYS DENGAN PROGRESSIVE DISCLOSURE) --}}
            <section class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 sm:p-6 space-y-5" aria-labelledby="section-5whys">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-neutral-200 gap-2">
                    <div>
                        <h3 id="section-5whys" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                            4. Analisis Akar Masalah (5 Whys Causality Ladder)
                        </h3>
                        <p class="text-xs text-neutral-600 mt-0.5">
                            Metode bertahap menggali akar penyebab. Why 1 wajib diisi, tambahkan tingkatan berikutnya sesuai kedalaman investigasi.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[11px] font-mono font-medium text-neutral-800 bg-white border border-neutral-300 rounded-badge uppercase tracking-wider shadow-2xs shrink-0 self-start sm:self-center">
                        Kaizen RCA
                    </span>
                </div>

                {{-- Tangga Kausalitas Vertikal (Progressive Reveal Flow dengan Alignment Presisi) --}}
                <div class="relative pl-7 sm:pl-8 space-y-3.5 before:content-[''] before:absolute before:left-3 sm:before:left-3.5 before:top-4 before:bottom-4 before:w-0.5 before:bg-neutral-300">
                    
                    {{-- Why 1: Penyebab Langsung (Wajib Selalu Tampil) --}}
                    <div class="relative">
                        <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full bg-brand border-2 border-white ring-2 ring-brand/30 shadow-2xs"></span>
                        <div class="bg-white p-3.5 sm:p-4 rounded-md border border-brand/40 shadow-2xs space-y-1.5 transition-all duration-150">
                            <div class="flex items-center justify-between flex-wrap gap-1">
                                <label for="why_1" class="block text-xs font-bold text-neutral-900 font-sans">
                                    Why 1 &mdash; Mengapa anomali atau gejala awal terjadi? <span class="text-red-500" title="Wajib diisi">*</span>
                                </label>
                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                                    Penyebab Langsung (Direct Symptom)
                                </span>
                            </div>
                            <input type="text"
                                   id="why_1"
                                   wire:model.live="why1"
                                   placeholder="Contoh: Temperatur nozzle cetak naik melampaui batas aman toleransi 240°C"
                                   class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            @error('why1')
                                <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    {{-- Why 2: Kondisi Fisik / Teknis --}}
                    <div x-show="visibleWhys >= 2 || $wire.why2" x-cloak class="space-y-3.5">
                        <div class="text-[11px] font-mono text-neutral-500 pl-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                            </svg>
                            <span>Mengapa hal pada Why 1 bisa terjadi?</span>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full bg-neutral-300 border-2 border-white ring-1 ring-neutral-200"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border border-neutral-200 hover:border-neutral-300 space-y-1.5 transition-colors duration-150 shadow-2xs">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <label for="why_2" class="block text-xs font-medium text-neutral-800 font-sans">
                                        Why 2 &mdash; Mengapa terjadi kondisi pada Why 1?
                                    </label>
                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                                        Tahap 2: Kondisi Mesin/Fisik
                                    </span>
                                </div>
                                <input type="text"
                                       id="why_2"
                                       wire:model.live="why2"
                                       placeholder="Contoh: Pendingin oli sirkulasi tidak mengalirkan fluida ke blok cetakan"
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>

                    {{-- Why 3: Prosedur / Metode --}}
                    <div x-show="visibleWhys >= 3 || $wire.why3" x-cloak class="space-y-3.5">
                        <div class="text-[11px] font-mono text-neutral-500 pl-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                            </svg>
                            <span>Mengapa hal pada Why 2 bisa terjadi?</span>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full bg-neutral-300 border-2 border-white ring-1 ring-neutral-200"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border border-neutral-200 hover:border-neutral-300 space-y-1.5 transition-colors duration-150 shadow-2xs">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <label for="why_3" class="block text-xs font-medium text-neutral-800 font-sans">
                                        Why 3 &mdash; Mengapa terjadi kondisi pada Why 2?
                                    </label>
                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                                        Tahap 3: Metode / Prosedur Kerja
                                    </span>
                                </div>
                                <input type="text"
                                       id="why_3"
                                       wire:model.live="why3"
                                       placeholder="Contoh: Katup solenoid otomatis tersumbat endapan kerak oli yang mengering"
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>

                    {{-- Why 4: Pengawasan / Pemeliharaan --}}
                    <div x-show="visibleWhys >= 4 || $wire.why4" x-cloak class="space-y-3.5">
                        <div class="text-[11px] font-mono text-neutral-500 pl-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                            </svg>
                            <span>Mengapa hal pada Why 3 bisa terjadi?</span>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full bg-neutral-300 border-2 border-white ring-1 ring-neutral-200"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border border-neutral-200 hover:border-neutral-300 space-y-1.5 transition-colors duration-150 shadow-2xs">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <label for="why_4" class="block text-xs font-medium text-neutral-800 font-sans">
                                        Why 4 &mdash; Mengapa terjadi kondisi pada Why 3?
                                    </label>
                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                                        Tahap 4: Sistem Pengawasan/PM
                                    </span>
                                </div>
                                <input type="text"
                                       id="why_4"
                                       wire:model.live="why4"
                                       placeholder="Contoh: Jadwal pembersihan berkala saringan oli terlewat saat rotasi teknisi shift malam"
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>

                    {{-- Why 5: Akar Masalah Fundamental --}}
                    <div x-show="visibleWhys >= 5 || $wire.why5" x-cloak class="space-y-3.5">
                        <div class="text-[11px] font-mono text-neutral-500 pl-1 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                            </svg>
                            <span>Mengapa hal pada Why 4 bisa terjadi?</span>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-7 sm:-left-8 top-3 w-4 h-4 rounded-full bg-neutral-300 border-2 border-white ring-1 ring-neutral-200"></span>
                            <div class="bg-white p-3.5 sm:p-4 rounded-md border border-neutral-200 hover:border-neutral-300 space-y-1.5 transition-colors duration-150 shadow-2xs">
                                <div class="flex items-center justify-between flex-wrap gap-1">
                                    <label for="why_5" class="block text-xs font-medium text-neutral-800 font-sans">
                                        Why 5 &mdash; Mengapa terjadi kondisi pada Why 4?
                                    </label>
                                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                                        Tahap 5: Kebijakan / Akar Fundamental
                                    </span>
                                </div>
                                <input type="text"
                                       id="why_5"
                                       wire:model.live="why5"
                                       placeholder="Contoh: Lembar logbook serah terima shift belum mewajibkan verifikasi checklist sirkulasi pendingin"
                                       class="w-full h-10 px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Kontrol Penambahan / Pengurangan Tingkat Why (Progressive Disclosure) --}}
                <div class="flex items-center justify-between gap-3 pt-2 border-t border-neutral-200/80 flex-wrap">
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

                    <p class="text-[11px] text-neutral-500 font-sans italic">
                        Tip Kaizen: Gali hingga akar terdalam yang dapat dicegah agar masalah tidak berulang.
                    </p>
                </div>

                {{-- Kesimpulan Akar Masalah (Root Cause Synthesis) --}}
                <div class="pt-3 border-t border-neutral-200 bg-white p-4 sm:p-5 rounded-md border border-neutral-200 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between flex-wrap gap-1">
                        <label for="kesimpulan_akar_masalah" class="block text-xs font-bold text-neutral-900 font-sans">
                            Kesimpulan Akar Masalah (Root Cause) <span class="text-red-500" title="Wajib diisi">*</span>
                        </label>
                        <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge uppercase">
                            Fokus Perbaikan Utama
                        </span>
                    </div>
                    <p class="text-xs text-neutral-600 leading-normal">
                        Tuliskan sintesis inti penyebab paling mendasar dari hasil penelusuran kausalitas 5 Whys di atas yang harus diselesaikan oleh tindakan korektif.
                    </p>
                    <textarea id="kesimpulan_akar_masalah"
                              wire:model.live="kesimpulanAkarMasalah"
                              rows="2"
                              placeholder="Contoh: Ketiadaan instruksi kerja terintegrasi dalam logbook shift untuk pembersihan berkala saringan solenoid oli pendingin..."
                              class="w-full px-3 py-2.5 bg-neutral-50/50 border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 leading-relaxed"></textarea>
                    @error('kesimpulanAkarMasalah')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>
            </section>

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
                              x-text="$wire.kesimpulanAkarMasalah ? $wire.kesimpulanAkarMasalah : '(Menunggu pengisian Kesimpulan Akar Masalah pada Bagian 4)'"></span>
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
                                          wire:model.live="koreksiDeskripsi"
                                          rows="3"
                                          placeholder="Contoh: Mematikan mesin injeksi, mengisolasi batch produksi 2 jam terakhir, dan memasang label peringatan status HOLD..."
                                          class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-150 leading-relaxed"></textarea>
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
                                           wire:model.live="koreksiPic"
                                           placeholder="Nama PIC"
                                           class="w-full h-9 px-2.5 py-1.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-150" />
                                </div>
                                <div>
                                    <label for="koreksi_waktu" class="block text-xs font-medium text-neutral-700 font-sans mb-1 truncate">
                                        Batas Waktu Pelaksanaan
                                    </label>
                                    <input type="text"
                                           id="koreksi_waktu"
                                           wire:model.live="koreksiWaktu"
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
                                          wire:model.live="korektifDeskripsi"
                                          rows="3"
                                          placeholder="Contoh: Merevisi lembar checklist serah terima shift harian, memasang saringan oli berkatup bypass otomatis, dan briefing SOP..."
                                          class="w-full px-3 py-2 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150 leading-relaxed"></textarea>
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
                                           wire:model.live="korektifPic"
                                           placeholder="Nama PIC (QC/Eng)"
                                           class="w-full h-9 px-2.5 py-1.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                                </div>
                                <div>
                                    <label for="korektif_waktu" class="block text-xs font-medium text-neutral-700 font-sans mb-1 truncate">
                                        Target Tanggal Selesai
                                    </label>
                                    <input type="text"
                                           id="korektif_waktu"
                                           wire:model.live="korektifWaktu"
                                           placeholder="Contoh: Maks 3 Hari"
                                           class="w-full h-9 px-2.5 py-1.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- BAGIAN 6: IDENTIFIKASI DAMPAK LANJUTAN & POTENSI --}}
            <section class="space-y-4" aria-labelledby="section-dampak">
                <div class="pb-1.5 border-b border-neutral-200 flex items-center justify-between">
                    <h3 id="section-dampak" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                        6. Identifikasi Dampak Lanjutan &amp; Potensi
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                        Manajemen Risiko
                    </span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Kartu Potensi Risiko --}}
                    <label class="flex items-start gap-3 p-3.5 bg-white border rounded-md cursor-pointer select-none transition-all duration-200 ease-out {{ $isPotensiRisiko ? 'border-amber-400 ring-1 ring-amber-400 bg-amber-50/30 shadow-xs' : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 hover:shadow-2xs' }}">
                        <input type="checkbox"
                               wire:model.live="isPotensiRisiko"
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
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Insiden ini berpotensi memicu bahaya keselamatan kerja (K3), komplain kualitas pelanggan fatal, atau kerugian biaya mesin besar jika dibiarkan.
                            </p>
                        </div>
                    </label>

                    {{-- Kartu Potensi Peluang --}}
                    <label class="flex items-start gap-3 p-3.5 bg-white border rounded-md cursor-pointer select-none transition-all duration-200 ease-out {{ $isPotensiPeluang ? 'border-brand ring-1 ring-brand bg-brand-tint/25 shadow-xs' : 'border-neutral-200 hover:border-neutral-300 hover:bg-neutral-50 hover:shadow-2xs' }}">
                        <input type="checkbox"
                               wire:model.live="isPotensiPeluang"
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
                            <p class="text-xs text-neutral-600 leading-relaxed">
                                Temuan ini memberikan dasar berharga untuk Kaizen, standardisasi SOP baru antar-divisi, atau efisiensi pemeliharaan preventif masa depan.
                            </p>
                        </div>
                    </label>
                </div>
            </section>

            {{-- ACTION BAR STEP 1 DENGAN RESPONSIF TINGGI --}}
            <div class="pt-5 border-t border-neutral-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-neutral-600 font-sans justify-center sm:justify-start">
                    <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Langkah 1 dari 2: Data tersimpan aman di database saat Anda melanjutkan.</span>
                </div>

                {{-- Cluster Tombol Aksi: Stack di Mobile, Horizontal di Desktop --}}
                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
                    {{-- Tombol Batal --}}
                    <a href="{{ route('ba.index') }}"
                       class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 hover:text-neutral-900 hover:border-neutral-300 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-neutral-900/15 shadow-2xs">
                        Batal
                    </a>

                    {{-- Tombol Simpan Draf Saja --}}
                    <button type="button"
                            wire:click="saveDraftOnly"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 border border-neutral-300 rounded-md text-xs font-sans font-medium text-neutral-800 bg-white hover:bg-neutral-50 hover:border-neutral-400 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-brand/20 shadow-2xs disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                        </svg>
                        <span wire:loading.remove wire:target="saveDraftOnly">Simpan Draf Saja</span>
                        <span wire:loading wire:target="saveDraftOnly">Menyimpan...</span>
                    </button>

                    {{-- Tombol Lanjut ke Langkah 2 --}}
                    <button type="button"
                            wire:click="nextStep"
                            @click="scrollToFirstError()"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand text-white font-sans font-medium text-xs rounded-md hover:bg-brand-dark hover:shadow-sm active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="nextStep" class="flex items-center gap-1.5">
                            <span>Lanjut ke Langkah 2: Video Penanganan</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="nextStep" class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Menyimpan Draft...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         5. LANGKAH 2: VIDEO PENANGANAN & BUKTI (FILE ATAU LINK EKSTERNAL)
         ========================================================================= --}}
    @if ($step === 2)
        <div class="bg-white border border-neutral-200 rounded-md p-5 sm:p-7 space-y-7 shadow-2xs">
            
            {{-- Ringkasan Draf Langkah 1 yang Tersimpan --}}
            <div class="bg-neutral-50 border border-neutral-200 rounded-md p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div class="space-y-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-mono text-xs font-bold text-neutral-900 bg-white px-2 py-0.5 rounded-badge border border-neutral-200">
                            {{ $nomorBaPreview }}
                        </span>
                        <span class="text-neutral-300">&middot;</span>
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                            Status: Tersimpan Sebagai Draf
                        </span>
                    </div>
                    <p class="text-xs text-neutral-700 truncate font-sans">
                        <strong class="text-neutral-900">Uraian Masalah:</strong> {{ $deskripsiMasalah }}
                    </p>
                </div>

                <button type="button"
                        wire:click="previousStep"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-neutral-200 bg-white hover:bg-neutral-50 hover:border-neutral-300 hover:shadow-xs text-neutral-700 rounded-md text-xs font-medium font-sans transition-all duration-200 ease-out shrink-0 shadow-2xs active:scale-[0.98]">
                    <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                    </svg>
                    <span>Edit Kembali Data CAPA</span>
                </button>
            </div>

            {{-- Header Video Penanganan --}}
            <div class="space-y-1.5 pb-2 border-b border-neutral-200">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-bold text-neutral-900 bg-neutral-100 border border-neutral-300 rounded-badge uppercase tracking-wider shadow-2xs">
                        Verifikasi Visual
                    </span>
                    <span class="text-xs text-neutral-500 font-sans">&middot; Bukti Objektif</span>
                </div>
                <h2 class="text-base sm:text-lg font-bold text-neutral-900 font-sans tracking-tight">
                    Langkah 2: Dokumentasi Video Bukti &amp; Panduan Penanganan
                </h2>
                <p class="text-xs sm:text-sm text-neutral-600 leading-relaxed max-w-[68ch]">
                    Sesuai instruksi standar operasional mutu, pelaporan BA wajib dilengkapi rekaman visual kondisi lapangan atau panduan tindakan perbaikan. Pilih salah satu antara <strong>mengunggah file video langsung</strong> atau <strong>mencantumkan tautan video eksternal</strong> (Google Drive, OneDrive, atau server lokal).
                </p>
            </div>

            {{-- Pesan Error Spesifik Video Wajib --}}
            @error('videoRequired')
                <div class="p-4 bg-red-50 border border-red-200 rounded-md text-xs font-semibold text-red-800 flex items-center gap-2.5 shadow-2xs" role="alert">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ $message }}</span>
                </div>
            @enderror

            {{-- Pilihan Metode Input Video (Tab Switcher) --}}
            <div class="space-y-4">
                <div class="flex border-b border-neutral-200 gap-2">
                    <button type="button"
                            wire:click="$set('videoMethod', 'file')"
                            class="px-4 py-2.5 text-xs font-sans font-bold border-b-2 transition-all duration-200 ease-out flex items-center gap-2 {{ $videoMethod === 'file' ? 'border-brand text-brand-dark bg-brand-tint/25 shadow-2xs' : 'border-transparent text-neutral-600 hover:text-neutral-900 hover:border-neutral-300' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <span>1. Unggah Berkas Video Langsung</span>
                    </button>
                    <button type="button"
                            wire:click="$set('videoMethod', 'link')"
                            class="px-4 py-2.5 text-xs font-sans font-bold border-b-2 transition-all duration-200 ease-out flex items-center gap-2 {{ $videoMethod === 'link' ? 'border-brand text-brand-dark bg-brand-tint/25 shadow-2xs' : 'border-transparent text-neutral-600 hover:text-neutral-900 hover:border-neutral-300' }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                        </svg>
                        <span>2. Gunakan Tautan Link Video Eksternal</span>
                    </button>
                </div>

                {{-- Opsi A: Upload Berkas Video --}}
                @if ($videoMethod === 'file')
                    <div class="space-y-4 bg-neutral-50/70 border border-neutral-200 rounded-md p-5 sm:p-6">
                        <label class="block text-xs font-bold text-neutral-900 font-sans">
                            Pilih Berkas Video Penanganan (MP4, MOV, WEBM &mdash; Batas Maksimal 100MB)
                        </label>

                        <div class="border-2 border-dashed border-neutral-300 rounded-md p-6 sm:p-8 text-center bg-white hover:border-brand hover:bg-brand-tint/10 transition-all duration-200 ease-out">
                            <input type="file"
                                   id="video_file"
                                   wire:model="videoFile"
                                   accept="video/mp4,video/quicktime,video/webm,video/x-matroska"
                                   class="hidden" />
                            <label for="video_file" class="cursor-pointer block space-y-3">
                                <div class="w-12 h-12 rounded-full bg-neutral-100 flex items-center justify-center mx-auto text-neutral-600 transition-transform duration-150 active:scale-95 border border-neutral-200 shadow-2xs">
                                    <svg class="w-6 h-6 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                </div>
                                <div class="text-xs font-bold text-brand hover:text-brand-dark hover:underline font-sans">
                                    Klik di sini untuk memilih file rekaman video dari perangkat Anda
                                </div>
                                <div class="text-xs text-neutral-500 font-sans">
                                    Mendukung format video resmi: <span class="font-mono font-medium">.mp4</span>, <span class="font-mono font-medium">.mov</span>, <span class="font-mono font-medium">.webm</span> (Batas ukuran maksimal 100MB)
                                </div>
                            </label>
                        </div>

                        {{-- Status Uploading Livewire --}}
                        <div wire:loading wire:target="videoFile" class="text-xs text-brand-dark font-medium flex items-center gap-2 p-2.5 bg-brand-tint/40 rounded-md border border-brand/30">
                            <svg class="animate-spin h-4 w-4 text-brand" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Sedang memproses dan mengunggah berkas video ke penyimpanan... Mohon tunggu.</span>
                        </div>

                        {{-- Card Berkas Video yang Telah Terpilih --}}
                        @if ($videoFile)
                            <div class="p-3.5 bg-brand-tint/40 border border-brand/30 rounded-md flex items-center justify-between text-xs shadow-2xs">
                                <div class="flex items-center gap-2.5 font-medium text-brand-dark min-w-0">
                                    <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span class="truncate font-sans max-w-xs sm:max-w-md">
                                        Berkas video terpilih: <strong>{{ is_object($videoFile) && method_exists($videoFile, 'getClientOriginalName') ? $videoFile->getClientOriginalName() : 'Video Terlampir' }}</strong>
                                    </span>
                                </div>
                                <button type="button"
                                        wire:click="$set('videoFile', null)"
                                        class="text-red-700 hover:text-red-900 hover:underline text-xs font-semibold shrink-0 ml-3 font-sans transition-colors duration-150">
                                    Hapus
                                </button>
                            </div>
                        @endif

                        @error('videoFile')
                            <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                {{-- Opsi B: Tautan Link Eksternal --}}
                @if ($videoMethod === 'link')
                    <div class="space-y-4 bg-neutral-50/70 border border-neutral-200 rounded-md p-5 sm:p-6">
                        <div>
                            <label for="video_external_link" class="block text-xs font-bold text-neutral-900 font-sans mb-1.5">
                                Masukkan Tautan Link Video Eksternal
                            </label>
                            <div class="relative">
                                <input type="url"
                                       id="video_external_link"
                                       wire:model.live="videoExternalLink"
                                       placeholder="Contoh: https://drive.google.com/file/d/xxxx/view atau https://onedrive.live.com/..."
                                       class="w-full pl-3 pr-9 py-2.5 bg-white border border-neutral-300 rounded-md text-xs font-sans text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand transition-all duration-150" />
                                <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none text-neutral-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                    </svg>
                                </div>
                            </div>
                            <p class="text-xs text-neutral-600 mt-1.5 leading-relaxed font-sans">
                                Pastikan pengaturan izin berbagi tautan dapat diakses oleh reviewer/atasan (setel hak akses minimal <em>Viewer / Siapa saja yang memiliki link</em>).
                            </p>
                            @error('videoExternalLink')
                                <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                @endif
            </div>

            {{-- ACTION BAR STEP 2 --}}
            <div class="pt-5 border-t border-neutral-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <button type="button"
                        wire:click="previousStep"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 hover:text-neutral-900 hover:border-neutral-300 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-neutral-900/15 shadow-2xs">
                    <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    <span>Kembali ke Langkah 1</span>
                </button>

                <button type="button"
                        wire:click="submit"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-brand text-white font-sans font-medium text-xs rounded-md hover:bg-brand-dark hover:shadow-sm active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="submit" class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                        </svg>
                        <span>Kirim Laporan BA &amp; Video</span>
                    </span>
                    <span wire:loading wire:target="submit" class="flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memproses Pengiriman...</span>
                    </span>
                </button>
            </div>
        </div>
    @endif
</div>
