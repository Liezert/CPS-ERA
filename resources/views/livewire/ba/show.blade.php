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
                    <div class="font-bold text-red-900">{{ $incident->isRejected() ? 'Laporan Ditutup: Ditolak oleh HR' : 'Laporan BA Ini Perlu Revisi' }}</div>
                    <p class="text-red-700 leading-relaxed">
                        <strong>Catatan Reviewer:</strong> {{ $incident->catatan_penolakan ?: 'Harap lengkapi dan perbaiki data analisa sebelum diserahkan kembali.' }}
                    </p>
                    @if ($incident->isRejected())
                        <p class="text-red-700 leading-relaxed">
                            Laporan ini tidak dapat direvisi. Buat laporan baru dengan topik yang berbeda.
                        </p>
                    @endif
                </div>
            </div>

            @if ($incident->isRejected() && (int) $incident->created_by === (int) auth()->id())
                <a href="{{ route('ba.create') }}"
                   class="inline-flex items-center justify-center px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-medium shrink-0 transition-colors shadow-xs">
                    <span>Buat Laporan Baru</span>
                </a>
            @elseif ($this->canEdit)
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
                <span class="font-mono text-neutral-500">{{ $incident->created_at->wib()->format('d M Y, H:i') }}</span>
            </div>
        </div>

        {{-- Status Badge & Action Buttons --}}
        <div class="shrink-0 flex flex-col items-start md:items-end gap-3">
            <div>
                <span class="text-xs font-sans text-neutral-600 block md:text-right font-semibold uppercase tracking-wider mb-1">
                    Status BA
                </span>
                <x-ui.badge :status="$incident->status" />
            </div>

            {{-- Reviewer tidak butuh tombol lompat: panel keputusan tepat di bawah header ini. --}}
            @if(! $this->reviewStage && $this->canEdit)
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
         REVIEW LAPORAN — approve / tolak langsung dari preview.
         Tahap 1 Supervisor divisi pelapor, tahap 2 HR (aturan dari BaIncidentPolicy).
         ========================================================================= --}}
    @if ($stage = $this->reviewStage)
        <section id="panel-review" x-data="{ mode: null }"
                 class="scroll-mt-24 bg-white border-2 border-brand/40 rounded-md p-5 sm:p-6 space-y-4 shadow-2xs">
            <div class="space-y-1">
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-mono font-semibold text-brand-dark bg-brand-tint border border-brand/30 rounded-badge">
                    {{ $stage === 'hr' ? 'Tahap 2 — Review HR' : 'Tahap 1 — Review Supervisor' }}
                </span>
                <h3 class="text-sm font-bold text-neutral-900 font-sans">Keputusan Review Laporan</h3>
                <p class="text-xs text-neutral-600 leading-relaxed">
                    @if ($stage === 'hr')
                        Evaluasi efektivitas tindakan korektif, lalu setujui final atau tolak permanen laporan ini.
                    @else
                        Setujui untuk meneruskan laporan ke tim HR, atau kembalikan ke pelapor untuk direvisi.
                    @endif
                </p>
            </div>

            {{-- Ringkasan untuk memutuskan tanpa scroll; formulir lengkap tetap ada di bawah panel. --}}
            <dl class="grid gap-3 sm:grid-cols-3 p-4 bg-neutral-50/70 border border-neutral-200 rounded-md text-xs">
                <div class="min-w-0">
                    <dt class="font-semibold text-neutral-600">Masalah · {{ $capa['lokasi'] ?: '-' }}</dt>
                    <dd class="mt-1 text-neutral-900 leading-relaxed line-clamp-4">{{ $capa['deskripsiMasalah'] ?: '-' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="font-semibold text-neutral-600">Akar masalah</dt>
                    <dd class="mt-1 text-neutral-900 leading-relaxed line-clamp-4">{{ $capa['kesimpulanAkarMasalah'] ?: '-' }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="font-semibold text-neutral-600">Tindakan korektif</dt>
                    <dd class="mt-1 text-neutral-900 leading-relaxed line-clamp-4">{{ $capa['korektifDeskripsi'] ?: '-' }}</dd>
                    <dd class="mt-1 text-neutral-600">PIC {{ $capa['korektifPic'] ?: '-' }} · Target {{ $capa['korektifWaktu'] ?: '-' }}</dd>
                </div>
                @if ($stage === 'hr' && $incident->potensi_kerugian !== null)
                    <x-capa.loss-summary :incident="$incident" class="sm:col-span-3 pt-3 border-t border-neutral-200" />
                @endif
                @if ($stage === 'hr' && filled($this->supervisorNote))
                    <div class="sm:col-span-3 pt-3 border-t border-neutral-200">
                        <dt class="font-semibold text-neutral-600">Catatan Supervisor</dt>
                        <dd class="mt-1 text-neutral-900 leading-relaxed">{{ $this->supervisorNote }}</dd>
                    </div>
                @endif
            </dl>

            @error('review')
                <div class="p-3 bg-red-50 border border-red-200 rounded-md text-xs font-medium text-red-800" role="alert">{{ $message }}</div>
            @enderror

            <div x-show="mode === null" class="flex flex-col sm:flex-row gap-2.5">
                <button type="button" x-on:click="mode = 'approve'"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-sans font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    {{ $stage === 'hr' ? 'Setujui Final' : 'Setujui & Teruskan ke HR' }}
                </button>
                <button type="button" x-on:click="mode = 'reject'"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 border border-red-300 text-red-700 bg-white hover:bg-red-50 rounded-md text-xs font-sans font-semibold transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    {{ $stage === 'hr' ? 'Tolak Permanen' : 'Tolak & Minta Revisi' }}
                </button>
            </div>

            {{-- Form Setujui --}}
            <form x-show="mode === 'approve'" x-cloak wire:submit="approve" class="space-y-3 p-4 bg-brand-tint/20 border border-brand/30 rounded-md">
                @if ($stage === 'hr')
                    <fieldset class="space-y-1.5">
                        <legend class="text-xs font-bold text-neutral-900 mb-1">Status Verifikasi Hasil</legend>
                        <label class="flex items-center gap-2 text-xs text-neutral-800">
                            <input type="radio" wire:model.live="statusVerifikasi" value="efektif" class="text-brand focus:ring-brand"> Diverifikasi Efektif
                        </label>
                        <label class="flex items-center gap-2 text-xs text-neutral-800">
                            <input type="radio" wire:model.live="statusVerifikasi" value="tidak_efektif" class="text-brand focus:ring-brand"> Tidak Efektif
                        </label>
                    </fieldset>

                    @if ($statusVerifikasi === 'efektif')
                        <div>
                            <label for="bukti_objektif" class="block text-xs font-bold text-neutral-900 mb-1">Bukti Objektif Efektivitas</label>
                            <textarea id="bukti_objektif" wire:model="buktiObjektif" rows="3"
                                      placeholder="Sebutkan data hasil pengukuran / inspeksi QC..."
                                      class="w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></textarea>
                            @error('buktiObjektif') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @else
                        <div>
                            <label for="alasan_tidak_efektif" class="block text-xs font-bold text-neutral-900 mb-1">Alasan Ketidakefektifan</label>
                            <textarea id="alasan_tidak_efektif" wire:model="alasanTidakEfektif" rows="3"
                                      placeholder="Jelaskan parameter yang belum terpenuhi..."
                                      class="w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></textarea>
                            @error('alasanTidakEfektif') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endif
                @else
                    <div>
                        <label for="catatan_supervisor" class="block text-xs font-bold text-neutral-900 mb-1">Catatan Lapangan untuk HR (opsional)</label>
                        <textarea id="catatan_supervisor" wire:model="catatanSupervisor" rows="3"
                                  placeholder="Temuan saat verifikasi di lapangan yang perlu diketahui tim HR..."
                                  class="w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></textarea>
                        @error('catatanSupervisor') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <fieldset class="space-y-1.5">
                        <legend class="text-xs font-bold text-neutral-900 mb-1">Potensi Kerugian</legend>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 text-xs text-neutral-800">
                                <input type="radio" wire:model.live="potensiKerugian" value="ada" class="text-brand focus:ring-brand"> Ada
                            </label>
                            <label class="flex items-center gap-2 text-xs text-neutral-800">
                                <input type="radio" wire:model.live="potensiKerugian" value="tidak" class="text-brand focus:ring-brand"> Tidak ada
                            </label>
                        </div>
                        @error('potensiKerugian') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
                    </fieldset>

                    @if ($potensiKerugian === 'ada')
                        <div class="space-y-3 p-3 bg-white border border-neutral-200 rounded-md">
                            <div>
                                <label for="nilai_kerugian" class="block text-xs font-bold text-neutral-900 mb-1">Rekomendasi mengganti kerugian sebesar (Rp)</label>
                                {{-- Format titik ribuan saat mengetik; .capture di pembungkus agar jalan sebelum wire:model membaca nilainya. --}}
                                <div class="relative sm:max-w-xs" x-on:input.capture="$event.target.value = $event.target.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"><span class="absolute inset-y-0 left-2.5 flex items-center text-xs text-neutral-500 pointer-events-none">Rp</span><input id="nilai_kerugian" type="text" inputmode="numeric" wire:model="nilaiKerugian" placeholder="1.000.000" class="pl-8 w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></div>
                                @error('nilaiKerugian') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div class="space-y-2">
                                <span class="block text-xs font-bold text-neutral-900">Ditanggung oleh</span>
                                @foreach ($penanggungKerugian as $i => $row)
                                    <div wire:key="penanggung-{{ $i }}" class="flex flex-col sm:flex-row gap-2 sm:items-start">
                                        <div class="flex-1">
                                            <input type="text" wire:model="penanggungKerugian.{{ $i }}.nama" placeholder="Nama" aria-label="Nama penanggung {{ $i + 1 }}" class="w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand">
                                            @error("penanggungKerugian.$i.nama") <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="sm:w-48">
                                            <div class="relative" x-on:input.capture="$event.target.value = $event.target.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"><span class="absolute inset-y-0 left-2.5 flex items-center text-xs text-neutral-500 pointer-events-none">Rp</span><input type="text" inputmode="numeric" wire:model="penanggungKerugian.{{ $i }}.nominal" placeholder="Sebesar" aria-label="Nominal penanggung {{ $i + 1 }} (Rp)" class="pl-8 w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></div>
                                            @error("penanggungKerugian.$i.nominal") <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                                        </div>
                                        @if (count($penanggungKerugian) > 1)
                                            <button type="button" wire:click="removePenanggung({{ $i }})" class="px-2.5 py-2 text-xs font-medium text-red-700 hover:bg-red-50 rounded-md">Hapus</button>
                                        @endif
                                    </div>
                                @endforeach
                                @error('penanggungKerugian') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
                                <button type="button" wire:click="addPenanggung" class="text-xs font-semibold text-brand-dark hover:underline">+ Tambah penanggung</button>
                            </div>
                        </div>
                    @endif
                @endif

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" x-on:click="mode = null"
                            class="px-4 py-2 border border-neutral-300 bg-white rounded-md text-xs font-medium text-neutral-700 hover:bg-neutral-50">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="approve"
                            class="px-4 py-2 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-semibold disabled:opacity-60">
                        <span wire:loading.remove wire:target="approve">{{ $stage === 'hr' ? 'Konfirmasi Setujui Final' : 'Konfirmasi Setujui' }}</span>
                        <span wire:loading wire:target="approve">Memproses...</span>
                    </button>
                </div>
            </form>

            {{-- Form Tolak --}}
            <form x-show="mode === 'reject'" x-cloak wire:submit="reject" class="space-y-3 p-4 bg-red-50/50 border border-red-200 rounded-md">
                <div>
                    <label for="catatan_penolakan" class="block text-xs font-bold text-neutral-900 mb-1">
                        {{ $stage === 'hr' ? 'Alasan Penolakan Permanen' : 'Bagian yang Perlu Direvisi Pelapor' }}
                    </label>
                    <textarea id="catatan_penolakan" wire:model="catatanPenolakan" rows="3"
                              placeholder="{{ $stage === 'hr' ? 'Jelaskan alasan laporan ini ditutup, mis. topik sudah pernah dilaporkan atau bukan ketidaksesuaian...' : 'Jelaskan bagian analisa atau tindakan yang perlu dilengkapi pembuat...' }}"
                              class="w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-red-400"></textarea>
                    @error('catatanPenolakan') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                    <p class="text-xs text-neutral-600 mt-1.5">
                        {{ $stage === 'hr' ? 'Penolakan HR bersifat final: laporan ditutup dan pelapor harus membuat laporan baru dengan topik berbeda.' : 'Laporan dikembalikan ke pelapor, lalu dikirim ulang ke Supervisor setelah diperbaiki.' }}
                    </p>
                </div>
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" x-on:click="mode = null"
                            class="px-4 py-2 border border-neutral-300 bg-white rounded-md text-xs font-medium text-neutral-700 hover:bg-neutral-50">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="reject"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold disabled:opacity-60">
                        <span wire:loading.remove wire:target="reject">{{ $stage === 'hr' ? 'Konfirmasi Tolak Permanen' : 'Konfirmasi Kembalikan ke Pelapor' }}</span>
                        <span wire:loading wire:target="reject">Memproses...</span>
                    </button>
                </div>
            </form>
        </section>
    @endif


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
         3. VIDEO PENANGANAN — hanya laporan lama (sebelum 2026-09-27 CAPA masih melampirkan video)
         ========================================================================= --}}
    @if($incident->video)
        <div class="bg-white border border-neutral-200 rounded-md p-6 space-y-4">
            <div class="pb-3 border-b border-neutral-200">
                <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-sans">
                    Dokumentasi Video Penanganan (Arsip)
                </h3>
                <p class="text-xs text-neutral-600">
                    Video yang dilampirkan saat laporan CAPA masih mewajibkan video.
                </p>
            </div>

            <x-capa.video-player :incident="$incident" />
        </div>
    @endif

    @if ($incident->potensi_kerugian !== null && $this->canSeeLoss)
        <dl class="bg-white border border-neutral-200 rounded-md p-6 text-xs">
            <x-capa.loss-summary :incident="$incident" />
        </dl>
    @endif

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
                        <span class="text-xs font-bold text-neutral-700 uppercase tracking-wider block mb-1">
                            Bukti Objektif Efektivitas:
                        </span>
                        <p class="text-xs text-neutral-900 leading-relaxed font-medium bg-white p-3 rounded border border-neutral-200">
                            {{ $incident->bukti_objektif ?: 'Diverifikasi efektif sesuai standar toleransi operasional pabrik.' }}
                        </p>
                    </div>
                @else
                    <div>
                        <span class="text-xs font-bold text-red-800 uppercase tracking-wider block mb-1">
                            Alasan Ketidakefektifan:
                        </span>
                        <p class="text-xs text-red-900 leading-relaxed font-medium bg-white p-3 rounded border border-red-200">
                            {{ $incident->alasan_tidak_efektif ?: 'Penyelesaian belum memenuhi kriteria penerimaan mutu.' }}
                        </p>
                    </div>
                @endif

                <div class="pt-2 border-t border-neutral-200 flex flex-wrap items-center justify-between text-xs text-neutral-600">
                    <span>Diverifikasi oleh: <strong class="text-neutral-900">{{ $incident->reviewer?->name ?? 'Supervisor/Admin' }}</strong></span>
                    <span class="font-mono">{{ $incident->reviewed_at ? $incident->reviewed_at->wib()->format('d M Y H:i') : '-' }}</span>
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
