<div class="max-w-4xl mx-auto space-y-6">
    {{-- Notifikasi Flash --}}
    @if (session('status'))
        <div class="p-3 bg-brand-tint border border-brand/20 rounded-md text-xs font-sans text-brand-dark flex items-center justify-between">
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Banner Khusus Jika BA Ditolak / Memerlukan Revisi --}}
    @if ($incident->isRevisionRequested() || $incident->isRejected())
        <div class="p-4 bg-red-50/90 border border-red-200 rounded-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="w-5 h-5 text-red-600 shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="space-y-1 text-xs">
                    <div class="font-bold text-red-900">{{ $incident->isRejected() ? 'Laporan BA Ini Ditolak Permanen oleh HR' : 'Laporan BA Ini Perlu Revisi' }}</div>
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

                    @case('pending_supervisor')
                    @case('pending_hr')
                        <span class="inline-flex items-center px-3 py-1 border border-amber-500 rounded-badge font-mono text-xs font-medium text-amber-900 bg-amber-50/50" title="Menunggu review">
                            {{ \App\Enums\BaIncidentStatus::from($incident->status)->getLabel() }}
                        </span>
                        @break

                    @case('revision_requested')
                        <span class="inline-flex items-center px-3 py-1 border border-red-500 rounded-badge font-mono text-xs font-medium text-red-700 bg-red-50/50" title="Dikembalikan Supervisor untuk diperbaiki">
                            Perlu Revisi
                        </span>
                        @break

                    @case('rejected')
                        <span class="inline-flex items-center px-3 py-1 border border-red-500 rounded-badge font-mono text-xs font-medium text-red-700 bg-red-50/50" title="Ditolak permanen oleh HR">
                            Ditolak HR
                        </span>
                        @break

                    @default
                        <span class="inline-flex items-center px-3 py-1 border border-neutral-200 rounded-badge font-mono text-xs font-medium text-neutral-700 bg-white">
                            {{ ucfirst($incident->status) }}
                        </span>
                @endswitch
            </div>

            {{-- Approve/reject hanya lewat panel Filament; reviewer diarahkan ke halaman review di sana. --}}
            @if($this->canOpenReviewPanel)
                <div class="pt-2 border-t border-neutral-100">
                    <a href="{{ route('filament.admin.resources.ba-incidents.view', $incident) }}"
                       class="px-3.5 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-sans font-medium transition-colors shadow-xs">
                        Review di Panel Admin
                    </a>
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
         2. FORMULIR CAPA/FTK — identik dengan form pengisian employee
            (komponen bersama components/capa/form/*, mode readonly)
         ========================================================================= --}}
    <div x-data="{
            visibleWhys: 1,
            copiedBa: false,
            copyBaNumber() {
                navigator.clipboard.writeText('{{ $incident->nomor_ba }}');
                this.copiedBa = true;
                setTimeout(() => { this.copiedBa = false; }, 2000);
            }
        }"
        class="bg-white border border-neutral-200 rounded-md p-5 sm:p-7 space-y-8 shadow-2xs">

        <x-capa.form.header :values="$capa" :readonly="true" />

        <x-capa.form.dokumen :values="$capa" :divisions="$divisions" :readonly="true" />

        <x-capa.form.sumber :values="$capa" :readonly="true" />

        <x-capa.form.kejadian :values="$capa" :readonly="true" />

        <x-capa.form.akar-masalah :values="$capa" :readonly="true" />

        <x-capa.form.rencana-penanganan :values="$capa" :readonly="true" />

        <x-capa.form.dampak :values="$capa" :readonly="true" />
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

        {{-- Video Player (Shared Component) --}}
        <x-capa.video-player :incident="$incident" />
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

            {{-- Materi Learning hasil laporan: terbit setelah HRGA membuat post-test --}}
            @if($material = $incident->learningMaterial)
                <div class="p-3 bg-brand-tint/20 border border-brand/30 rounded-md flex items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                        @if($material->status === 'published')
                            <span class="text-neutral-800">Hasil laporan ini sudah terbit sebagai materi <strong>Learning</strong> beserta post-test.</span>
                        @else
                            <span class="text-neutral-800">Hasil laporan ini akan terbit di <strong>Learning</strong> setelah HRGA menyiapkan post-test.</span>
                        @endif
                    </div>
                    @if($material->status === 'published')
                        <a href="{{ route('learning.show', $material) }}" class="text-brand font-semibold hover:underline shrink-0">
                            Buka di Learning &rarr;
                        </a>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- =========================================================================
         5. UPDATE HISTORY (RIWAYAT AKTIVITAS) — internal, hanya untuk reviewer
         ========================================================================= --}}
    @if ($this->canViewActivityLog)
        <x-capa.activity-timeline :logs="$incident->activityLogs" />
    @endif
</div>
