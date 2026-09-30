@php
    [$statusLabel, $statusClasses] = match ($video->status) {
        'pending_hr' => ['Menunggu Review HR', 'border-amber-400 text-amber-900 bg-amber-50'],
        'published' => ['Tayang di Learning', 'border-brand/40 text-brand-dark bg-brand-tint'],
        'rejected' => ['Ditolak HR', 'border-red-300 text-red-700 bg-red-50'],
        default => [ucfirst($video->status), 'border-neutral-200 text-neutral-700 bg-white'],
    };
@endphp

<div class="max-w-4xl mx-auto space-y-6 pb-12">
    @if (session('status'))
        <div class="p-3 bg-brand-tint border border-brand/20 rounded-md text-xs text-brand-dark">{{ session('status') }}</div>
    @endif

    <header class="bg-white border border-neutral-200 rounded-md p-5 sm:p-6 shadow-2xs space-y-3">
        <nav class="flex items-center gap-2 text-xs font-sans text-neutral-500 font-medium" aria-label="Breadcrumb">
            <a href="{{ route('videos.index') }}" class="hover:text-neutral-900 transition-colors">Video Kontribusi</a>
            <span>/</span>
            <span class="text-neutral-900 font-semibold truncate">{{ $video->title }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div class="space-y-1 min-w-0">
                <h1 class="font-sans font-bold text-xl text-neutral-900 tracking-tight">{{ $video->title }}</h1>
                <p class="text-xs text-neutral-600">
                    Oleh {{ $video->creator?->name ?? 'Pegawai' }} &middot; {{ $video->division?->name ?? '-' }}
                    @if ($video->learningCategory) &middot; {{ $video->learningCategory->name }} @endif
                    &middot; <span class="font-mono">{{ $video->created_at->wib()->format('d M Y, H:i') }}</span>
                </p>
            </div>
            <span class="shrink-0 inline-flex items-center px-2.5 py-1 text-xs font-mono font-medium border rounded-badge {{ $statusClasses }}">{{ $statusLabel }}</span>
        </div>
        @if ($video->description)
            <p class="text-xs text-neutral-700 leading-relaxed whitespace-pre-line">{{ $video->description }}</p>
        @endif
    </header>

    @if ($video->isRejected() && $video->rejection_reason)
        <div class="p-4 bg-red-50 border border-red-200 rounded-md text-xs space-y-1" role="status">
            <p class="font-bold text-red-900">Alasan penolakan dari HR</p>
            <p class="text-red-800 leading-relaxed">{{ $video->rejection_reason }}</p>
        </div>
    @endif

    @if ($video->isPublished() && $video->learningMaterial)
        <div class="p-3 bg-brand-tint/30 border border-brand/30 rounded-md flex items-center justify-between gap-3 text-xs">
            <span class="text-neutral-800">Video ini sudah tayang sebagai materi <strong>Learning</strong>.</span>
            <a href="{{ route('learning.show', $video->learningMaterial) }}" class="shrink-0 font-semibold text-brand hover:underline">Buka di Learning</a>
        </div>

        {{-- Setelah tayang: HR/admin melengkapi post-test agar karyawan bisa menguji pemahaman --}}
        @can('create', \App\Models\Quiz::class)
            @php $questionCount = $video->learningMaterial->postTest?->questions()->count() ?? 0; @endphp
            <section class="bg-white border border-neutral-200 rounded-md p-5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-0.5">
                    <h2 class="text-sm font-bold text-neutral-900">Post-Test Materi</h2>
                    <p class="text-xs text-neutral-600">
                        {{ $questionCount > 0 ? "Sudah ada {$questionCount} soal. Karyawan mengerjakannya setelah menandai materi selesai." : 'Belum ada soal. Tambahkan post-test agar karyawan bisa menguji pemahamannya.' }}
                    </p>
                </div>
                <a href="{{ route('learning.post-test.edit', $video->learningMaterial) }}" wire:navigate
                   class="shrink-0 inline-flex items-center justify-center px-4 py-2.5 rounded-md text-xs font-semibold {{ $questionCount > 0 ? 'border border-neutral-300 bg-white text-neutral-800 hover:bg-neutral-50' : 'bg-brand hover:bg-brand-dark text-white' }}">
                    {{ $questionCount > 0 ? 'Edit Soal Post-Test' : 'Buat Soal Post-Test' }}
                </a>
            </section>
        @endcan
    @endif

    <section class="bg-white border border-neutral-200 rounded-md p-5 shadow-2xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-neutral-900">Video</h2>
        @if ($video->drive_preview_url)
            <div class="bg-neutral-900 rounded-md overflow-hidden aspect-video">
                <iframe src="{{ $video->drive_preview_url }}" title="{{ $video->title }}"
                        class="w-full h-full border-0" allow="autoplay; fullscreen" allowfullscreen loading="lazy"></iframe>
            </div>
        @elseif ($video->video_url)
            <a href="{{ $video->video_url }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:underline">Buka tautan video</a>
        @else
            <p class="text-xs text-neutral-500">Video tidak tersedia.</p>
        @endif
    </section>

    {{-- Review HR: approve / tolak langsung di halaman ini --}}
    @if ($this->canReview)
        <section id="panel-review" x-data="{ mode: null }" class="bg-white border-2 border-brand/40 rounded-md p-5 sm:p-6 space-y-4 shadow-2xs">
            <div class="space-y-1">
                <h2 class="text-sm font-bold text-neutral-900">Keputusan Review HR</h2>
                <p class="text-xs text-neutral-600">Jika disetujui, video tampil di Learning untuk seluruh karyawan dan pengunggah mendapat 1 Poin CPS ERA (maksimal 3 per tahun).</p>
            </div>

            @error('review')
                <div class="p-3 bg-red-50 border border-red-200 rounded-md text-xs font-medium text-red-800" role="alert">{{ $message }}</div>
            @enderror

            <div x-show="mode === null" class="flex flex-col sm:flex-row gap-2.5">
                <button type="button" x-on:click="mode = 'approve'"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-semibold">Setujui &amp; Tayangkan</button>
                <button type="button" x-on:click="mode = 'reject'"
                        class="inline-flex items-center justify-center px-4 py-2.5 border border-red-300 text-red-700 bg-white hover:bg-red-50 rounded-md text-xs font-semibold">Tolak Video</button>
            </div>

            <form x-show="mode === 'approve'" x-cloak wire:submit="approve" class="space-y-3 p-4 bg-brand-tint/20 border border-brand/30 rounded-md">
                <label for="catatan_hr" class="block text-xs font-bold text-neutral-900">Catatan untuk pengunggah (opsional)</label>
                <textarea id="catatan_hr" wire:model="catatanHr" rows="2"
                          class="w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-brand/20 focus:border-brand"></textarea>
                @error('catatanHr') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" x-on:click="mode = null" class="px-4 py-2 border border-neutral-300 bg-white rounded-md text-xs font-medium text-neutral-700 hover:bg-neutral-50">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="approve" class="px-4 py-2 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-semibold disabled:opacity-60">
                        <span wire:loading.remove wire:target="approve">Konfirmasi Setujui</span>
                        <span wire:loading wire:target="approve">Memproses...</span>
                    </button>
                </div>
            </form>

            <form x-show="mode === 'reject'" x-cloak wire:submit="reject" class="space-y-3 p-4 bg-red-50/50 border border-red-200 rounded-md">
                <label for="alasan_penolakan" class="block text-xs font-bold text-neutral-900">Alasan penolakan</label>
                <textarea id="alasan_penolakan" wire:model="alasanPenolakan" rows="3" placeholder="Contoh: Audio tidak jelas, mohon rekam ulang."
                          class="w-full p-2.5 bg-white border border-neutral-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-red-200 focus:border-red-400"></textarea>
                @error('alasanPenolakan') <span class="text-xs text-red-600 block">{{ $message }}</span> @enderror
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                    <button type="button" x-on:click="mode = null" class="px-4 py-2 border border-neutral-300 bg-white rounded-md text-xs font-medium text-neutral-700 hover:bg-neutral-50">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="reject" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold disabled:opacity-60">
                        <span wire:loading.remove wire:target="reject">Konfirmasi Tolak</span>
                        <span wire:loading wire:target="reject">Memproses...</span>
                    </button>
                </div>
            </form>
        </section>
    @endif
</div>
