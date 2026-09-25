<div class="space-y-6">
    {{-- =========================================================================
         1. BREADCRUMBS & HEADER MATERI (Design System §2 & §5)
         ========================================================================= --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-sans text-neutral-600 font-medium mb-1.5 flex-wrap">
                <a href="{{ route('learning.index') }}" class="hover:text-brand transition-colors">Learning</a>
                <span>&rsaquo;</span>
                @if($material->category)
                    <span class="text-neutral-800">{{ $material->category->name }}</span>
                    <span>&rsaquo;</span>
                @endif
                <span class="text-neutral-900 font-semibold truncate max-w-xs">{{ $material->title }}</span>
            </div>

            <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                {{ $material->title }}
            </h1>

            <div class="flex items-center gap-3 mt-2 text-xs font-sans text-neutral-600 font-medium flex-wrap">
                @if($material->category)
                    <span class="px-2 py-0.5 border border-neutral-200 rounded-badge text-neutral-800 bg-white font-sans text-[11px] font-semibold">
                        {{ $material->category->name }}
                    </span>
                @endif

                <div class="inline-flex items-center gap-1.5 text-neutral-700 font-sans text-[11px] font-medium">
                    <x-layout.nav-icon :name="$material->type" class="w-4 h-4 text-neutral-600" />
                    <span class="capitalize">{{ ucfirst($material->type) }}</span>
                </div>

                <span>&middot;</span>
                <span>Diterbitkan oleh {{ $material->creator?->name ?? 'Tim Internal' }}</span>
                <span>&middot;</span>
                <span class="font-mono text-neutral-500">{{ $material->created_at->format('d M Y') }}</span>
            </div>
        </div>

        <div class="shrink-0 flex items-center gap-2">
            <a href="{{ route('learning.index') }}"
               class="inline-flex items-center px-3 py-1.5 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                Kembali ke Katalog
            </a>
        </div>
    </div>

    {{-- Banner Notifikasi Status Progress --}}
    @if(session()->has('progress_status'))
        <div x-data="{ show: true }"
             x-show="show"
             class="p-4 bg-brand-tint border border-brand/40 rounded-badge flex items-center justify-between gap-3 text-brand-dark">
            <div class="flex items-center gap-2.5">
                <x-layout.nav-icon name="badge-check" class="w-5 h-5 text-brand shrink-0" />
                <span class="font-sans text-xs font-medium">{{ session('progress_status') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-brand hover:text-brand-dark p-1">
                <x-layout.nav-icon name="x-mark" class="w-4 h-4" />
            </button>
        </div>
    @endif

    {{-- =========================================================================
         2. LAYOUT KONTEN MATERI (KIRI: VIEWER, KANAN: PROGRESS & POST-TEST)
         ========================================================================= --}}
    <div class="grid grid-cols-1 desktop:grid-cols-3 gap-6">
        
        {{-- SISI KIRI (2 Kolom): KONTEN MATERI PEMBELAJARAN --}}
        <div class="desktop:col-span-2 space-y-6">
            
            {{-- Kartu Konten Materi Utama --}}
            <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-5">
                <div>
                    <h2 class="font-sans font-medium text-sm text-neutral-900 uppercase tracking-normal mb-2">
                        Deskripsi &amp; Panduan Materi
                    </h2>
                    <p class="font-sans text-xs text-neutral-700 leading-relaxed whitespace-pre-line">
                        {{ $material->description ?: 'Tidak ada deskripsi detail untuk materi pembelajaran ini.' }}
                    </p>
                </div>

                {{-- Area Viewer Spesifik per Jenis Materi (7 Tipe Resmi) --}}
                <div class="pt-4 border-t border-neutral-100">
                    <h3 class="font-sans font-medium text-xs text-neutral-700 uppercase tracking-normal mb-3">
                        Media &amp; Berkas Pembelajaran
                    </h3>

                    @if($material->type === 'video' && $material->drive_preview_url)
                        {{-- Video tersimpan di Google Drive: diputar langsung lewat iframe preview Drive --}}
                        <div class="space-y-2">
                            <div class="bg-neutral-900 rounded-md overflow-hidden aspect-video">
                                <iframe src="{{ $material->drive_preview_url }}"
                                        title="{{ $material->title }}"
                                        class="w-full h-full border-0"
                                        allow="autoplay; fullscreen"
                                        allowfullscreen
                                        loading="lazy"></iframe>
                            </div>
                            <div class="flex justify-end">
                                <a href="{{ $material->drive_view_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-xs font-sans font-medium text-neutral-600 hover:text-brand transition-colors">
                                    <span>Buka di Google Drive</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                    @elseif($material->type === 'video')
                        {{-- Video di luar Drive (tautan eksternal): dibuka di tab baru --}}
                        <div class="bg-neutral-900 rounded-md overflow-hidden aspect-video flex flex-col items-center justify-center text-white p-6 text-center">
                            <div class="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center mb-3">
                                <x-layout.nav-icon name="video" class="w-6 h-6 text-white" />
                            </div>
                            <p class="font-sans text-xs font-medium">Pemutar Video Pembelajaran CPS</p>
                            @if(!empty($material->content_url))
                                <a href="{{ $material->content_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-sans font-medium transition-colors">
                                    <span>Tonton Video di Tab Baru</span>
                                </a>
                            @else
                                <p class="text-[11px] text-neutral-400 mt-1">Video disematkan secara internal.</p>
                            @endif
                        </div>

                    @elseif($material->type === 'dokumen' || $material->type === 'file_pendukung' || $material->type === 'presentasi')
                        {{-- Dokumen / Presentasi / File Download Box --}}
                        <div class="border border-neutral-200 rounded-md p-4 bg-neutral-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-[2px] bg-white border border-neutral-200 text-neutral-600 flex items-center justify-center shrink-0">
                                    <x-layout.nav-icon :name="$material->type" class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-sans font-medium text-xs text-neutral-900 truncate">
                                        {{ $material->title }}
                                    </p>
                                    <p class="font-mono text-[11px] text-neutral-400 mt-0.5 uppercase">
                                        Tipe: {{ $material->type }}
                                    </p>
                                </div>
                            </div>

                            @if(!empty($material->content_url))
                                <a href="{{ $material->content_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-neutral-200 hover:border-brand hover:text-brand rounded-md text-xs font-sans font-medium text-neutral-700 transition-colors shrink-0">
                                    <span>Unduh / Buka Dokumen</span>
                                </a>
                            @else
                                <span class="text-xs font-sans text-neutral-400 italic shrink-0">
                                    Dokumen Terlampir Internal
                                </span>
                            @endif
                        </div>

                    @elseif($material->type === 'link')
                        {{-- Tautan Eksternal Box --}}
                        <div class="border border-neutral-200 rounded-md p-4 bg-neutral-50/50 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-[2px] bg-white border border-neutral-200 text-neutral-500 flex items-center justify-center shrink-0">
                                    <x-layout.nav-icon name="link" class="w-4 h-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-sans font-medium text-xs text-neutral-900">Tautan Materi Resmi</p>
                                    <p class="font-mono text-[11px] text-neutral-400 truncate mt-0.5">
                                        {{ $material->content_url ?: 'https://portal.cps.co.id/learning' }}
                                    </p>
                                </div>
                            </div>
                            <a href="{{ $material->content_url ?: '#' }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-sans font-medium transition-colors shrink-0">
                                <span>Buka Tautan</span>
                            </a>
                        </div>

                    @else
                        {{-- Artikel / Tutorial Bacaan Lengkap --}}
                        <div class="prose prose-sm max-w-none text-neutral-800 text-xs font-sans leading-relaxed bg-neutral-50/40 border border-neutral-200 rounded-badge p-4">
                            <p class="font-medium text-neutral-900 mb-2">Panduan Langkah Demi Langkah:</p>
                            <p>
                                Pelajari setiap tahapan dan prosedur operasional standar di atas secara seksama. Pastikan Anda memahami regulasi K3 dan checklist keselamatan kerja sebelum menandai materi ini telah selesai.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SISI KANAN (1 Kolom): PANEL PROGRESS & GATING POST-TEST (DoD #1 & #2) --}}
        <div class="space-y-6">
            
            {{-- 1. Kartu Progress Belajar Pengguna (DoD #1) --}}
            <div class="bg-white border border-neutral-200 rounded-md p-5 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                    <span class="font-sans font-semibold text-xs text-neutral-900 uppercase tracking-normal">
                        Progress Pembelajaran Anda
                    </span>
                    <span class="font-mono text-xs font-bold {{ $isCompleted ? 'text-brand' : 'text-neutral-700' }}">
                        {{ $progressPercent }}%
                    </span>
                </div>

                {{-- Progress Bar Tipis Hijau (#0B7840) sesuai Design System §5 --}}
                <div>
                    <div class="w-full bg-neutral-100 rounded-[2px] h-2 overflow-hidden mb-1.5">
                        <div class="bg-brand h-2 transition-all duration-300 rounded-[2px]"
                             style="width: {{ $progressPercent }}%;"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-sans text-neutral-400">
                        <span>Status:</span>
                        @if($isCompleted)
                            <span class="font-medium text-brand">Selesai (100%)</span>
                        @elseif($progressPercent > 0)
                            <span class="font-medium text-neutral-700">Sedang Dipelajari</span>
                        @else
                            <span>Belum Dimulai</span>
                        @endif
                    </div>
                </div>

                {{-- Tombol Pembaruan Progress Interaktif --}}
                <div class="pt-2 border-t border-neutral-100 space-y-2">
                    <p class="text-[11px] font-sans text-neutral-500">
                        Perbarui status pemahaman Anda terhadap materi ini:
                    </p>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                wire:click="updateProgress(50)"
                                class="px-2.5 py-1.5 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors text-center {{ $progressPercent === 50 ? 'border-brand text-brand' : '' }}">
                            Sedang Pelajari (50%)
                        </button>

                        <button type="button"
                                wire:click="markCompleted"
                                class="px-2.5 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-badge text-xs font-sans font-medium transition-colors text-center">
                            Tandai Selesai (100%)
                        </button>
                    </div>

                    @if($progressPercent > 0)
                        <button type="button"
                                wire:click="updateProgress(0)"
                                class="w-full text-center text-[11px] font-sans text-neutral-400 hover:text-neutral-700 pt-1 transition-colors">
                            Reset ke 0%
                        </button>
                    @endif
                </div>
            </div>

            {{-- 2. Kartu Post-Test (Gated: HANYA Muncul Setelah Materi Selesai 100% - DoD #2) --}}
            <div class="bg-white border {{ $isCompleted && $hasPostTest ? 'border-brand/40 bg-brand-tint/10' : 'border-neutral-200' }} rounded-md p-5 space-y-3.5">
                <div class="flex items-center justify-between pb-2.5 border-b {{ $isCompleted && $hasPostTest ? 'border-brand/20' : 'border-neutral-100' }}">
                    <div class="flex items-center gap-2">
                        <x-layout.nav-icon name="academic" class="w-4 h-4 {{ $isCompleted && $hasPostTest ? 'text-brand' : 'text-neutral-400' }}" />
                        <span class="font-sans font-semibold text-xs text-neutral-900 uppercase tracking-normal">
                            Evaluasi Post-Test
                        </span>
                    </div>

                    @if($hasPostTest)
                        <span class="px-1.5 py-0.5 border {{ $isCompleted ? 'border-brand/30 bg-brand-tint text-brand-dark' : 'border-neutral-200 bg-neutral-50 text-neutral-500' }} rounded-badge text-[10px] font-mono">
                            {{ $isCompleted ? 'Terbuka' : 'Terkunci' }}
                        </span>
                    @endif
                </div>

                @if($hasPostTest)
                    @if($isCompleted)
                        {{-- KONDISI 1: Materi Telah Selesai (100%) -> Tombol Post-Test MUNCUL (DoD #2) --}}
                        <div class="space-y-3">
                            <div class="flex items-start gap-2 text-xs font-sans text-brand-dark">
                                <x-layout.nav-icon name="badge-check" class="w-4 h-4 text-brand shrink-0 mt-0.5" />
                                <p class="leading-relaxed">
                                    Materi pembelajaran ini telah selesai! Silakan ikuti evaluasi Post-Test untuk menguji pemahaman Anda.
                                </p>
                            </div>

                            <div class="p-2.5 bg-white border border-brand/20 rounded-badge text-xs font-sans text-neutral-700">
                                <p class="font-medium text-neutral-900">{{ $postTest->title }}</p>
                                <p class="font-mono text-[11px] text-brand mt-1">
                                    Lulus dengan skor 100% menambah progres KPI periode ini
                                </p>
                            </div>

                            {{-- Tombol Lanjut ke Post-Test --}}
                            <a href="{{ route('learning.post-test', $material) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-sans font-semibold transition-colors focus:outline-none focus:ring-1 focus:ring-brand shadow-none">
                                <span>Lanjut ke Post-Test</span>
                            </a>
                        </div>
                    @else
                        {{-- KONDISI 2: Materi Belum Selesai (< 100%) -> Tombol Post-Test DIKUNCI / TERSEMBUNYI (DoD #2) --}}
                        <div class="space-y-3">
                            <div class="flex items-start gap-2 text-xs font-sans text-neutral-600 bg-neutral-50 p-3 rounded-badge border border-neutral-200">
                                <div class="w-4 h-4 text-neutral-400 shrink-0 mt-0.5">
                                    <x-layout.nav-icon name="shield-alert" class="w-4 h-4" />
                                </div>
                                <p class="leading-relaxed">
                                    <strong class="text-neutral-800">Post-Test Terkunci:</strong> Anda harus menyelesaikan materi ini (progress 100%) terlebih dahulu agar evaluasi Post-Test dapat diakses.
                                </p>
                            </div>

                            <button type="button"
                                    disabled
                                    class="w-full px-4 py-2 bg-neutral-100 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-400 cursor-not-allowed text-center select-none">
                                Selesaikan Materi untuk Membuka Post-Test
                            </button>
                        </div>
                    @endif
                @else
                    {{-- Materi tidak memiliki Post-Test --}}
                    <p class="text-xs font-sans text-neutral-500 leading-relaxed">
                        Materi pembelajaran ini tidak memiliki Post-Test. Anda cukup menyelesaikan materi untuk mencatatkan progress 100%.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
