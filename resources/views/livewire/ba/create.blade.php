<div class="max-w-4xl mx-auto space-y-6" @dragover.prevent @drop.prevent>
    {{-- Header Form --}}
    <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-sans text-neutral-500 mb-1">
                <a href="{{ route('ba.index') }}" class="hover:text-brand transition-colors">BA &amp; Lesson Learned</a>
                <span>&rsaquo;</span>
                <span class="text-neutral-900 font-medium">Buat Laporan Baru</span>
            </div>
            <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                Pelaporan Berita Acara (BA) Baru
            </h1>
            <p class="font-sans text-xs text-neutral-500 mt-1">
                Laporan insiden operasional, ketidaksesuaian mesin/proses, dan usulan Lesson Learned PT CPS.
            </p>
        </div>

        <div class="shrink-0">
            <a href="{{ route('ba.index') }}"
               class="inline-flex items-center px-3 py-1.5 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                Batal
            </a>
        </div>
    </div>

    {{-- Form Pelaporan --}}
    <form wire:submit="save" class="bg-white border border-neutral-200 rounded-md p-6 space-y-6">
        
        {{-- Ringkasan Validasi Form --}}
        @if ($errors->any())
            <div class="p-4 bg-neutral-50 border border-neutral-300 rounded-badge flex items-start gap-3">
                <div class="w-5 h-5 text-neutral-700 shrink-0 mt-0.5">
                    <x-layout.nav-icon name="shield-alert" class="w-5 h-5" />
                </div>
                <div class="space-y-1 text-xs font-sans text-neutral-800">
                    <p class="font-semibold text-neutral-900">Harap periksa kelengkapan laporan:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px] text-neutral-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        
        {{-- Baris 1: Nomor BA (Auto-generate, Read-Only, IBM Plex Mono) & Divisi (13 opsi) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Nomor BA Auto-generate --}}
            <div>
                <label for="nomor_ba" class="block text-xs font-sans font-medium text-neutral-700 uppercase tracking-normal mb-1.5">
                    Nomor Berita Acara (Auto-Generate)
                </label>
                <div class="relative">
                    <input type="text"
                           id="nomor_ba"
                           value="{{ $nomorBaPreview }}"
                           readonly
                           disabled
                           class="w-full px-3 py-2 bg-neutral-100 border border-neutral-200 rounded-badge text-xs font-mono font-semibold text-neutral-700 cursor-not-allowed select-none"
                           aria-describedby="nomor_ba_hint" />
                </div>
                <p id="nomor_ba_hint" class="text-[11px] font-sans text-neutral-400 mt-1">
                    Nomor dibuat otomatis oleh sistem dengan format standar <span class="font-mono text-neutral-600">BA-YYYY-NNNN</span> (read-only).
                </p>
            </div>

            {{-- Pilihan 13 Divisi Tetap (Reaktif live binding agar tidak reset) --}}
            <div>
                <label for="division_id" class="block text-xs font-sans font-medium text-neutral-700 uppercase tracking-normal mb-1.5">
                    Divisi Terkait <span class="text-neutral-400">*</span>
                </label>
                <select id="division_id"
                        wire:model.live="divisionId"
                        class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-badge text-xs font-sans text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors">
                    <option value="">Pilih Divisi (13 Divisi Resmi)</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                </select>
                @error('divisionId')
                    <span class="text-[11px] font-sans text-neutral-600 font-medium mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- Baris 2: Judul Insiden (Reaktif live debounce agar input aman dari reset upload) --}}
        <div>
            <label for="title" class="block text-xs font-sans font-medium text-neutral-700 uppercase tracking-normal mb-1.5">
                Judul Laporan / Insiden <span class="text-neutral-400">*</span>
            </label>
            <input type="text"
                   id="title"
                   wire:model.live.debounce.300ms="title"
                   placeholder="Contoh: Terhentinya Mesin Injection Nozzle 02 Akibat Suhu Berlebih"
                   class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-badge text-xs font-sans text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors" />
            @error('title')
                <span class="text-[11px] font-sans text-neutral-600 font-medium mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        {{-- Baris 3: Deskripsi Kejadian Kronologis (Reaktif live debounce agar input aman dari reset upload) --}}
        <div>
            <label for="description" class="block text-xs font-sans font-medium text-neutral-700 uppercase tracking-normal mb-1.5">
                Kronologi Kejadian &amp; Dampak Masalah <span class="text-neutral-400">*</span>
            </label>
            <textarea id="description"
                      wire:model.live.debounce.300ms="description"
                      rows="4"
                      placeholder="Jelaskan secara rinci waktu kejadian, parameter operasi saat insiden, komponen yang rusak, dan tindakan awal yang diambil..."
                      class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-badge text-xs font-sans text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"></textarea>
            @error('description')
                <span class="text-[11px] font-sans text-neutral-600 font-medium mt-1 block">{{ $message }}</span>
            @enderror
        </div>

        {{-- Baris 4: DUA File Upload Terpisah (File BA & FTK) dengan Dukungan DRAG & DROP Card-Scoped --}}
        <div class="pt-4 border-t border-neutral-100 space-y-4">
            <div>
                <h3 class="font-sans font-medium text-sm text-neutral-900">
                    Lampiran Dokumen Wajib (2 Berkas Terpisah)
                </h3>
                <!-- TODO: Menunggu keputusan PRD §5.3 (Poin 6: Batas ukuran file upload) -->
                <p class="font-sans text-xs text-neutral-500 mt-0.5">
                    Tarik dan lepaskan berkas langsung ke kartu masing-masing (Drag &amp; Drop), atau klik untuk memilih berkas. Format: PDF, DOC, DOCX, JPG, PNG (maks 10MB interim — <span class="italic text-neutral-400">[Menunggu Keputusan PRD §5.3: Batas Ukuran File]</span>).
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Slot 1: Card File Dokumen BA (Dropzone Card dengan Progress Bar & File Preview) --}}
                <div x-data="{
                         isDroppingBa: false,
                         isUploadingBa: false,
                         progressBa: 0
                     }"
                     x-on:livewire-upload-start="isUploadingBa = true; progressBa = 0"
                     x-on:livewire-upload-finish="isUploadingBa = false; progressBa = 100"
                     x-on:livewire-upload-error="isUploadingBa = false"
                     x-on:livewire-upload-progress="progressBa = $event.detail.progress"
                     @dragover.prevent.stop="isDroppingBa = true"
                     @dragleave.prevent.stop="isDroppingBa = false"
                     @drop.prevent.stop="
                         isDroppingBa = false;
                         if ($event.dataTransfer && $event.dataTransfer.files.length > 0) {
                             const file = $event.dataTransfer.files[0];
                             isUploadingBa = true;
                             progressBa = 0;
                             $wire.upload('fileBa', file,
                                 () => { isUploadingBa = false; progressBa = 100; },
                                 () => { isUploadingBa = false; },
                                 (e) => { progressBa = e.detail.progress; }
                             );
                         }
                     "
                     :class="isDroppingBa ? 'border-brand bg-brand-tint/30 ring-2 ring-brand/40 border-dashed' : 'border-neutral-200 bg-neutral-50/50 hover:border-neutral-300'"
                     class="border rounded-md p-4 transition-all duration-150 flex flex-col justify-between relative cursor-pointer group"
                     @click="$refs.fileBaInput.click()">
                    
                    {{-- Hidden native file input --}}
                    <input type="file"
                           id="file_ba_input"
                           x-ref="fileBaInput"
                           wire:model="fileBa"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="hidden"
                           @click.stop />

                    <div>
                        {{-- Card Header --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-sans font-semibold text-xs text-neutral-900 group-hover:text-brand transition-colors">
                                1. File Dokumen BA (Berita Acara)
                            </span>
                            @if ($fileBa && !$errors->has('fileBa'))
                                <span class="px-1.5 py-0.5 border border-brand/40 rounded-badge text-[10px] font-mono text-brand-dark bg-brand-tint font-medium">
                                    Terlampir
                                </span>
                            @else
                                <span class="px-1.5 py-0.5 border border-neutral-200 rounded-badge text-[10px] font-mono text-neutral-500 bg-white">
                                    Wajib
                                </span>
                            @endif
                        </div>

                        {{-- Progress Bar saat file sedang diunggah --}}
                        <div x-show="isUploadingBa" x-cloak class="my-3 p-3 bg-white border border-brand/40 rounded-badge" @click.stop>
                            <div class="flex items-center justify-between text-xs font-sans mb-1.5">
                                <span class="font-medium text-brand flex items-center gap-1.5">
                                    <svg class="animate-spin h-3.5 w-3.5 text-brand" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mengunggah Dokumen BA...
                                </span>
                                <span class="font-mono text-xs font-semibold text-brand" x-text="progressBa + '%'"></span>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-[2px] h-2 overflow-hidden">
                                <div class="bg-brand h-2 transition-all duration-150 rounded-[2px]" :style="'width: ' + progressBa + '%'"></div>
                            </div>
                        </div>

                        {{-- Tampilan Preview Berkas setelah berhasil diunggah --}}
                        @if ($fileBa && !$errors->has('fileBa'))
                            <div class="my-2 p-3 bg-white border border-brand/40 rounded-badge space-y-3" @click.stop>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-[2px] bg-brand-tint border border-brand/30 text-brand flex items-center justify-center shrink-0">
                                            <x-layout.nav-icon name="dokumen" class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-sans font-medium text-xs text-neutral-900 truncate" title="{{ $fileBa->getClientOriginalName() }}">
                                                {{ $fileBa->getClientOriginalName() }}
                                            </p>
                                            <p class="font-mono text-[11px] text-neutral-500 mt-0.5">
                                                {{ number_format($fileBa->getSize() / 1024, 1) }} KB &middot; <span class="uppercase font-semibold text-neutral-700">{{ $fileBa->getClientOriginalExtension() }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-brand-tint border border-brand/30 rounded-badge text-[10px] font-sans font-medium text-brand-dark shrink-0">
                                        <x-layout.nav-icon name="badge-check" class="w-3 h-3 text-brand" />
                                        Siap Diunggah
                                    </span>
                                </div>

                                <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-xs">
                                    <button type="button"
                                            @click.stop="$refs.fileBaInput.click()"
                                            class="text-neutral-600 hover:text-brand font-sans transition-colors">
                                        Ganti Berkas
                                    </button>
                                    <button type="button"
                                            wire:click.stop="$set('fileBa', null)"
                                            class="text-neutral-400 hover:text-neutral-700 font-sans transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Drop Area Visual Box (saat belum ada file) --}}
                            <div class="border border-dashed border-neutral-300 rounded-[2px] p-4 text-center bg-white my-2 group-hover:border-neutral-400 transition-colors">
                                <div class="w-8 h-8 mx-auto mb-1.5 rounded-badge bg-neutral-100 text-neutral-500 flex items-center justify-center">
                                    <x-layout.nav-icon name="dokumen" class="w-4 h-4" />
                                </div>
                                <p class="font-sans text-xs font-medium text-neutral-800">
                                    <span class="text-brand underline decoration-brand/30">Klik untuk memilih</span> atau drag &amp; drop ke kartu ini
                                </p>
                                <p class="font-sans text-[10px] text-neutral-400 mt-1">
                                    PDF, DOC, DOCX, JPG, PNG hingga 10MB
                                </p>
                            </div>
                        @endif

                        {{-- Indikator Drag Over --}}
                        <template x-if="isDroppingBa">
                            <div class="mt-2 text-center text-xs font-sans font-medium text-brand">
                                Lepaskan berkas untuk otomatis mengunggah Dokumen BA...
                            </div>
                        </template>
                    </div>

                    @error('fileBa')
                        <span class="text-[11px] font-sans text-neutral-600 font-medium mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Slot 2: Card File Formulir FTK (Dropzone Card dengan Progress Bar & File Preview) --}}
                <div x-data="{
                         isDroppingFtk: false,
                         isUploadingFtk: false,
                         progressFtk: 0
                     }"
                     x-on:livewire-upload-start="isUploadingFtk = true; progressFtk = 0"
                     x-on:livewire-upload-finish="isUploadingFtk = false; progressFtk = 100"
                     x-on:livewire-upload-error="isUploadingFtk = false"
                     x-on:livewire-upload-progress="progressFtk = $event.detail.progress"
                     @dragover.prevent.stop="isDroppingFtk = true"
                     @dragleave.prevent.stop="isDroppingFtk = false"
                     @drop.prevent.stop="
                         isDroppingFtk = false;
                         if ($event.dataTransfer && $event.dataTransfer.files.length > 0) {
                             const file = $event.dataTransfer.files[0];
                             isUploadingFtk = true;
                             progressFtk = 0;
                             $wire.upload('fileFtk', file,
                                 () => { isUploadingFtk = false; progressFtk = 100; },
                                 () => { isUploadingFtk = false; },
                                 (e) => { progressFtk = e.detail.progress; }
                             );
                         }
                     "
                     :class="isDroppingFtk ? 'border-brand bg-brand-tint/30 ring-2 ring-brand/40 border-dashed' : 'border-neutral-200 bg-neutral-50/50 hover:border-neutral-300'"
                     class="border rounded-md p-4 transition-all duration-150 flex flex-col justify-between relative cursor-pointer group"
                     @click="$refs.fileFtkInput.click()">
                    
                    {{-- Hidden native file input --}}
                    <input type="file"
                           id="file_ftk_input"
                           x-ref="fileFtkInput"
                           wire:model="fileFtk"
                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                           class="hidden"
                           @click.stop />

                    <div>
                        {{-- Card Header --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-sans font-semibold text-xs text-neutral-900 group-hover:text-brand transition-colors">
                                2. File Formulir FTK (Faktor Teknis)
                            </span>
                            @if ($fileFtk && !$errors->has('fileFtk'))
                                <span class="px-1.5 py-0.5 border border-brand/40 rounded-badge text-[10px] font-mono text-brand-dark bg-brand-tint font-medium">
                                    Terlampir
                                </span>
                            @else
                                <span class="px-1.5 py-0.5 border border-neutral-200 rounded-badge text-[10px] font-mono text-neutral-500 bg-white">
                                    Wajib
                                </span>
                            @endif
                        </div>

                        {{-- Progress Bar saat file sedang diunggah --}}
                        <div x-show="isUploadingFtk" x-cloak class="my-3 p-3 bg-white border border-brand/40 rounded-badge" @click.stop>
                            <div class="flex items-center justify-between text-xs font-sans mb-1.5">
                                <span class="font-medium text-brand flex items-center gap-1.5">
                                    <svg class="animate-spin h-3.5 w-3.5 text-brand" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mengunggah Formulir FTK...
                                </span>
                                <span class="font-mono text-xs font-semibold text-brand" x-text="progressFtk + '%'"></span>
                            </div>
                            <div class="w-full bg-neutral-100 rounded-[2px] h-2 overflow-hidden">
                                <div class="bg-brand h-2 transition-all duration-150 rounded-[2px]" :style="'width: ' + progressFtk + '%'"></div>
                            </div>
                        </div>

                        {{-- Tampilan Preview Berkas setelah berhasil diunggah --}}
                        @if ($fileFtk && !$errors->has('fileFtk'))
                            <div class="my-2 p-3 bg-white border border-brand/40 rounded-badge space-y-3" @click.stop>
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-8 h-8 rounded-[2px] bg-brand-tint border border-brand/30 text-brand flex items-center justify-center shrink-0">
                                            <x-layout.nav-icon name="sop" class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-sans font-medium text-xs text-neutral-900 truncate" title="{{ $fileFtk->getClientOriginalName() }}">
                                                {{ $fileFtk->getClientOriginalName() }}
                                            </p>
                                            <p class="font-mono text-[11px] text-neutral-500 mt-0.5">
                                                {{ number_format($fileFtk->getSize() / 1024, 1) }} KB &middot; <span class="uppercase font-semibold text-neutral-700">{{ $fileFtk->getClientOriginalExtension() }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-brand-tint border border-brand/30 rounded-badge text-[10px] font-sans font-medium text-brand-dark shrink-0">
                                        <x-layout.nav-icon name="badge-check" class="w-3 h-3 text-brand" />
                                        Siap Diunggah
                                    </span>
                                </div>

                                <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-xs">
                                    <button type="button"
                                            @click.stop="$refs.fileFtkInput.click()"
                                            class="text-neutral-600 hover:text-brand font-sans transition-colors">
                                        Ganti Berkas
                                    </button>
                                    <button type="button"
                                            wire:click.stop="$set('fileFtk', null)"
                                            class="text-neutral-400 hover:text-neutral-700 font-sans transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        @else
                            {{-- Drop Area Visual Box (saat belum ada file) --}}
                            <div class="border border-dashed border-neutral-300 rounded-[2px] p-4 text-center bg-white my-2 group-hover:border-neutral-400 transition-colors">
                                <div class="w-8 h-8 mx-auto mb-1.5 rounded-badge bg-neutral-100 text-neutral-500 flex items-center justify-center">
                                    <x-layout.nav-icon name="sop" class="w-4 h-4" />
                                </div>
                                <p class="font-sans text-xs font-medium text-neutral-800">
                                    <span class="text-brand underline decoration-brand/30">Klik untuk memilih</span> atau drag &amp; drop ke kartu ini
                                </p>
                                <p class="font-sans text-[10px] text-neutral-400 mt-1">
                                    PDF, DOC, DOCX, JPG, PNG hingga 10MB
                                </p>
                            </div>
                        @endif

                        {{-- Indikator Drag Over --}}
                        <template x-if="isDroppingFtk">
                            <div class="mt-2 text-center text-xs font-sans font-medium text-brand">
                                Lepaskan berkas untuk otomatis mengunggah Formulir FTK...
                            </div>
                        </template>
                    </div>

                    @error('fileFtk')
                        <span class="text-[11px] font-sans text-neutral-600 font-medium mt-2 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Tombol Aksi Submit --}}
        <div class="pt-4 border-t border-neutral-100 flex items-center justify-end gap-3">
            <a href="{{ route('ba.index') }}"
               class="px-4 py-2 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                Batal
            </a>

            <button type="submit"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-brand hover:bg-brand-dark text-white rounded-badge text-xs font-sans font-medium transition-colors flex items-center gap-2 focus:outline-none focus:ring-1 focus:ring-brand disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Terbitkan Laporan BA</span>
                <span wire:loading wire:target="save">Menyimpan Dokumen...</span>
            </button>
        </div>
    </form>
</div>
