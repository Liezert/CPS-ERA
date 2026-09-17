<div class="max-w-4xl mx-auto space-y-6">
    {{-- Notifikasi Flash --}}
    @if (session('status'))
        <div class="p-3 bg-brand-tint border border-brand/20 rounded-md text-xs font-sans text-brand-dark flex items-center justify-between">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Banner Khusus Jika BA Ditolak / Memerlukan Revisi --}}
    @if ($incident->status === 'rejected')
        <div class="p-4 bg-red-50/90 border border-red-200 rounded-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-5 h-5 text-red-600 shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="font-bold text-red-900">Laporan BA Ini Ditolak / Perlu Revisi</div>
                    <p class="text-red-700 leading-relaxed">
                        <strong>Catatan Reviewer:</strong> {{ $incident->catatan_penolakan ?: 'Harap lengkapi dan perbaiki data analisa sebelum diserahkan kembali.' }}
                    </p>
                </div>
            </div>

            @if ($this->canEdit)
                <a href="{{ route('ba.create', ['incidentId' => $incident->id]) }}"
                   class="inline-flex items-center justify-center px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-medium shrink-0 transition-colors shadow-xs">
                    <span>Edit Ulang &amp; Resubmit BA</span>
                </a>
            @endif
        </div>
    @endif

    {{-- =========================================================================
         1. HEADER DETAIL BERITA ACARA & STATUS BADGE
         ========================================================================= --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-6 flex flex-col md:flex-row md:items-start justify-between gap-6">
        <div class="space-y-2 flex-1">
            <div class="flex items-center gap-2 text-xs font-sans text-neutral-600 font-medium">
                <a href="{{ route('ba.index') }}" class="hover:text-brand transition-colors">Laporan CAPA</a>
                <span>&rsaquo;</span>
                <span class="font-mono text-neutral-800 font-semibold">{{ $incident->nomor_ba }}</span>
            </div>

            <div class="flex items-baseline gap-3 flex-wrap">
                <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                    {{ $incident->title ?: 'Formulir CAPA: '.$incident->nomor_ba }}
                </h1>
            </div>

            <div class="flex items-center gap-3 flex-wrap text-xs font-sans text-neutral-600 pt-1">
                {{-- Nomor BA IBM Plex Mono --}}
                <span class="font-mono text-xs px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge font-semibold text-neutral-800">
                    {{ $incident->nomor_ba }}
                </span>

                @if($incident->division)
                    <span>&middot;</span>
                    <span class="font-medium text-neutral-800">Divisi {{ $incident->division->name }}</span>
                @endif

                <span>&middot;</span>
                <span>Dilaporkan oleh {{ $incident->creator?->name ?? 'Pegawai' }}</span>

                <span>&middot;</span>
                <span class="font-mono text-neutral-500">{{ $incident->created_at->format('d M Y, H:i') }}</span>
            </div>
        </div>

        {{-- Status Badge & Action Buttons --}}
        <div class="shrink-0 flex flex-col items-start md:items-end gap-3">
            <div>
                <span class="text-[10px] font-sans text-neutral-600 block md:text-right font-semibold uppercase tracking-wider mb-1">
                    Status BA
                </span>
                @switch($incident->status)
                    @case('draft')
                        <span class="inline-flex items-center px-3 py-1 border border-neutral-300 rounded-badge font-mono text-xs font-medium text-neutral-600 bg-white" title="Tersimpan sebagai draft">
                            Draft
                        </span>
                        @break

                    @case('submitted')
                    @case('created')
                        <span class="inline-flex items-center px-3 py-1 border border-amber-500 rounded-badge font-mono text-xs font-medium text-amber-900 bg-amber-50/50" title="Menunggu peninjauan reviewer">
                            {{ $incident->status === 'submitted' ? 'Submitted' : 'Created' }}
                        </span>
                        @break

                    @case('approved')
                    @case('reviewed')
                    @case('closed')
                        <span class="inline-flex items-center px-3 py-1 border border-brand rounded-badge font-mono text-xs font-medium text-brand-dark bg-brand-tint/40" title="Telah diverifikasi & disetujui">
                            {{ $incident->status === 'approved' ? 'Approved' : ucfirst($incident->status) }}
                        </span>
                        @break

                    @case('rejected')
                        <span class="inline-flex items-center px-3 py-1 border border-red-500 rounded-badge font-mono text-xs font-medium text-red-700 bg-red-50/50" title="Perlu perbaikan">
                            Rejected
                        </span>
                        @break

                    @default
                        <span class="inline-flex items-center px-3 py-1 border border-neutral-200 rounded-badge font-mono text-xs font-medium text-neutral-700 bg-white">
                            {{ ucfirst($incident->status) }}
                        </span>
                @endswitch
            </div>

            {{-- Tombol Aksi Otorisasi Reviewer (Admin atau Atasan Divisi yang Sama) --}}
            @if($this->canApprove)
                <div class="flex items-center gap-2 pt-2 border-t border-neutral-100">
                    <button type="button"
                            wire:click="openRejectModal"
                            class="px-3 py-1.5 border border-red-300 hover:bg-red-50 text-red-700 rounded-md text-xs font-sans font-medium transition-colors">
                        Minta Revisi
                    </button>

                    <button type="button"
                            wire:click="openApproveModal"
                            class="px-3.5 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-sans font-medium transition-colors shadow-xs">
                        Setujui BA (Approve)
                    </button>
                </div>
            @elseif($this->canEdit)
                <div class="pt-2 border-t border-neutral-100">
                    <a href="{{ route('ba.create', ['incidentId' => $incident->id]) }}"
                       class="px-3 py-1.5 border border-neutral-300 rounded-md text-xs font-sans font-medium text-neutral-800 bg-white hover:bg-neutral-50 transition-colors">
                        Edit Ulang Data
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- =========================================================================
         2. KONTEN FORMULIR DIGITAL CAPA/FTK
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-6">
        
        {{-- Section Header Dokumen --}}
        <div class="flex items-center justify-between pb-3 border-b border-neutral-200">
            <div>
                <h2 class="text-sm font-bold text-neutral-900">
                    Rincian Formulir Tindakan Koreksi &amp; Korektif (CAPA)
                </h2>
            </div>
            <div class="text-right text-xs text-neutral-600">
                <span>Tanggal Pengisian: </span>
                <strong class="font-mono text-neutral-900">{{ $incident->tanggal_pengisian ? $incident->tanggal_pengisian->format('d M Y') : $incident->created_at->format('d M Y') }}</strong>
            </div>
        </div>

        {{-- Meta Grid: Sumber, Tanggal Masalah, Lokasi --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-neutral-50/70 border border-neutral-200 rounded-md p-4">
            <div>
                <span class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">
                    Sumber Ketidaksesuaian
                </span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-badge text-xs font-semibold bg-white border border-neutral-300 text-neutral-900">
                    {{ \App\Models\BaIncident::SUMBER_OPTIONS[$incident->sumber_ketidaksesuaian] ?? ucfirst(str_replace('_', ' ', (string) $incident->sumber_ketidaksesuaian)) }}
                </span>
                @if($incident->sumber_ketidaksesuaian_lainnya)
                    <p class="text-[11px] text-neutral-600 mt-1 italic">
                        "{{ $incident->sumber_ketidaksesuaian_lainnya }}"
                    </p>
                @endif
            </div>

            <div>
                <span class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">
                    Tanggal Terjadi Masalah
                </span>
                <span class="font-mono text-xs font-bold text-neutral-900 block">
                    {{ $incident->tanggal_masalah ? $incident->tanggal_masalah->format('d M Y') : '-' }}
                </span>
            </div>

            <div>
                <span class="text-[11px] font-medium text-neutral-500 uppercase tracking-wider block mb-1">
                    Lokasi / Tempat Kejadian
                </span>
                <span class="text-xs font-semibold text-neutral-900 block">
                    {{ $incident->lokasi ?: '-' }}
                </span>
            </div>
        </div>

        {{-- Uraian Masalah --}}
        <div class="space-y-1.5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-700 font-sans">
                Uraian Masalah / Ketidaksesuaian
            </h3>
            <div class="p-4 bg-neutral-50 border border-neutral-200 rounded-md text-xs sm:text-sm text-neutral-800 leading-relaxed whitespace-pre-line">
                {{ $incident->deskripsi_masalah ?: ($incident->description ?: 'Tidak ada deskripsi rinci.') }}
            </div>
        </div>

        {{-- Analisis 5 Whys --}}
        <div class="border border-neutral-200 rounded-md p-4 space-y-3">
            <div class="flex items-center justify-between pb-2 border-b border-neutral-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-sans">
                    Analisis Akar Masalah (5 Whys)
                </h3>
                <span class="text-[10px] font-mono text-neutral-500 bg-neutral-100 px-1.5 py-0.5 rounded border border-neutral-200">
                    Investigasi Kausalitas
                </span>
            </div>

            <div class="space-y-2 text-xs">
                @if($incident->why_1)
                    <div class="flex items-start gap-2">
                        <span class="font-mono font-bold text-neutral-500 shrink-0 w-16">Why 1:</span>
                        <p class="text-neutral-800 font-medium">{{ $incident->why_1 }}</p>
                    </div>
                @endif
                @if($incident->why_2)
                    <div class="flex items-start gap-2">
                        <span class="font-mono font-bold text-neutral-500 shrink-0 w-16">Why 2:</span>
                        <p class="text-neutral-700">{{ $incident->why_2 }}</p>
                    </div>
                @endif
                @if($incident->why_3)
                    <div class="flex items-start gap-2">
                        <span class="font-mono font-bold text-neutral-500 shrink-0 w-16">Why 3:</span>
                        <p class="text-neutral-700">{{ $incident->why_3 }}</p>
                    </div>
                @endif
                @if($incident->why_4)
                    <div class="flex items-start gap-2">
                        <span class="font-mono font-bold text-neutral-500 shrink-0 w-16">Why 4:</span>
                        <p class="text-neutral-700">{{ $incident->why_4 }}</p>
                    </div>
                @endif
                @if($incident->why_5)
                    <div class="flex items-start gap-2">
                        <span class="font-mono font-bold text-neutral-500 shrink-0 w-16">Why 5:</span>
                        <p class="text-neutral-700">{{ $incident->why_5 }}</p>
                    </div>
                @endif
            </div>

            <div class="pt-3 border-t border-neutral-200 mt-2">
                <span class="text-[11px] font-bold text-neutral-900 uppercase tracking-wider block mb-1">
                    Kesimpulan Akar Masalah:
                </span>
                <p class="text-xs text-neutral-800 font-medium bg-neutral-50 p-2.5 rounded border border-neutral-200">
                    {{ $incident->kesimpulan_akar_masalah ?: '-' }}
                </p>
            </div>
        </div>

        {{-- Tindakan Koreksi & Korektif --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Tindakan Koreksi (Sementara) --}}
            <div class="border border-neutral-200 rounded-md p-4 space-y-2 bg-white">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <h4 class="text-xs font-bold text-neutral-900 uppercase tracking-wider">
                        Tindakan Koreksi (Sementara)
                    </h4>
                </div>
                <p class="text-xs text-neutral-800 leading-relaxed min-h-[48px]">
                    {{ $incident->koreksi_deskripsi ?: '-' }}
                </p>
                <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500">
                    <span>PIC: <strong class="text-neutral-700">{{ $incident->koreksi_pic ?: '-' }}</strong></span>
                    <span>Waktu: <strong class="text-neutral-700">{{ $incident->koreksi_waktu ?: '-' }}</strong></span>
                </div>
            </div>

            {{-- Tindakan Korektif (Akar Masalah) --}}
            <div class="border border-neutral-200 rounded-md p-4 space-y-2 bg-white">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-brand"></span>
                    <h4 class="text-xs font-bold text-neutral-900 uppercase tracking-wider">
                        Tindakan Korektif (Akar Masalah)
                    </h4>
                </div>
                <p class="text-xs text-neutral-800 leading-relaxed min-h-[48px]">
                    {{ $incident->korektif_deskripsi ?: '-' }}
                </p>
                <div class="pt-2 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500">
                    <span>PIC: <strong class="text-neutral-700">{{ $incident->korektif_pic ?: '-' }}</strong></span>
                    <span>Waktu: <strong class="text-neutral-700">{{ $incident->korektif_waktu ?: '-' }}</strong></span>
                </div>
            </div>
        </div>

        {{-- Dampak Lanjutan: Potensi Risiko & Peluang --}}
        <div class="flex flex-wrap items-center gap-3 pt-2">
            @if($incident->is_potensi_risiko)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[2px] bg-red-50 text-red-800 border border-red-200 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                    Potensi Risiko Signifikan
                </span>
            @endif
            @if($incident->is_potensi_peluang)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-[2px] bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Potensi Peluang Improvement
                </span>
            @endif
        </div>
    </div>

    {{-- =========================================================================
         3. DOKUMENTASI VIDEO PENANGANAN & BUKTI (LANGKAH 2)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-200">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-sans">
                    Dokumentasi Video Bukti &amp; Penanganan
                </h3>
                <p class="text-[11px] text-neutral-600">
                    Bukti rekaman kondisi lapangan atau tutorial penanganan masalah.
                </p>
            </div>
            @if($incident->video)
                <span class="inline-flex items-center px-2 py-0.5 rounded-badge text-[10px] font-mono font-semibold bg-brand-tint text-brand-dark border border-brand/30">
                    Video Terlampir
                </span>
            @else
                <span class="inline-flex items-center px-2 py-0.5 rounded-badge text-[10px] font-mono text-neutral-500 bg-neutral-100 border border-neutral-200">
                    Belum Dilampirkan
                </span>
            @endif
        </div>

        @if($incident->video)
            <div class="bg-neutral-50 rounded-md p-4 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h4 class="text-xs font-semibold text-neutral-900">
                        {{ $incident->video->title }}
                    </h4>
                    @if($incident->video->video_external_link)
                        <a href="{{ $incident->video->video_external_link }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-1 text-xs text-brand font-medium hover:underline">
                            <span>Buka Tautan Eksternal</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                </div>

                @if($incident->video->video_file_url)
                    <div class="aspect-video bg-black rounded overflow-hidden max-w-lg mx-auto">
                        <video controls class="w-full h-full object-contain">
                            <source src="{{ asset($incident->video->video_file_url) }}" type="video/mp4">
                            Browser Anda tidak mendukung pemutar video HTML5.
                        </video>
                    </div>
                @elseif($incident->video->video_external_link)
                    <div class="p-3 bg-white border border-neutral-200 rounded flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-neutral-100 flex items-center justify-center text-neutral-600 shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[11px] text-neutral-500 font-sans">Tautan Video:</div>
                            <a href="{{ $incident->video->video_external_link }}" target="_blank" class="text-xs font-mono text-brand truncate block hover:underline">
                                {{ $incident->video->video_external_link }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-6 text-xs text-neutral-500 bg-neutral-50 rounded-md border border-dashed border-neutral-300">
                Tidak ada video yang dilampirkan pada pelaporan ini.
            </div>
        @endif
    </div>

    {{-- =========================================================================
         4. HASIL VERIFIKASI TINDAKAN KOREKTIF (DITAMPILKAN JIKA SUDAH DIREVIEW)
         ========================================================================= --}}
    @if($incident->status_verifikasi || $incident->reviewed_by)
        <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-200">
                <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-sans">
                    Hasil Verifikasi Tindakan Korektif (Reviewer)
                </h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-badge text-xs font-mono font-bold {{ $incident->status_verifikasi === 'efektif' ? 'bg-brand-tint text-brand-dark border border-brand/40' : 'bg-red-50 text-red-800 border border-red-300' }}">
                    {{ $incident->status_verifikasi === 'efektif' ? '✓ DIVERIFIKASI EFEKTIF' : '✗ TIDAK EFEKTIF' }}
                </span>
            </div>

            <div class="p-4 bg-neutral-50/70 border border-neutral-200 rounded-md space-y-3">
                @if($incident->status_verifikasi === 'efektif')
                    <div>
                        <span class="text-[11px] font-bold text-neutral-700 uppercase tracking-wider block mb-1">
                            Bukti Objektif Efektivitas:
                        </span>
                        <p class="text-xs text-neutral-900 leading-relaxed font-medium bg-white p-3 rounded border border-neutral-200">
                            {{ $incident->bukti_objektif ?: 'Diverifikasi efektif sesuai standar toleransi operasional pabrik.' }}
                        </p>
                    </div>
                @else
                    <div>
                        <span class="text-[11px] font-bold text-red-800 uppercase tracking-wider block mb-1">
                            Alasan Ketidakefektifan:
                        </span>
                        <p class="text-xs text-red-900 leading-relaxed font-medium bg-white p-3 rounded border border-red-200">
                            {{ $incident->alasan_tidak_efektif ?: 'Penyelesaian belum memenuhi kriteria penerimaan mutu.' }}
                        </p>
                    </div>
                @endif

                <div class="pt-2 border-t border-neutral-200 flex flex-wrap items-center justify-between text-xs text-neutral-600">
                    <span>Diverifikasi oleh: <strong class="text-neutral-900">{{ $incident->reviewer?->name ?? 'Supervisor/Admin' }}</strong></span>
                    <span class="font-mono">{{ $incident->reviewed_at ? $incident->reviewed_at->format('d M Y H:i') : '-' }}</span>
                </div>
            </div>

            {{-- Link ke Lesson Learned jika sudah terbit --}}
            @if($incident->lessonLearned)
                <div class="p-3 bg-brand-tint/20 border border-brand/30 rounded-md flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        <span class="text-neutral-800">Materi <strong>Lesson Learned</strong> resmi diterbitkan ke Knowledge Hub.</span>
                    </div>
                    <a href="{{ route('knowledge.index') }}" class="text-brand font-semibold hover:underline">
                        Lihat di Knowledge Repository &rarr;
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- =========================================================================
         5. UPDATE HISTORY (RIWAYAT AKTIVITAS)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-neutral-200">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-sans">
                    Update History (Riwayat Aktivitas)
                </h3>
                <p class="text-[11px] text-neutral-600">
                    Audit trail dan lini masa perjalanan status dokumen Berita Acara.
                </p>
            </div>
            <span class="text-[10px] font-mono text-neutral-500">
                {{ $incident->activityLogs->count() }} Aktivitas
            </span>
        </div>

        <div class="relative pl-6 space-y-6 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-neutral-200">
            @forelse($incident->activityLogs as $log)
                <div class="relative group">
                    <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-brand border-2 border-white ring-2 ring-neutral-200"></span>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold text-neutral-900">{{ $log->action }}</span>
                            <span class="text-[11px] text-neutral-500">&middot;</span>
                            <span class="text-[11px] text-neutral-600">{{ $log->actor?->name ?? 'Sistem' }}</span>
                            <span class="text-[11px] text-neutral-400 font-mono">{{ $log->created_at->format('d M Y H:i') }}</span>
                        </div>
                        @if($log->note)
                            <p class="text-xs text-neutral-700 bg-neutral-50 p-2.5 rounded border border-neutral-200">
                                {{ $log->note }}
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-xs text-neutral-500 py-3">
                    Belum ada riwayat aktivitas tercatat.
                </div>
            @endforelse
        </div>
    </div>

    {{-- =========================================================================
         6. MODAL VERIFIKASI PERSETUJUAN (APPROVE DENGAN VERIFIKASI EFEKTIF/TIDAK)
         ========================================================================= --}}
    @if($showApproveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-lg w-full p-6 space-y-4 shadow-xl border border-neutral-200">
                <div class="flex items-center justify-between pb-2 border-b border-neutral-200">
                    <h3 class="text-base font-bold text-neutral-900">
                        Verifikasi &amp; Setujui Berita Acara
                    </h3>
                    <button type="button" wire:click="closeApproveModal" class="text-neutral-400 hover:text-neutral-600">
                        &times;
                    </button>
                </div>

                <p class="text-xs text-neutral-600 leading-relaxed">
                    Reviewer wajib memverifikasi efektivitas tindakan korektif sebelum menyetujui laporan ini.
                </p>

                <div class="space-y-3">
                    {{-- Status Verifikasi --}}
                    <div>
                        <label class="block text-xs font-semibold text-neutral-800 mb-1">
                            Status Verifikasi Hasil <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 p-2.5 border rounded cursor-pointer {{ $statusVerifikasi === 'efektif' ? 'border-brand bg-brand-tint/20 ring-1 ring-brand' : 'border-neutral-200' }}">
                                <input type="radio" wire:model.live="statusVerifikasi" value="efektif" class="text-brand" />
                                <span class="text-xs font-medium text-neutral-800">Efektif</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 border rounded cursor-pointer {{ $statusVerifikasi === 'tidak_efektif' ? 'border-red-500 bg-red-50 ring-1 ring-red-500' : 'border-neutral-200' }}">
                                <input type="radio" wire:model.live="statusVerifikasi" value="tidak_efektif" class="text-red-600" />
                                <span class="text-xs font-medium text-neutral-800">Tidak Efektif</span>
                            </label>
                        </div>
                    </div>

                    {{-- Bukti Objektif (Jika Efektif) --}}
                    @if($statusVerifikasi === 'efektif')
                        <div>
                            <label for="bukti_objektif" class="block text-xs font-semibold text-neutral-800 mb-1">
                                Bukti Objektif Efektivitas <span class="text-red-500">*</span>
                            </label>
                            <textarea id="bukti_objektif"
                                      wire:model.live="buktiObjektif"
                                      rows="3"
                                      placeholder="Sebutkan data pengukuran, hasil inspeksi QC, atau uji jalan mesin setelah perbaikan..."
                                      class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-md text-xs font-sans text-neutral-900 focus:ring-1 focus:ring-brand"></textarea>
                            @error('buktiObjektif')
                                <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    {{-- Alasan (Jika Tidak Efektif) --}}
                    @if($statusVerifikasi === 'tidak_efektif')
                        <div>
                            <label for="alasan_tidak_efektif" class="block text-xs font-semibold text-neutral-800 mb-1">
                                Alasan Ketidakefektifan <span class="text-red-500">*</span>
                            </label>
                            <textarea id="alasan_tidak_efektif"
                                      wire:model.live="alasanTidakEfektif"
                                      rows="3"
                                      placeholder="Jelaskan parameter yang masih belum sesuai atau mengapa tindakan korektif belum menyelesaikan masalah..."
                                      class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-md text-xs font-sans text-neutral-900 focus:ring-1 focus:ring-red-500"></textarea>
                            @error('alasanTidakEfektif')
                                <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif
                </div>

                <div class="pt-3 border-t border-neutral-200 flex items-center justify-end gap-2">
                    <button type="button"
                            wire:click="closeApproveModal"
                            class="px-3 py-1.5 border border-neutral-200 rounded-md text-xs text-neutral-700 bg-white hover:bg-neutral-50">
                        Batal
                    </button>
                    <button type="button"
                            wire:click="confirmApprove"
                            class="px-4 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-medium transition-colors shadow-xs">
                        Konfirmasi Setujui &amp; Terbitkan Lesson Learned
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================================
         6. MODAL PENOLAKAN (REJECT DENGAN CATATAN REVISI)
         ========================================================================= --}}
    @if($showRejectModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6 space-y-4 shadow-xl border border-neutral-200">
                <div class="flex items-center justify-between pb-2 border-b border-neutral-200">
                    <h3 class="text-base font-bold text-red-700">
                        Tolak / Minta Revisi Laporan BA
                    </h3>
                    <button type="button" wire:click="closeRejectModal" class="text-neutral-400 hover:text-neutral-600">
                        &times;
                    </button>
                </div>

                <p class="text-xs text-neutral-600 leading-relaxed">
                    Cantumkan alasan penolakan atau instruksi perbaikan yang harus dipenuhi oleh pembuat laporan.
                </p>

                <div>
                    <label for="rejection_reason" class="block text-xs font-semibold text-neutral-800 mb-1">
                        Catatan Alasan Penolakan / Revisi <span class="text-red-500">*</span>
                    </label>
                    <textarea id="rejection_reason"
                              wire:model.live="rejectionReason"
                              rows="3"
                              placeholder="Jelaskan kekurangan analisa 5 Whys atau tindakan korektif yang perlu dilengkapi..."
                              class="w-full px-3 py-2 bg-white border border-neutral-200 rounded-md text-xs font-sans text-neutral-900 focus:ring-1 focus:ring-red-500"></textarea>
                    @error('rejectionReason')
                        <span class="text-[11px] text-red-600 mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="pt-3 border-t border-neutral-200 flex items-center justify-end gap-2">
                    <button type="button"
                            wire:click="closeRejectModal"
                            class="px-3 py-1.5 border border-neutral-200 rounded-md text-xs text-neutral-700 bg-white hover:bg-neutral-50">
                        Batal
                    </button>
                    <button type="button"
                            wire:click="confirmReject"
                            class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-medium transition-colors shadow-xs">
                        Kirim Penolakan
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
