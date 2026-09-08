<div class="max-w-4xl mx-auto space-y-6">
    {{-- Notifikasi Flash --}}
    @if (session('status'))
        <div class="p-3 bg-brand-tint border border-brand/20 rounded-md text-xs font-sans text-brand-dark flex items-center justify-between">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- =========================================================================
         1. HEADER DETAIL BERITA ACARA & STATUS BADGE KOTAK (Design System §5)
         HANYA 3 KEMUNGKINAN STATUS: Created, Reviewed, Closed
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-6 flex flex-col md:flex-row md:items-start justify-between gap-6">
        <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2 text-xs font-sans text-neutral-500">
                <a href="{{ route('ba.index') }}" class="hover:text-brand transition-colors">BA &amp; Lesson Learned</a>
                <span>&rsaquo;</span>
                <span class="font-mono text-neutral-700 font-semibold">{{ $incident->nomor_ba }}</span>
            </div>

            <div class="flex items-baseline gap-3 flex-wrap">
                <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                    {{ $incident->title ?: 'Laporan Insiden Tanpa Judul' }}
                </h1>
            </div>

            <div class="flex items-center gap-3 flex-wrap text-xs font-sans text-neutral-500 pt-1">
                {{-- Nomor BA IBM Plex Mono --}}
                <span class="font-mono text-xs px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge font-semibold text-neutral-800">
                    {{ $incident->nomor_ba }}
                </span>

                @if($incident->division)
                    <span>&middot;</span>
                    <span class="font-medium text-neutral-700">Divisi {{ $incident->division->name }}</span>
                @endif

                <span>&middot;</span>
                <span>Dilaporkan oleh {{ $incident->creator?->name ?? 'Pegawai' }}</span>

                <span>&middot;</span>
                <span class="font-mono text-neutral-400">{{ $incident->created_at->format('d M Y, H:i') }}</span>
            </div>
        </div>

        {{-- Status Badge Kotak Bersudut Tegas (HANYA 3 KEMUNGKINAN: Created, Reviewed, Closed) --}}
        <div class="shrink-0 flex flex-col items-start md:items-end gap-3">
            <div>
                <span class="text-[10px] font-sans text-neutral-400 block md:text-right uppercase tracking-wider mb-1">
                    Status BA
                </span>
                @switch($incident->status)
                    @case('created')
                        {{-- Status 1: Created --}}
                        <span class="inline-flex items-center px-3 py-1 border border-neutral-300 rounded-badge font-mono text-xs font-medium text-neutral-600 bg-white"
                              title="Menunggu peninjauan Supervisor">
                            Created
                        </span>
                        @break

                    @case('reviewed')
                        {{-- Status 2: Reviewed --}}
                        <span class="inline-flex items-center px-3 py-1 border border-neutral-600 rounded-badge font-mono text-xs font-medium text-neutral-900 bg-white"
                              title="Telah ditinjau Supervisor">
                            Reviewed
                        </span>
                        @break

                    @case('closed')
                        {{-- Status 3: Closed --}}
                        <span class="inline-flex items-center px-3 py-1 border border-brand rounded-badge font-mono text-xs font-medium text-brand-dark bg-brand-tint/40"
                              title="Laporan telah diselesaikan dan ditutup">
                            Closed
                        </span>
                        @break
                @endswitch
            </div>

            {{-- Tombol Aksi Otorisasi Supervisor Divisi yang Sama --}}
            @if($this->canApprove)
                <div class="flex items-center gap-2 pt-2 border-t border-neutral-100">
                    <button type="button"
                            wire:click="$toggle('showRevisionModal')"
                            class="px-3 py-1.5 border border-neutral-300 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                        Minta Revisi
                    </button>

                    <button type="button"
                            wire:click="approve"
                            wire:confirm="Konfirmasi: Anda akan menyetujui Berita Acara ini dan menerbitkan Lesson Learned baru?"
                            class="px-3 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-badge text-xs font-sans font-medium transition-colors focus:outline-none focus:ring-1 focus:ring-brand">
                        Setujui BA (Approve)
                    </button>
                </div>
            @elseif($this->canClose)
                <div class="pt-2 border-t border-neutral-100">
                    <button type="button"
                            wire:click="closeIncident"
                            wire:confirm="Konfirmasi: Anda akan menutup laporan Berita Acara ini?"
                            class="px-3 py-1.5 bg-neutral-900 hover:bg-black text-white rounded-badge text-xs font-sans font-medium transition-colors">
                        Tutup Laporan (Close)
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Catatan Revisi --}}
    @if($showRevisionModal)
        <div class="bg-neutral-50 border border-neutral-200 rounded-md p-4 space-y-3">
            <h3 class="font-sans font-semibold text-xs text-neutral-900">
                Catatan Revisi dari Supervisor Divisi {{ $incident->division?->name }}
            </h3>
            <textarea wire:model="revisionNote"
                      rows="3"
                      placeholder="Tuliskan aspek teknis yang perlu diperbaiki oleh pelapor..."
                      class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-badge text-xs font-sans text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand"></textarea>
            @error('revisionNote')
                <span class="text-[11px] font-sans text-neutral-600 block">{{ $message }}</span>
            @enderror

            <div class="flex items-center justify-end gap-2">
                <button type="button"
                        wire:click="$set('showRevisionModal', false)"
                        class="px-3 py-1 text-xs font-sans border border-neutral-200 rounded-badge text-neutral-600 bg-white hover:bg-neutral-50">
                    Batal
                </button>
                <button type="button"
                        wire:click="submitRevision"
                        class="px-3 py-1 text-xs font-sans bg-neutral-800 text-white rounded-badge hover:bg-neutral-900">
                    Kirim Catatan
                </button>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         2. KRONOLOGI KEJADIAN & INFORMASI INSIDEN
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
        <h2 class="font-sans font-semibold text-sm text-neutral-900 border-b border-neutral-100 pb-2">
            Kronologi &amp; Rincian Kejadian
        </h2>

        <div class="font-sans text-xs text-neutral-700 leading-relaxed whitespace-pre-line">
            {{ $incident->description ?: 'Tidak ada rincian kronologi yang dicantumkan.' }}
        </div>
    </div>

    {{-- =========================================================================
         3. DUA FILE UPLOAD TERPISAH (Dokumen BA dan Formulir FTK)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
        <div>
            <h2 class="font-sans font-semibold text-sm text-neutral-900">
                Berkas Terlampir (2 Slot Terpisah)
            </h2>
            <p class="font-sans text-xs text-neutral-500 mt-0.5">
                Dokumen Berita Acara dan Formulir FTK yang tersimpan via Spatie MediaLibrary.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Slot 1: Berkas BA --}}
            <div class="border border-neutral-200 rounded-md p-4 bg-neutral-50/40 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-badge border border-neutral-200 bg-white flex items-center justify-center text-neutral-500 shrink-0 mt-0.5">
                        <x-layout.nav-icon name="dokumen" class="w-4 h-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="font-sans font-medium text-xs text-neutral-900">
                            Berkas Dokumen BA
                        </p>
                        <p class="font-sans text-[11px] text-neutral-400 truncate mt-0.5">
                            Surat Berita Acara Resmi
                        </p>
                    </div>
                </div>

                <div class="shrink-0">
                    @if(!empty($incident->file_ba_url))
                        <a href="{{ $incident->file_ba_url }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center px-2.5 py-1 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                            Lihat Berkas &nearr;
                        </a>
                    @else
                        <span class="text-[11px] font-sans text-neutral-400 italic">Tidak ada berkas</span>
                    @endif
                </div>
            </div>

            {{-- Slot 2: Berkas FTK --}}
            <div class="border border-neutral-200 rounded-md p-4 bg-neutral-50/40 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-badge border border-neutral-200 bg-white flex items-center justify-center text-neutral-500 shrink-0 mt-0.5">
                        <x-layout.nav-icon name="sop" class="w-4 h-4" />
                    </div>
                    <div class="min-w-0">
                        <p class="font-sans font-medium text-xs text-neutral-900">
                            Berkas Formulir FTK
                        </p>
                        <p class="font-sans text-[11px] text-neutral-400 truncate mt-0.5">
                            Formulir Analisis Faktor Teknis
                        </p>
                    </div>
                </div>

                <div class="shrink-0">
                    @if(!empty($incident->file_ftk_url))
                        <a href="{{ $incident->file_ftk_url }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center px-2.5 py-1 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                            Lihat Berkas &nearr;
                        </a>
                    @else
                        <span class="text-[11px] font-sans text-neutral-400 italic">Tidak ada berkas</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Lesson Learned Banner jika BA telah di-approve --}}
    @if($incident->lessonLearned)
        <div class="bg-brand-tint/30 border border-brand/20 rounded-md p-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-badge bg-white border border-brand/20 text-brand flex items-center justify-center shrink-0">
                    <x-layout.nav-icon name="lesson_learned" class="w-4 h-4 text-brand" />
                </div>
                <div>
                    <h3 class="font-sans font-medium text-xs text-neutral-900">
                        Lesson Learned Terbit Otomatis
                    </h3>
                    <p class="font-sans text-[11px] text-neutral-600 mt-0.5">
                        {{ $incident->lessonLearned->title }}
                    </p>
                </div>
            </div>

            <a href="{{ route('knowledge.index', ['search' => $incident->nomor_ba]) }}"
               class="shrink-0 text-xs font-sans font-medium text-brand hover:underline">
                Buka di Knowledge Hub &rarr;
            </a>
        </div>
    @endif

    {{-- =========================================================================
         4. UPDATE HISTORY (TIMELINE VERTICAL TIPIS + TITIK BULAT NETRAL)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
        <h2 class="font-sans font-semibold text-sm text-neutral-900 border-b border-neutral-100 pb-2">
            Update History (Riwayat Aktivitas)
        </h2>

        @if($incident->activityLogs->isEmpty())
            <p class="font-sans text-xs text-neutral-400 italic py-2">
                Belum ada riwayat aktivitas tercatat.
            </p>
        @else
            {{-- Timeline Sederhana: Garis Vertikal Tipis + Titik --}}
            <div class="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-px before:bg-neutral-200">
                @foreach($incident->activityLogs as $log)
                    <div class="relative group">
                        {{-- Titik Bullet Netral --}}
                        <div class="absolute -left-6 top-1.5 w-2 h-2 rounded-full bg-neutral-400 border-2 border-white ring-1 ring-neutral-200"></div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-sans font-medium text-xs text-neutral-900">
                                    {{ $log->action }}
                                </span>
                                <span>&middot;</span>
                                <span class="font-mono text-[11px] text-neutral-400">
                                    {{ $log->created_at->format('d M Y, H:i') }}
                                </span>
                            </div>

                            @if(!empty($log->note))
                                <p class="font-sans text-xs text-neutral-600 bg-neutral-50/70 p-2 border border-neutral-100 rounded-badge">
                                    {{ $log->note }}
                                </p>
                            @endif

                            <div class="text-[10px] font-sans text-neutral-400">
                                Dicatat oleh <span class="text-neutral-600 font-medium">{{ $log->actor?->name ?? 'Sistem' }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
