@php
    use App\Models\Division;

    $record = $this->getRecord();
    $capa = $record->capaFormValues();
    $divisions = Division::orderBy('id')->get();

    // Header actions didaftarkan di ViewBaIncident; di sini dirender dengan gaya tombol form employee.
    $actions = collect($this->getCachedHeaderActions())
        ->keyBy(fn ($action) => $action->getName())
        ->filter(fn ($action) => $action->isVisible());

    $statusVerifikasi = $record->status_verifikasi;
@endphp

{{-- Formulir CAPA/FTK — komponen & layout yang sama dengan form pengisian employee, mode readonly --}}
<div class="capa-scope">
    <div x-data="{
            visibleWhys: 1,
            copiedBa: false,
            copyBaNumber() {
                navigator.clipboard.writeText(@js($record->nomor_ba));
                this.copiedBa = true;
                setTimeout(() => { this.copiedBa = false; }, 2000);
            }
        }"
        class="max-w-4xl mx-auto space-y-6 pb-12">

        <div class="bg-white border border-neutral-200 rounded-md p-5 sm:p-7 space-y-8 shadow-2xs">

            <x-capa.form.header :values="$capa" :readonly="true" />

            <x-capa.form.dokumen :values="$capa" :divisions="$divisions" :readonly="true" />

            <x-capa.form.sumber :values="$capa" :readonly="true" />

            <x-capa.form.kejadian :values="$capa" :readonly="true" />

            <x-capa.form.akar-masalah :values="$capa" :readonly="true" />

            <x-capa.form.rencana-penanganan :values="$capa" :readonly="true" />

            <x-capa.form.dampak :values="$capa" :readonly="true" />

            {{-- BAGIAN 7: VIDEO PENANGANAN & BUKTI (Langkah 2 form employee) --}}
            <section class="space-y-4" aria-labelledby="section-video">
                <div class="pb-1.5 border-b border-neutral-200 flex items-center justify-between">
                    <h3 id="section-video" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                        7. Video Penanganan &amp; Bukti
                    </h3>
                    <span class="inline-flex items-center px-2 py-0.5 text-[11px] font-mono font-medium text-neutral-600 bg-neutral-100 border border-neutral-200 rounded-badge">
                        Dokumentasi Visual
                    </span>
                </div>

                <x-capa.video-player :incident="$record" />
            </section>

            {{-- BAGIAN 8: VERIFIKASI & APPROVAL REVIEWER --}}
            <section class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 sm:p-6 space-y-5" aria-labelledby="section-review">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-neutral-200 gap-2">
                    <div>
                        <h3 id="section-review" class="text-sm font-bold text-neutral-900 font-sans tracking-tight">
                            8. Verifikasi &amp; Approval Reviewer
                        </h3>
                        <p class="text-xs text-neutral-600 mt-0.5">
                            Hasil evaluasi efektivitas tindakan korektif oleh admin / reviewer mutu.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-[11px] font-mono font-medium text-neutral-800 bg-white border border-neutral-300 rounded-badge uppercase tracking-wider shadow-2xs shrink-0 self-start sm:self-center">
                        Evaluasi Reviewer
                    </span>
                </div>

                @if ($statusVerifikasi || $record->reviewed_by)
                    <div class="bg-white p-4 sm:p-5 rounded-md border border-neutral-200 shadow-2xs space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="space-y-1.5 min-w-0">
                                <span class="block text-xs font-semibold text-neutral-800 font-sans">Status Verifikasi</span>
                                @if ($statusVerifikasi === 'efektif')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-badge text-xs font-mono font-bold bg-brand-tint text-brand-dark border border-brand/40">✓ Diverifikasi Efektif</span>
                                @elseif ($statusVerifikasi === 'tidak_efektif')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-badge text-xs font-mono font-bold bg-red-50 text-red-800 border border-red-300">✗ Tidak Efektif</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-badge text-xs font-mono font-medium bg-neutral-100 text-neutral-600 border border-neutral-200">Belum Diverifikasi</span>
                                @endif
                            </div>
                            <div class="space-y-1.5 min-w-0">
                                <span class="block text-xs font-semibold text-neutral-800 font-sans">Ditinjau Oleh</span>
                                <span class="block text-xs text-neutral-900 truncate">{{ $record->reviewer?->name ?? '-' }}</span>
                            </div>
                            <div class="space-y-1.5 min-w-0">
                                <span class="block text-xs font-semibold text-neutral-800 font-sans">Waktu Review / Selesai</span>
                                <span class="block text-xs font-mono text-neutral-700">
                                    {{ $record->reviewed_at?->format('d M Y H:i') ?? '-' }}
                                    @if ($record->closed_at)
                                        &middot; {{ $record->closed_at->format('d M Y H:i') }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        @if ($statusVerifikasi === 'efektif')
                            <div>
                                <span class="text-[11px] font-bold text-neutral-700 uppercase tracking-wider block mb-1 font-mono">Bukti Objektif Efektivitas:</span>
                                <p class="text-xs text-neutral-900 leading-relaxed bg-neutral-50/70 p-3 rounded-md border border-neutral-200 whitespace-pre-line">{{ $record->bukti_objektif ?: '-' }}</p>
                            </div>
                        @elseif ($statusVerifikasi === 'tidak_efektif')
                            <div>
                                <span class="text-[11px] font-bold text-red-800 uppercase tracking-wider block mb-1 font-mono">Alasan Ketidakefektifan:</span>
                                <p class="text-xs text-red-900 leading-relaxed bg-red-50/50 p-3 rounded-md border border-red-200 whitespace-pre-line">{{ $record->alasan_tidak_efektif ?: '-' }}</p>
                            </div>
                        @endif

                        @if (filled($record->catatan_penolakan))
                            <div>
                                <span class="text-[11px] font-bold text-red-800 uppercase tracking-wider block mb-1 font-mono">Catatan Penolakan / Revisi:</span>
                                <p class="text-xs text-red-900 leading-relaxed bg-red-50/50 p-3 rounded-md border border-red-200 whitespace-pre-line">{{ $record->catatan_penolakan }}</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="p-3.5 bg-amber-50/50 border border-amber-300/80 rounded-md flex items-start gap-2.5 text-xs shadow-2xs">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="min-w-0 flex-1">
                            <span class="font-bold block text-neutral-900">Laporan menunggu verifikasi reviewer.</span>
                            <span class="text-neutral-700 block mt-0.5">
                                Periksa kelengkapan bagian 1&ndash;7, lalu pilih <strong>Setujui &amp; Verifikasi</strong> atau <strong>Tolak / Revisi</strong> di bawah.
                            </span>
                        </div>
                    </div>
                @endif
            </section>

            {{-- ACTION BAR REVIEW (gaya action bar form employee) --}}
            <div class="pt-5 border-t border-neutral-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-neutral-600 font-sans justify-center sm:justify-start">
                    <svg class="w-4 h-4 text-brand shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Diajukan oleh <strong class="text-neutral-900">{{ $record->creator?->name ?? 'Pegawai' }}</strong> &middot; Divisi {{ $record->division?->name ?? '-' }}</span>
                </div>

                <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center gap-2.5 shrink-0">
                    @if ($action = $actions->get('edit'))
                        <a href="{{ $action->getUrl() }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 border border-neutral-300 rounded-md text-xs font-sans font-medium text-neutral-800 bg-white hover:bg-neutral-50 hover:border-neutral-400 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out shadow-2xs">
                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                            </svg>
                            <span>Edit Data</span>
                        </a>
                    @endif

                    @if ($actions->has('reject'))
                        <button type="button"
                                wire:click="mountAction('reject')"
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2.5 border border-red-300 rounded-md text-xs font-sans font-medium text-red-700 bg-white hover:bg-red-50 hover:border-red-400 hover:shadow-xs active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-red-500/20 shadow-2xs disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg class="w-3.5 h-3.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Tolak / Minta Revisi</span>
                        </button>
                    @endif

                    @if ($actions->has('approve'))
                        <button type="button"
                                wire:click="mountAction('approve')"
                                wire:loading.attr="disabled"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand text-white font-sans font-medium text-xs rounded-md hover:bg-brand-dark hover:shadow-sm active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Setujui &amp; Verifikasi</span>
                        </button>
                    @endif

                    @if ($action = $actions->get('post_test'))
                        <a href="{{ $action->getUrl() }}"
                           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand text-white font-sans font-medium text-xs rounded-md hover:bg-brand-dark hover:shadow-sm active:scale-[0.98] transition-all duration-200 ease-out focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                            </svg>
                            <span>{{ $action->getLabel() }}</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
