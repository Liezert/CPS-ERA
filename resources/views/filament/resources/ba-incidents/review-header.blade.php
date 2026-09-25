@php
    use App\Enums\BaIncidentStatus;
    use App\Filament\Resources\BaIncidents\BaIncidentResource;

    $status = $record->status;
    $isReviewed = $status === BaIncidentStatus::Approved->value;
    // Merah untuk dua kasus ditolak: diminta revisi (Supervisor) dan ditolak permanen (HR).
    $isRejected = in_array($status, [BaIncidentStatus::RevisionRequested->value, BaIncidentStatus::Rejected->value], true);
    $statusLabel = BaIncidentStatus::tryFrom($status)?->getLabel() ?? ucfirst($status);
    $hasVideo = $record->video && ($record->video->video_file_url || $record->video->video_external_link);
    $indexUrl = BaIncidentResource::getUrl('index');
@endphp

{{-- Header & pipeline review — desain identik dengan header form pengisian employee (livewire/ba/create) --}}
<div class="capa-scope">
    <div class="max-w-4xl mx-auto space-y-6">
        <header class="bg-white border border-neutral-200 rounded-md p-5 sm:p-6 shadow-2xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-2 min-w-0 flex-1">
                    <nav class="flex items-center gap-2 text-xs font-sans text-neutral-500 font-medium" aria-label="Breadcrumb">
                        <a href="{{ $indexUrl }}" class="hover:text-neutral-900 transition-colors duration-150 truncate">
                            Laporan CAPA
                        </a>
                        <svg class="w-3 h-3 text-neutral-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                        <span class="text-neutral-900 font-semibold truncate">
                            Review CAPA / FTK
                        </span>
                    </nav>

                    <div class="space-y-1.5 pt-0.5">
                        <h1 class="font-sans font-bold text-xl sm:text-2xl text-neutral-900 tracking-tight">
                            Review Laporan Tindakan Korektif (CAPA)
                        </h1>
                    </div>

                    <p class="font-sans text-xs sm:text-sm text-neutral-600 leading-relaxed max-w-[68ch]">
                        Tinjau formulir CAPA yang diajukan karyawan persis seperti saat diisi: analisis 5 Whys, rencana tindakan koreksi &amp; korektif, serta video bukti, sebelum memberikan verifikasi efektivitas.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-start sm:self-center">
                    <a href="{{ $indexUrl }}"
                       class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 hover:text-neutral-900 hover:border-neutral-300 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-neutral-900/15 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                        <span>Kembali</span>
                    </a>
                </div>
            </div>
        </header>

        {{-- Pipeline progres: Formulir → Video → Review Admin (gaya stepper form employee) --}}
        <nav class="bg-white border border-neutral-200 rounded-md p-4 sm:p-5 shadow-2xs" aria-label="Alur Review Laporan">
            <div class="w-full bg-neutral-100 rounded-full h-1.5 mb-4 overflow-hidden">
                <div class="h-1.5 rounded-full transition-all duration-300 ease-out {{ $isRejected ? 'bg-red-500 w-full' : ($isReviewed ? 'bg-brand w-full' : 'bg-brand w-2/3') }}"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4 items-stretch">
                {{-- Langkah 1 --}}
                <div class="flex items-center gap-3.5 p-3 rounded-md border border-neutral-200/80 bg-neutral-50/50">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 bg-brand-tint border border-brand/40">
                        <svg class="w-4 h-4 text-brand-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold font-sans text-neutral-700">Langkah 1: Formulir CAPA</span>
                            <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">Terisi</span>
                        </div>
                        <p class="text-xs text-neutral-600 truncate mt-0.5">
                            Oleh {{ $record->creator?->name ?? 'Pegawai' }} &middot; {{ $record->created_at?->format('d M Y H:i') }}
                        </p>
                    </div>
                </div>

                {{-- Langkah 2 --}}
                <div class="flex items-center gap-3.5 p-3 rounded-md border border-neutral-200/80 bg-neutral-50/50">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-mono text-xs font-bold shrink-0 {{ $hasVideo ? 'bg-brand-tint border border-brand/40' : 'bg-amber-50 text-amber-800 border border-amber-300' }}">
                        @if ($hasVideo)
                            <svg class="w-4 h-4 text-brand-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @else
                            !
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold font-sans text-neutral-700">Langkah 2: Video Penanganan</span>
                            @if ($hasVideo)
                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">Terlampir</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-amber-900 bg-amber-100 border border-amber-300 rounded-badge">Belum Ada</span>
                            @endif
                        </div>
                        <p class="text-xs text-neutral-600 truncate mt-0.5">
                            {{ $hasVideo ? 'Bukti visual tersedia di bagian 7' : 'Tidak ada berkas / tautan video' }}
                        </p>
                    </div>
                </div>

                {{-- Langkah 3: Review Admin --}}
                <div class="flex items-center gap-3.5 p-3 rounded-md border {{ $isReviewed ? 'border-neutral-200/80 bg-neutral-50/50' : ($isRejected ? 'border-red-300 bg-red-50/40 shadow-2xs' : 'border-brand/40 bg-brand-tint/25 shadow-2xs') }}">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-mono text-xs font-bold shrink-0 {{ $isReviewed ? 'bg-brand-tint border border-brand/40' : ($isRejected ? 'bg-red-600 text-white ring-4 ring-red-100' : 'bg-brand text-white ring-4 ring-brand-tint') }}">
                        @if ($isReviewed)
                            <svg class="w-4 h-4 text-brand-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @else
                            3
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold font-sans text-neutral-900">Langkah 3: Review Admin</span>
                            @if ($isReviewed)
                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">{{ $statusLabel }}</span>
                            @elseif ($isRejected)
                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-red-800 bg-red-50 border border-red-200 rounded-badge">{{ $statusLabel }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-amber-900 bg-amber-100 border border-amber-300 rounded-badge">{{ $statusLabel }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-neutral-600 truncate mt-0.5">
                            @if ($record->reviewed_at)
                                {{ $record->reviewer?->name ?? 'Reviewer' }} &middot; {{ $record->reviewed_at->format('d M Y H:i') }}
                            @else
                                Verifikasi efektivitas tindakan korektif
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </nav>

        @if ($isRejected && filled($record->catatan_penolakan))
            <div class="p-4 bg-red-50 border border-red-200 rounded-md flex items-start gap-3 shadow-2xs" role="alert">
                <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <div class="space-y-1 text-xs font-sans">
                    <p class="font-bold text-red-900">Laporan ini dikembalikan untuk revisi</p>
                    <p class="text-red-700 leading-relaxed"><strong>Catatan Reviewer:</strong> {{ $record->catatan_penolakan }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
