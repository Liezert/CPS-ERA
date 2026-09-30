<div class="max-w-3xl mx-auto space-y-6 pb-12" data-form-draft="title,description,learningCategoryId,videoMethod,videoExternalLink">
    <header class="bg-white border border-neutral-200 rounded-md p-5 sm:p-6 shadow-2xs space-y-2">
        <nav class="flex items-center gap-2 text-xs font-sans text-neutral-500 font-medium" aria-label="Breadcrumb">
            <a href="{{ route('learning.index') }}" class="hover:text-neutral-900 transition-colors">Learning</a>
            <span>/</span>
            <a href="{{ route('videos.index') }}" class="hover:text-neutral-900 transition-colors">Video Kontribusi</a>
            <span>/</span>
            <span class="text-neutral-900 font-semibold">Unggah</span>
        </nav>
        <h1 class="font-sans font-bold text-xl sm:text-2xl text-neutral-900 tracking-tight">Unggah Video Kontribusi</h1>
        <p class="font-sans text-xs sm:text-sm text-neutral-600 leading-relaxed max-w-[68ch]">
            Bagikan video praktik kerja, tips, atau cara penanganan masalah. Setelah disetujui tim HR, video tampil di
            <strong>Learning</strong> untuk seluruh karyawan dan Anda mendapat <strong>1 Poin CPS ERA</strong> (maksimal 3 poin per tahun).
        </p>
    </header>

    @error('video')
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-xs font-semibold text-red-800" role="alert">{{ $message }}</div>
    @enderror

    <form wire:submit="submit" class="bg-white border border-neutral-200 rounded-md p-5 sm:p-7 space-y-6 shadow-2xs">
        <div class="grid grid-cols-1 gap-4">
            <div>
                <label for="title" class="block text-xs font-bold text-neutral-900 mb-1.5">Judul Video <span class="text-red-600">*</span></label>
                <input type="text" id="title" wire:model="title" maxlength="150"
                       placeholder="Contoh: Cara Kalibrasi Sensor Suhu Line 2"
                       class="w-full px-3 py-2.5 bg-white border border-neutral-300 rounded-md text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand" />
                @error('title') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="category" class="block text-xs font-bold text-neutral-900 mb-1.5">Kategori Learning</label>
                <select id="category" wire:model="learningCategoryId"
                        class="w-full px-3 py-2.5 bg-white border border-neutral-300 rounded-md text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand">
                    <option value="">Pilih kategori (opsional)</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('learningCategoryId') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-neutral-900 mb-1.5">Deskripsi Singkat</label>
                <textarea id="description" wire:model="description" rows="3" maxlength="2000"
                          placeholder="Apa yang bisa dipelajari dari video ini?"
                          class="w-full p-3 bg-white border border-neutral-300 rounded-md text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></textarea>
                @error('description') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex border-b border-neutral-200 gap-2">
                <button type="button" wire:click="$set('videoMethod', 'file')"
                        class="px-4 py-2.5 text-xs font-sans font-bold border-b-2 transition-colors {{ $videoMethod === 'file' ? 'border-brand text-brand-dark bg-brand-tint/25' : 'border-transparent text-neutral-600 hover:text-neutral-900' }}">
                    Unggah Berkas Video
                </button>
                <button type="button" wire:click="$set('videoMethod', 'link')"
                        class="px-4 py-2.5 text-xs font-sans font-bold border-b-2 transition-colors {{ $videoMethod === 'link' ? 'border-brand text-brand-dark bg-brand-tint/25' : 'border-transparent text-neutral-600 hover:text-neutral-900' }}">
                    Gunakan Tautan Video
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
                            Pilih Berkas Video (MP4, MOV, WEBM &mdash; Batas Maksimal 100MB)
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
                            <p class="text-xs text-neutral-500 font-sans">Jangan menutup halaman sampai unggahan selesai.</p>
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
                    <div class="space-y-2 bg-neutral-50/70 border border-neutral-200 rounded-md p-5 sm:p-6">
                        <label for="video_external_link" class="block text-xs font-bold text-neutral-900 font-sans">
                            Tautan Video (Google Drive / OneDrive)
                        </label>
                        <input type="url" id="video_external_link" wire:model="videoExternalLink"
                               placeholder="https://drive.google.com/file/d/xxxx/view"
                               class="w-full px-3 py-2.5 bg-white border border-neutral-300 rounded-md text-xs text-neutral-900 placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand" />
                        <p class="text-xs text-neutral-600">Pastikan izin berbagi tautan minimal <em>Siapa saja yang memiliki link dapat melihat</em>.</p>
                        @error('videoExternalLink')
                            <span class="text-xs text-red-600 block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
        </div>

        <div class="pt-5 border-t border-neutral-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
            <a href="{{ route('videos.index') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 border border-neutral-200 rounded-md text-xs font-medium text-neutral-700 bg-white hover:bg-neutral-50">Batal</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="submit,videoFile"
                    class="inline-flex items-center justify-center px-5 py-2.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-semibold disabled:opacity-60 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="submit">Kirim ke HR untuk Direview</span>
                <span wire:loading wire:target="submit">Mengirim...</span>
            </button>
        </div>
    </form>

    {{-- Overlay unggah ke Google Drive: video besar bisa butuh beberapa menit --}}
    <div wire:loading.flex wire:target="submit" class="fixed inset-0 z-50 items-center justify-center bg-neutral-900/60 p-4" role="status" aria-live="polite">
        <div class="bg-white rounded-md border border-neutral-200 shadow-xl max-w-md w-full p-6 space-y-3 text-center">
            <svg class="animate-spin h-6 w-6 text-brand mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <h3 class="text-sm font-bold text-neutral-900">Mengirim video ke Google Drive</h3>
            <p class="text-xs text-neutral-600">Video besar dapat memakan waktu beberapa menit. Mohon jangan menutup halaman ini.</p>
        </div>
    </div>
</div>
