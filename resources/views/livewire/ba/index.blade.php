<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                    Laporan CAPA
                </h1>
                <span class="inline-flex items-center px-2 py-0.5 border border-neutral-200 rounded-badge font-mono text-xs text-neutral-600 bg-neutral-50">
                    {{ $incidents->total() }} Laporan
                </span>
            </div>
            <p class="font-sans text-xs text-neutral-600 mt-1">
                Daftar laporan ketidaksesuaian operasional, verifikasi insiden divisi, dan alur perbaikan berkelanjutan (Corrective and Preventive Action).
            </p>
        </div>

        <div class="shrink-0">
            <a href="{{ route('ba.create') }}"
               class="inline-flex items-center px-4 py-2 bg-brand hover:bg-brand-dark text-white rounded-badge text-xs font-sans font-medium transition-colors focus:outline-none focus:ring-1 focus:ring-brand">
                Buat Laporan CAPA
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
    <div class="bg-neutral-50/50 border border-neutral-200 rounded-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            {{-- Search Input --}}
            <div class="md:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-500">
                    <x-layout.nav-icon name="search" class="w-4 h-4" />
                </div>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari nomor BA, judul insiden, atau kronologi..."
                       class="w-full pl-9 pr-8 py-2 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 placeholder-neutral-500 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors" />

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
                    <option value="">Semua Divisi ({{ $divisions->count() }} Divisi)</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Status --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedStatus"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft (Tersimpan)</option>
                    <option value="submitted">Submitted (Menunggu Review)</option>
                    <option value="approved">Approved (Disetujui)</option>
                    <option value="rejected">Rejected (Perlu Revisi)</option>
                    <option value="created">Created (Legacy)</option>
                    <option value="reviewed">Reviewed (Legacy)</option>
                    <option value="closed">Closed (Legacy)</option>
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
                    Buat Laporan BA Baru
                </a>
            </div>
        </div>
    @else
        <div class="bg-white border border-neutral-200 rounded-md divide-y divide-neutral-200 overflow-hidden">
            @foreach($incidents as $ba)
                <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-neutral-50/70 transition-colors">
                    <div class="space-y-1.5 min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            {{-- Nomor BA --}}
                            <span class="font-mono text-xs font-bold text-neutral-900 bg-neutral-100 border border-neutral-200 px-1.5 py-0.5 rounded-badge">
                                {{ $ba->nomor_ba }}
                            </span>

                            {{-- Divisi --}}
                            @if($ba->division)
                                <span class="text-xs font-sans text-neutral-600 font-medium">
                                    {{ $ba->division->name }}
                                </span>
                                <span class="text-neutral-300">&middot;</span>
                            @endif

                            {{-- Status Badge Kotak 2px --}}
                            <x-ui.badge status="{{ strtolower($ba->status) }}" />
                        </div>

                        <a href="{{ route('ba.show', $ba->id) }}"
                           class="font-sans font-semibold text-sm text-neutral-900 hover:text-brand transition-colors block truncate pt-0.5">
                            {{ $ba->title ?: 'Laporan Insiden Tanpa Judul' }}
                        </a>

                        <div class="flex items-center gap-2 text-[11px] font-sans text-neutral-600 font-medium">
                            <span>Pelapor: {{ $ba->creator?->name ?? 'Pegawai' }}</span>
                            <span class="text-neutral-300">&middot;</span>
                            <span class="font-mono text-neutral-500">{{ $ba->created_at->format('d M Y, H:i') }}</span>
                        </div>
                    </div>

                    <div class="shrink-0 self-end sm:self-center">
                        <a href="{{ route('ba.show', $ba->id) }}"
                           class="inline-flex items-center px-3 py-1.5 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                            Lihat Detail
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
