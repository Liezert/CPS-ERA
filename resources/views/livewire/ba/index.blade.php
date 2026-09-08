<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                    Berita Acara &amp; Lesson Learned
                </h1>
                <span class="inline-flex items-center px-2 py-0.5 border border-neutral-200 rounded-badge font-mono text-xs text-neutral-600 bg-neutral-50">
                    {{ $incidents->total() }} Laporan
                </span>
            </div>
            <p class="font-sans text-xs text-neutral-500 mt-1">
                Daftar laporan ketidaksesuaian operasional, verifikasi insiden divisi, dan alur perbaikan berkelanjutan.
            </p>
        </div>

        <div class="shrink-0">
            <a href="{{ route('ba.create') }}"
               class="inline-flex items-center px-4 py-2 bg-brand hover:bg-brand-dark text-white rounded-badge text-xs font-sans font-medium transition-colors focus:outline-none focus:ring-1 focus:ring-brand">
                Buat Laporan BA
            </a>
        </div>
    </div>

    {{-- Notifikasi Sukses Penerbitan BA --}}
    @if (session()->has('success'))
        <div x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="p-4 bg-brand-tint border border-brand/40 rounded-badge flex items-center justify-between gap-3 text-brand-dark">
            <div class="flex items-center gap-2.5">
                <div class="w-5 h-5 rounded-[2px] bg-brand text-white flex items-center justify-center shrink-0">
                    <x-layout.nav-icon name="badge-check" class="w-3.5 h-3.5" />
                </div>
                <span class="font-sans text-xs font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-brand-dark hover:text-neutral-900 transition-colors p-1" title="Tutup notifikasi">
                <x-layout.nav-icon name="x-mark" class="w-4 h-4" />
            </button>
        </div>
    @endif

    {{-- Filter & Search Bar --}}
    <div class="bg-white border border-neutral-200 rounded-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            {{-- Search Input --}}
            <div class="md:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                    <x-layout.nav-icon name="search" class="w-4 h-4" />
                </div>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari nomor BA, judul insiden, atau kronologi..."
                       class="w-full pl-9 pr-8 py-2 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors" />

                @if(!empty($search))
                    <button type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-neutral-400 hover:text-neutral-600 focus:outline-none">
                        <x-layout.nav-icon name="x-mark" class="w-3.5 h-3.5" />
                    </button>
                @endif
            </div>

            {{-- Filter Divisi (13 opsi tetap) --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedDivisionId"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors">
                    <option value="">Semua Divisi (13 Divisi)</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Status (3 kemungkinan) --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedStatus"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors">
                    <option value="">Semua Status</option>
                    <option value="created">Created (Baru)</option>
                    <option value="reviewed">Reviewed (Ditinjau)</option>
                    <option value="closed">Closed (Ditutup)</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Daftar BA Table / List --}}
    @if($incidents->isEmpty())
        <div class="bg-white border border-neutral-200 rounded-md p-12 text-center">
            <div class="w-12 h-12 mx-auto rounded-md bg-neutral-100 border border-neutral-200 text-neutral-400 flex items-center justify-center mb-3">
                <x-layout.nav-icon name="shield-alert" class="w-6 h-6" />
            </div>
            <h3 class="font-sans font-medium text-sm text-neutral-900">
                Tidak ada laporan Berita Acara
            </h3>
            <p class="font-sans text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                @if(!empty($search) || !empty($selectedDivisionId) || !empty($selectedStatus))
                    Tidak ada BA yang cocok dengan filter atau kata kunci saat ini.
                @else
                    Belum ada insiden operasional yang dilaporkan.
                @endif
            </p>
            <div class="mt-4">
                <a href="{{ route('ba.create') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-brand text-white rounded-badge text-xs font-sans font-medium hover:bg-brand-dark transition-colors">
                    Buat Laporan BA Pertama
                </a>
            </div>
        </div>
    @else
        <div class="bg-white border border-neutral-200 rounded-md divide-y divide-neutral-200 overflow-hidden">
            @foreach($incidents as $ba)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-neutral-50/50 transition-colors">
                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            {{-- Nomor BA IBM Plex Mono --}}
                            <span class="font-mono text-xs font-semibold text-neutral-800 px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge">
                                {{ $ba->nomor_ba }}
                            </span>

                            @if($ba->division)
                                <span class="px-1.5 py-0.5 border border-neutral-200 rounded-badge text-[11px] font-sans text-neutral-700 bg-neutral-50">
                                    {{ $ba->division->name }}
                                </span>
                            @endif

                            {{-- Status Badge Kotak 2px --}}
                            @switch($ba->status)
                                @case('created')
                                    <span class="px-2 py-0.5 border border-neutral-300 rounded-badge font-mono text-[10px] text-neutral-600 bg-white">
                                        Created
                                    </span>
                                    @break
                                @case('reviewed')
                                    <span class="px-2 py-0.5 border border-neutral-600 rounded-badge font-mono text-[10px] text-neutral-900 bg-white">
                                        Reviewed
                                    </span>
                                    @break
                                @case('closed')
                                    <span class="px-2 py-0.5 border border-brand rounded-badge font-mono text-[10px] text-brand-dark bg-brand-tint/30">
                                        Closed
                                    </span>
                                    @break
                            @endswitch
                        </div>

                        <a href="{{ route('ba.show', $ba->id) }}"
                           class="font-sans font-semibold text-sm text-neutral-900 hover:text-brand transition-colors block truncate pt-0.5">
                            {{ $ba->title ?: 'Laporan Insiden Tanpa Judul' }}
                        </a>

                        <div class="flex items-center gap-2 text-[11px] font-sans text-neutral-400">
                            <span>Pelapor: {{ $ba->creator?->name ?? 'Pegawai' }}</span>
                            <span>&middot;</span>
                            <span class="font-mono">{{ $ba->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    </div>

                    <div class="shrink-0 self-end sm:self-center">
                        <a href="{{ route('ba.show', $ba->id) }}"
                           class="inline-flex items-center px-3 py-1.5 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                            Lihat Detail &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-2">
            {{ $incidents->links() }}
        </div>
    @endif
</div>
