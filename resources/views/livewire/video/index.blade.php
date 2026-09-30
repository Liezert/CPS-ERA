@php
    $statusLabels = [
        'pending_hr' => ['Menunggu Review HR', 'border-amber-400 text-amber-900 bg-amber-50'],
        'published' => ['Tayang di Learning', 'border-brand/40 text-brand-dark bg-brand-tint'],
        'rejected' => ['Ditolak HR', 'border-red-300 text-red-700 bg-red-50'],
    ];
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <header class="bg-white border border-neutral-200 rounded-md p-5 sm:p-6 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <h1 class="font-sans font-bold text-xl sm:text-2xl text-neutral-900 tracking-tight">Video Kontribusi</h1>
            <p class="text-xs sm:text-sm text-neutral-600 max-w-[68ch]">
                Video yang disetujui tim HR tampil di Learning untuk seluruh karyawan, dan pengunggahnya mendapat 1 Poin CPS ERA.
            </p>
        </div>
        <a href="{{ route('videos.create') }}"
           class="shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-semibold">
            @include('components.layout.nav-icon', ['name' => 'video', 'class' => 'w-4 h-4'])
            Unggah Video
        </a>
    </header>

    @if (session('status'))
        <div class="p-3 bg-brand-tint border border-brand/20 rounded-md text-xs text-brand-dark">{{ session('status') }}</div>
    @endif

    @if ($reviewQueue !== null)
        <section class="bg-white border border-neutral-200 rounded-md p-5 shadow-2xs" aria-labelledby="video-review-title">
            <div class="flex items-center justify-between pb-3 border-b border-neutral-200">
                <h2 id="video-review-title" class="font-sans font-semibold text-sm text-neutral-900">Menunggu Review HR</h2>
                <span class="font-mono text-sm font-bold {{ $reviewQueue->isNotEmpty() ? 'text-amber-800' : 'text-neutral-500' }}">{{ $reviewQueue->count() }}</span>
            </div>
            @forelse ($reviewQueue as $video)
                <a href="{{ route('videos.show', $video) }}" wire:key="queue-{{ $video->id }}"
                   class="flex items-center justify-between gap-3 py-3 border-b border-neutral-100 last:border-b-0 group">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-neutral-900 truncate">{{ $video->title }}</p>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            {{ $video->creator?->name ?? 'Pegawai' }} &middot; {{ $video->division?->name ?? '-' }} &middot; {{ $video->created_at->diffForHumans() }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs font-semibold text-brand group-hover:text-brand-dark">Review</span>
                </a>
            @empty
                <p class="py-6 text-center text-xs text-neutral-500">Tidak ada video yang menunggu review.</p>
            @endforelse
        </section>
    @endif

    <section class="bg-white border border-neutral-200 rounded-md p-5 shadow-2xs" aria-labelledby="my-videos-title">
        <h2 id="my-videos-title" class="font-sans font-semibold text-sm text-neutral-900 pb-3 border-b border-neutral-200">Video Saya</h2>
        @forelse ($myVideos as $video)
            @php [$label, $classes] = $statusLabels[$video->status] ?? [ucfirst($video->status), 'border-neutral-200 text-neutral-700 bg-white']; @endphp
            <a href="{{ route('videos.show', $video) }}" wire:key="mine-{{ $video->id }}"
               class="flex items-center justify-between gap-3 py-3 border-b border-neutral-100 last:border-b-0">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-neutral-900 truncate">{{ $video->title }}</p>
                    <p class="text-xs text-neutral-500 mt-0.5">Diunggah {{ $video->created_at->wib()->format('d M Y, H:i') }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center px-2 py-0.5 text-xs font-mono font-medium border rounded-badge {{ $classes }}">{{ $label }}</span>
            </a>
        @empty
            <div class="py-8 text-center space-y-3">
                <p class="text-xs text-neutral-500">Anda belum pernah mengunggah video.</p>
                <a href="{{ route('videos.create') }}" class="inline-flex px-3.5 py-2 border border-neutral-300 rounded-md text-xs font-medium text-neutral-800 hover:bg-neutral-50">Unggah Video Pertama</a>
            </div>
        @endforelse

        @if ($myVideos->hasPages())
            <div class="pt-3">{{ $myVideos->links() }}</div>
        @endif
    </section>
</div>
