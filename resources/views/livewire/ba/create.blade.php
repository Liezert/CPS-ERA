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
                {{-- span = pesan error per isian (bukan ikon banner ringkasan), supaya input-nya bisa difokuskan. --}}
                const firstError = document.querySelector('span.text-red-600, [aria-invalid=\'true\']');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const focusable = firstError.closest('div')?.querySelector('input, select, textarea');
                    if (focusable) focusable.focus();
                }
            });
        },

        {{-- Salinan isian form di browser: kalau halaman harus dimuat ulang (mis. jaringan
             putus), isian terakhir dipulihkan. Kunci per user + per draf, dihapus saat laporan terkirim. --}}
        localDraftKey: 'cps-era:capa-draft:{{ auth()->id() }}:{{ $baIncidentId ?? 'baru' }}',
        localDraftFields: @js(\App\Livewire\Ba\Create::LOCAL_DRAFT_FIELDS),
        restoredAt: null,
        readLocalDraft(key) {
            try { return JSON.parse(localStorage.getItem(key)); } catch (e) { return null; }
        },
        saveLocalDraft() {
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
            if (Date.now() - (saved.savedAt ?? 0) > 7 * 864e5) { try { localStorage.removeItem(this.localDraftKey) } catch (e) {} return; }
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
                        {{ $isRevision ? 'Revisi Formulir Tindakan Korektif (CAPA / FTK)' : 'Pelaporan Tindakan Korektif (CAPA)' }}
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

    {{-- Notifikasi Draf Tersimpan: toast di bawah layar, dekat tombol simpan yang baru diklik
         (dulu muncul di atas halaman sehingga tak terlihat saat pengguna di bagian bawah form). --}}
    @if (session()->has('success'))
        <div wire:key="draft-toast-{{ md5(session('success').microtime()) }}"
             x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show" x-transition
             class="fixed z-30 bottom-20 tablet:bottom-6 inset-x-4 sm:inset-x-auto sm:right-6 sm:max-w-md p-4 bg-white border border-brand/40 rounded-md flex items-center justify-between gap-3 text-xs font-sans text-brand-dark shadow-lg" role="status">
            <div class="flex items-center gap-2.5 min-w-0">
                <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <span class="font-medium truncate">{{ session('success') }}</span>
            </div>
            <span class="text-xs font-mono text-brand font-semibold shrink-0">Tersimpan di Sistem</span>
        </div>
    @endif

    {{-- Isian yang belum tersimpan dipulihkan dari browser setelah halaman dimuat ulang --}}
    <div x-show="restoredAt" x-cloak
         class="p-4 bg-amber-50 border border-amber-300 rounded-md flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs font-sans text-amber-900 shadow-2xs" role="status">
        <span>
            Isian terakhir Anda (<span class="font-mono" x-text="restoredAt"></span>) dipulihkan dari perangkat ini.
            Periksa kembali, lalu simpan draf atau kirim laporan.
        </span>
        <button type="button" x-on:click="discardLocalDraft()"
                class="shrink-0 px-3 py-1.5 border border-amber-400 rounded-md bg-white font-medium hover:bg-amber-100 transition-colors">
            Buang isian yang dipulihkan
        </button>
    </div>

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

                    {{-- Tombol Kirim Laporan ke Supervisor --}}
                    {{-- Scroll ke error setelah respons server tiba; kalau dipanggil saat klik, pesan error belum ada di DOM. --}}
                    <button type="button"
                            @click="$wire.submitReport().then(() => scrollToFirstError())"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand text-white font-sans font-medium text-xs rounded-md hover:bg-brand-dark hover:shadow-sm active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="submitReport" class="flex items-center gap-1.5">
                            <span>Kirim Laporan ke Supervisor</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="submitReport" class="flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Mengirim Laporan...</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
</div>
