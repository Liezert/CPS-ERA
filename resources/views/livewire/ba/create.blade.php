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
        },

        {{-- Salinan isian Langkah 1 di browser: kalau halaman harus dimuat ulang (mis. jaringan
             putus), isian terakhir dipulihkan. Kunci per user + per draf, dihapus saat laporan terkirim. --}}
        localDraftKey: 'cps-era:capa-draft:{{ auth()->id() }}:{{ $baIncidentId ?? 'baru' }}',
        localDraftFields: @js(\App\Livewire\Ba\Create::LOCAL_DRAFT_FIELDS),
        restoredAt: null,
        readLocalDraft(key) {
            try { return JSON.parse(localStorage.getItem(key)); } catch (e) { return null; }
        },
        saveLocalDraft() {
            if (this.$wire.step !== 1) return;
            const values = {};
            this.localDraftFields.forEach(field => values[field] = this.$wire[field]);
            try { localStorage.setItem(this.localDraftKey, JSON.stringify({ savedAt: Date.now(), values })); } catch (e) {}
        },
        discardLocalDraft() {
            try { localStorage.removeItem(this.localDraftKey); } catch (e) {}
            window.location.reload();
        },
        onDraftSaved(id) {
            try { localStorage.removeItem(this.localDraftKey); } catch (e) {}
            this.localDraftKey = 'cps-era:capa-draft:{{ auth()->id() }}:' + id;
            this.saveLocalDraft();
        },
        init() {
            const saved = this.readLocalDraft(this.localDraftKey);
            if (! saved?.values) return;
            const differs = this.localDraftFields.some(field => (saved.values[field] ?? '') !== (this.$wire[field] ?? ''));
            if (! differs) return;
            this.$wire.restoreLocalDraft(saved.values).then(() => {
                this.restoredAt = new Date(saved.savedAt).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
            });
        }
    }"
    x-on:input.debounce.500ms="saveLocalDraft()"
    x-on:change="saveLocalDraft()"
    x-on:capa-draft-saved.window="onDraftSaved($event.detail.id)"
    x-on:capa-draft-submitted.window="try { localStorage.removeItem(localDraftKey) } catch (e) {}"
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

    {{-- Isian yang belum tersimpan dipulihkan dari browser setelah halaman dimuat ulang --}}
    <div x-show="restoredAt && $wire.step === 1" x-cloak
         class="p-4 bg-amber-50 border border-amber-300 rounded-md flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs font-sans text-amber-900 shadow-2xs" role="status">
        <span>
            Isian terakhir Anda (<span class="font-mono" x-text="restoredAt"></span>) dipulihkan dari perangkat ini.
            Periksa kembali, lalu simpan draf atau lanjutkan ke Langkah 2.
        </span>
        <button type="button" x-on:click="discardLocalDraft()"
                class="shrink-0 px-3 py-1.5 border border-amber-400 rounded-md bg-white font-medium hover:bg-amber-100 transition-colors">
            Buang isian yang dipulihkan
        </button>
    </div>

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
                        @if($step !== 1)
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                                Draf Tersimpan (Klik untuk Edit)
                            </span>
                        @endif
                    </div>
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
                        @endif
                    </div>
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
            
            <x-capa.form.header :values="$capa" />

            <x-capa.form.dokumen :values="$capa" :divisions="$divisions" />

            <x-capa.form.sumber :values="$capa" :sumber-options="$sumberOptions" />

            <x-capa.form.kejadian :values="$capa" />

            <x-capa.form.akar-masalah :values="$capa" />

            <x-capa.form.rencana-penanganan :values="$capa" />

            <x-capa.form.dampak :values="$capa" />

            {{-- ACTION BAR STEP 1 DENGAN RESPONSIF TINGGI --}}
            <div class="pt-5 border-t border-neutral-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-4">

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

            @if ($hasExistingDriveVideo)
                <div class="p-3 bg-brand-tint/30 border border-brand/20 rounded-md text-xs font-sans text-brand-dark">
                    Video yang sudah dikirim sebelumnya akan dipakai lagi. Unggah video baru hanya jika ingin menggantinya.
                </div>

                @if ($existingVideoIncident)
                    <x-capa.video-player :incident="$existingVideoIncident" />
                @endif
            @endif

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
                    {{-- Status unggahan mengikuti event upload Livewire (start/progress/finish/error),
                         sehingga persentase yang tampil adalah progres unggahan sesungguhnya.
                         Pratinjau diputar langsung dari berkas di perangkat pengguna (object URL),
                         tanpa mengunduh ulang dari server. --}}
                    <div x-data="{
                            uploading: false,
                            progress: 0,
                            uploadFailed: false,
                            previewUrl: null,
                            previewUnsupported: false,
                            setPreview(file) {
                                if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                                this.previewUnsupported = false;
                                this.previewUrl = file ? URL.createObjectURL(file) : null;
                            },
                            clearPreview() {
                                this.setPreview(null);
                                this.$refs.videoInput.value = '';
                            },
                         }"
                         x-on:livewire-upload-start="uploading = true; progress = 0; uploadFailed = false"
                         x-on:livewire-upload-progress="progress = $event.detail.progress"
                         x-on:livewire-upload-finish="uploading = false; progress = 100"
                         x-on:livewire-upload-error="uploading = false; uploadFailed = true; clearPreview()"
                         x-on:livewire-upload-cancel="uploading = false; clearPreview()"
                         class="space-y-4 bg-neutral-50/70 border border-neutral-200 rounded-md p-5 sm:p-6">
                        <label class="block text-xs font-bold text-neutral-900 font-sans">
                            Pilih Berkas Video Penanganan (MP4, MOV, WEBM &mdash; Batas Maksimal 100MB)
                        </label>

                        {{-- Area unggah: klik memilih berkas, atau seret & lepas berkas ke sini.
                             Berkas yang dilepas dipasang ke input lalu dipicu event change,
                             sehingga wire:model memprosesnya sama seperti pemilihan manual. --}}
                        <div x-data="{ dragging: false }"
                             x-show="!uploading"
                             @dragover.prevent="dragging = true"
                             @dragenter.prevent="dragging = true"
                             @dragleave.prevent="dragging = false"
                             @drop.prevent="
                                dragging = false;
                                const dropped = $event.dataTransfer.files;
                                if (! dropped.length) return;
                                const transfer = new DataTransfer();
                                transfer.items.add(dropped[0]);
                                $refs.videoInput.files = transfer.files;
                                $refs.videoInput.dispatchEvent(new Event('change', { bubbles: true }));
                             "
                             :class="dragging
                                ? 'border-brand bg-brand-tint/20 ring-2 ring-brand/20'
                                : 'border-neutral-300 bg-white hover:border-brand hover:bg-brand-tint/10'"
                             class="border-2 border-dashed rounded-md p-6 sm:p-8 text-center transition-all duration-200 ease-out">
                            <input type="file"
                                   id="video_file"
                                   x-ref="videoInput"
                                   x-on:change="setPreview($event.target.files[0])"
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
                                    <span x-show="!dragging">{{ $videoFile ? 'Ganti video: klik atau seret berkas lain ke sini' : 'Klik di sini atau seret berkas video ke area ini' }}</span>
                                    <span x-show="dragging" x-cloak>Lepaskan berkas untuk mengunggah</span>
                                </div>
                                <div class="text-xs text-neutral-500 font-sans">
                                    Mendukung format video resmi: <span class="font-mono font-medium">.mp4</span>, <span class="font-mono font-medium">.mov</span>, <span class="font-mono font-medium">.webm</span> (Batas ukuran maksimal 100MB)
                                </div>
                            </label>
                        </div>

                        {{-- Progress bar unggahan (persentase nyata dari Livewire) --}}
                        <div x-show="uploading" x-cloak class="p-3.5 bg-white border border-brand/30 rounded-md space-y-2 shadow-2xs" role="status" aria-live="polite">
                            <div class="flex items-center justify-between text-xs font-sans">
                                <span class="font-medium text-brand-dark">Mengunggah video&hellip;</span>
                                <span class="font-mono font-semibold text-brand-dark" x-text="progress + '%'"></span>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-full h-2 overflow-hidden"
                                 role="progressbar" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progress" aria-label="Progres unggahan video">
                                <div class="bg-brand h-2 rounded-full transition-all duration-200" :style="{ width: progress + '%' }"></div>
                            </div>
                            <p class="text-[11px] text-neutral-500 font-sans">Jangan menutup halaman sampai unggahan selesai.</p>
                        </div>

                        <div x-show="uploadFailed" x-cloak class="p-3 bg-red-50 border border-red-200 rounded-md text-xs font-sans text-red-800" role="alert">
                            Unggahan video gagal. Periksa koneksi internet serta ukuran/format berkas, lalu coba unggah ulang.
                        </div>

                        {{-- Berkas terunggah + pratinjau --}}
                        @if ($videoFile)
                            <div x-show="!uploading" class="space-y-3">
                                <div class="p-3.5 bg-brand-tint/40 border border-brand/30 rounded-md flex items-center justify-between text-xs shadow-2xs">
                                    <div class="flex items-center gap-2.5 font-medium text-brand-dark min-w-0">
                                        <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                        </svg>
                                        <span class="truncate font-sans max-w-xs sm:max-w-md">
                                            Berhasil terunggah: <strong>{{ is_object($videoFile) && method_exists($videoFile, 'getClientOriginalName') ? $videoFile->getClientOriginalName() : 'Video Terlampir' }}</strong>
                                        </span>
                                    </div>
                                    <button type="button"
                                            wire:click="$set('videoFile', null)"
                                            x-on:click="clearPreview()"
                                            class="text-red-700 hover:text-red-900 hover:underline text-xs font-semibold shrink-0 ml-3 font-sans transition-colors duration-150">
                                        Hapus
                                    </button>
                                </div>

                                <div x-show="previewUrl" x-cloak class="space-y-1.5">
                                    <p class="text-xs font-bold text-neutral-900 font-sans">Pratinjau Video</p>
                                    <video x-show="!previewUnsupported"
                                           :src="previewUrl"
                                           x-on:error="previewUnsupported = true"
                                           controls
                                           preload="metadata"
                                           class="w-full max-h-96 rounded-md border border-neutral-200 bg-black"></video>
                                    <p x-show="previewUnsupported" class="p-3 bg-neutral-100 border border-neutral-200 rounded-md text-xs text-neutral-600 font-sans">
                                        Browser ini tidak bisa memutar format berkas tersebut untuk pratinjau. Berkas tetap bisa dikirim.
                                    </p>
                                </div>
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
                        <span>Mengunggah ke Drive...</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- =====================================================================
             OVERLAY PROSES UNGGAH KE GOOGLE DRIVE
             - Video dikirim bertahap per potongan; proses bisa memakan waktu lama
               sehingga pengguna perlu tahu sistem sedang bekerja, bukan menggantung.
             ===================================================================== --}}
        <div wire:loading.flex wire:target="submit"
             class="fixed inset-0 z-50 items-center justify-center bg-neutral-900/60 p-4"
             role="status"
             aria-live="polite">
            <div class="bg-white rounded-md border border-neutral-200 shadow-xl max-w-md w-full p-6 space-y-4 text-center">
                <div class="w-12 h-12 rounded-full bg-brand-tint flex items-center justify-center mx-auto">
                    <svg class="animate-spin h-6 w-6 text-brand" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-sm font-bold text-neutral-900 font-sans">
                        Mengunggah video ke Google Drive
                    </h3>
                    <p class="text-xs text-neutral-600 leading-relaxed">
                        Berkas dikirim bertahap per potongan 8 MB. Video berukuran besar dapat memakan waktu
                        beberapa menit, tergantung kecepatan jaringan.
                    </p>
                </div>

                {{-- Bar indeterminate: menandakan proses berjalan tanpa mengklaim persentase palsu --}}
                <div class="w-full bg-neutral-100 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-brand h-1.5 w-1/3 rounded-full animate-pulse"></div>
                </div>

                <p class="text-[11px] text-neutral-500 font-sans">
                    Mohon <strong class="text-neutral-700">jangan menutup atau memuat ulang halaman ini</strong>
                    sampai proses selesai.
                </p>
            </div>
        </div>
    @endif
</div>
