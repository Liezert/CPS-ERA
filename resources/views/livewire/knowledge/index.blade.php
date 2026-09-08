<div class="space-y-6">
    {{-- =========================================================================
         1. HEADER HALAMAN & TOGGLE BOOKMARK / VIEW MODE (Design System §2 & §5)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                    Knowledge Repository
                </h1>
                {{-- Counter Badge Total Dokumen (font-mono netral) --}}
                <span class="inline-flex items-center px-2 py-0.5 border border-neutral-200 rounded-badge font-mono text-xs text-neutral-600 bg-neutral-50">
                    {{ $documents->total() }} Materi
                </span>
            </div>
            <p class="font-sans text-xs text-neutral-500 mt-1">
                Pusat aset pengetahuan organisasi, SOP, materi pelatihan, dan lesson learned terverifikasi PT CPS.
            </p>
        </div>

        {{-- Toolbar Kanan: Filter Cepat Bookmark & Mode Grid/List --}}
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
            {{-- Tombol Toggle Hanya Bookmark --}}
            <button type="button"
                    wire:click="toggleOnlyBookmarks"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-sans font-medium rounded-badge border transition-colors focus:outline-none focus:ring-1 focus:ring-brand {{ $onlyBookmarks ? 'bg-brand-tint border-brand/30 text-brand-dark' : 'bg-white border-neutral-200 text-neutral-700 hover:bg-neutral-50' }}">
                @if($onlyBookmarks)
                    <x-layout.nav-icon name="bookmark-solid" class="w-3.5 h-3.5 text-brand" />
                @else
                    <x-layout.nav-icon name="bookmark" class="w-3.5 h-3.5 text-neutral-500" />
                @endif
                <span>Tersimpan</span>
                @if($totalBookmarksCount > 0)
                    <span class="ml-0.5 font-mono text-[10px] px-1 py-0.2 bg-white/70 border border-neutral-200 rounded-badge">
                        {{ $totalBookmarksCount }}
                    </span>
                @endif
            </button>

            {{-- Mode Tampilan Switcher (Grid vs List) --}}
            <div class="inline-flex items-center border border-neutral-200 rounded-badge p-0.5 bg-neutral-50">
                <button type="button"
                        wire:click="$set('viewMode', 'grid')"
                        class="p-1.5 rounded-[2px] transition-colors {{ $viewMode === 'grid' ? 'bg-white shadow-none text-neutral-900 font-medium' : 'text-neutral-400 hover:text-neutral-700' }}"
                        title="Tampilan Grid"
                        aria-label="Tampilan Grid">
                    <x-layout.nav-icon name="grid" class="w-4 h-4" />
                </button>
                <button type="button"
                        wire:click="$set('viewMode', 'list')"
                        class="p-1.5 rounded-[2px] transition-colors {{ $viewMode === 'list' ? 'bg-white shadow-none text-neutral-900 font-medium' : 'text-neutral-400 hover:text-neutral-700' }}"
                        title="Tampilan List"
                        aria-label="Tampilan List">
                    <x-layout.nav-icon name="list" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         2. SEARCH BAR REAKTIF & FILTER 13 DIVISI & TIPE MATERI (Design System §8)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            {{-- Kolom Search Input: Reaktif tanpa reload via wire:model.live.debounce.300ms --}}
            <div class="md:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                    <x-layout.nav-icon name="search" class="w-4 h-4" />
                </div>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari judul materi, SOP, topik, atau kata kunci..."
                       class="w-full pl-9 pr-8 py-2 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                       aria-label="Pencarian materi" />

                @if(!empty($search))
                    <button type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-neutral-400 hover:text-neutral-600 focus:outline-none"
                            title="Hapus pencarian">
                        <x-layout.nav-icon name="x-mark" class="w-3.5 h-3.5" />
                    </button>
                @endif
            </div>

            {{-- Kolom Filter Divisi (13 Divisi Tetap sesuai Design System §8) --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedDivisionId"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter berdasarkan divisi">
                    <option value="">Semua Divisi (13 Divisi)</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Kolom Filter Tipe Materi (6 Tipe: Dokumen, Video, Presentasi, Lesson Learned, SOP, Link) --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedType"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-badge text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter berdasarkan tipe materi">
                    <option value="">Semua Tipe Materi</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Baris Filter Aktif & Reset (jika ada filter yang sedang aktif) --}}
        @if(!empty($search) || !empty($selectedDivisionId) || !empty($selectedType) || $onlyBookmarks)
            <div class="mt-3 pt-3 border-t border-neutral-100 flex items-center justify-between flex-wrap gap-2 text-xs">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-neutral-500 font-sans text-[11px]">Filter aktif:</span>

                    @if(!empty($search))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge text-neutral-700 text-[11px]">
                            <span>Pencarian: "{{ $search }}"</span>
                            <button type="button" wire:click="$set('search', '')" class="hover:text-neutral-900">
                                <x-layout.nav-icon name="x-mark" class="w-3 h-3" />
                            </button>
                        </span>
                    @endif

                    @if(!empty($selectedDivisionId))
                        @php
                            $activeDiv = $divisions->firstWhere('id', $selectedDivisionId);
                        @endphp
                        @if($activeDiv)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge text-neutral-700 text-[11px]">
                                <span>Divisi: {{ $activeDiv->name }}</span>
                                <button type="button" wire:click="$set('selectedDivisionId', null)" class="hover:text-neutral-900">
                                    <x-layout.nav-icon name="x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
                    @endif

                    @if(!empty($selectedType))
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge text-neutral-700 text-[11px]">
                            <span>Tipe: {{ $types[$selectedType] ?? $selectedType }}</span>
                            <button type="button" wire:click="$set('selectedType', null)" class="hover:text-neutral-900">
                                <x-layout.nav-icon name="x-mark" class="w-3 h-3" />
                            </button>
                        </span>
                    @endif

                    @if($onlyBookmarks)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-brand-tint border border-brand/30 rounded-badge text-brand-dark text-[11px]">
                            <span>Hanya Bookmark</span>
                            <button type="button" wire:click="$set('onlyBookmarks', false)" class="hover:text-neutral-900">
                                <x-layout.nav-icon name="x-mark" class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                </div>

                {{-- Tombol Reset Seluruh Filter --}}
                <button type="button"
                        wire:click="resetFilters"
                        class="text-xs font-sans text-neutral-500 hover:text-brand underline decoration-neutral-300 transition-colors focus:outline-none">
                    Reset Semua Filter
                </button>
            </div>
        @endif
    </div>

    {{-- =========================================================================
         3. KONTEN DOKUMEN (MODE GRID / MODE LIST)
         Aturan Desain: Tipe materi ditandai LEWAT IKON OUTLINE, BUKAN WARNA BERBEDA
         ========================================================================= --}}
    @if($documents->isEmpty())
        {{-- Empty State Bersih Tanpa Slop/Ilustrasi Berlebihan --}}
        <div class="bg-white border border-neutral-200 rounded-md p-12 text-center">
            <div class="w-12 h-12 mx-auto rounded-md bg-neutral-100 border border-neutral-200 text-neutral-400 flex items-center justify-center mb-3">
                <x-layout.nav-icon name="book" class="w-6 h-6" />
            </div>
            <h3 class="font-sans font-medium text-sm text-neutral-900">
                Tidak ada materi ditemukan
            </h3>
            <p class="font-sans text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                @if(!empty($search) || !empty($selectedDivisionId) || !empty($selectedType) || $onlyBookmarks)
                    Tidak ada dokumen yang cocok dengan kata kunci atau filter saat ini.
                @else
                    Belum ada dokumen knowledge yang terpublikasi di repositori.
                @endif
            </p>
            @if(!empty($search) || !empty($selectedDivisionId) || !empty($selectedType) || $onlyBookmarks)
                <div class="mt-4">
                    <button type="button"
                            wire:click="resetFilters"
                            class="inline-flex items-center px-3 py-1.5 border border-neutral-200 rounded-badge text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                        Reset Filter
                    </button>
                </div>
            @endif
        </div>
    @else
        @if($viewMode === 'grid')
            {{-- =================================================================
                 TAMPILAN GRID (3 Kolom Desktop, 2 Tablet, 1 Mobile)
                 ================================================================= --}}
            <div class="grid grid-cols-1 md:grid-cols-2 desktop:grid-cols-3 gap-4">
                @foreach($documents as $doc)
                    @php
                        $isBookmarked = in_array($doc->id, $bookmarkedDocIds);
                    @endphp
                    <div wire:key="doc-grid-{{ $doc->id }}" class="bg-white border border-neutral-200 rounded-md p-4 flex flex-col justify-between hover:border-neutral-300 transition-colors group">
                        <div>
                            {{-- Header Kartu: Divisi Badge + Ikon Outline Tipe Materi + Bookmark Button --}}
                            <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-neutral-100">
                                <div class="flex items-center gap-2 min-w-0">
                                    {{-- Tag Divisi --}}
                                    @if($doc->division)
                                        <span class="truncate px-1.5 py-0.5 border border-neutral-200 rounded-badge text-neutral-700 bg-neutral-50 font-sans text-[11px]">
                                            {{ $doc->division->name }}
                                        </span>
                                    @endif

                                    {{-- Tipe Materi: Ikon Outline Netral + Teks Tanpa Warna-Warni --}}
                                    <div class="inline-flex items-center gap-1 text-neutral-500 text-[11px] font-sans shrink-0">
                                        <x-layout.nav-icon :name="$doc->type" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span class="capitalize">{{ $types[$doc->type] ?? ucfirst($doc->type) }}</span>
                                    </div>
                                </div>

                                {{-- Tombol Bookmark Per Item --}}
                                <button type="button"
                                        wire:key="bm-grid-{{ $doc->id }}-{{ $isBookmarked ? '1' : '0' }}"
                                        wire:click="toggleBookmark('{{ $doc->id }}')"
                                        class="p-1 rounded-[2px] text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors focus:outline-none focus:ring-1 focus:ring-brand shrink-0"
                                        title="{{ $isBookmarked ? 'Hapus bookmark' : 'Simpan bookmark' }}"
                                        aria-label="{{ $isBookmarked ? 'Hapus bookmark ' . $doc->title : 'Simpan bookmark ' . $doc->title }}">
                                    @if($isBookmarked)
                                        <x-layout.nav-icon name="bookmark-solid" class="w-4 h-4 text-brand" />
                                    @else
                                        <x-layout.nav-icon name="bookmark" class="w-4 h-4 text-neutral-400" />
                                    @endif
                                </button>
                            </div>

                            {{-- Judul & Deskripsi Dokumen --}}
                            <div class="mt-3">
                                <h2 class="font-sans font-semibold text-sm text-neutral-900 group-hover:text-brand transition-colors line-clamp-2 leading-snug">
                                    {{ $doc->title }}
                                </h2>
                                <p class="font-sans text-xs text-neutral-500 line-clamp-3 mt-1.5 leading-relaxed">
                                    {{ $doc->description ?: 'Tidak ada ringkasan deskripsi untuk materi ini.' }}
                                </p>
                            </div>
                        </div>

                        {{-- Footer Kartu: Pembuat, Tanggal, & Link Aksi --}}
                        <div class="pt-3 mt-3 border-t border-neutral-100 flex items-center justify-between text-[11px] font-sans text-neutral-400">
                            <span class="truncate max-w-[140px] text-neutral-500">
                                {{ $doc->creator?->name ?? 'Tim Internal' }}
                            </span>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="font-mono text-neutral-400">
                                    {{ $doc->created_at->format('d/m/Y') }}
                                </span>
                                @if(!empty($doc->external_link))
                                    <a href="{{ $doc->external_link }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="text-neutral-600 hover:text-brand font-medium underline decoration-neutral-200">
                                        Buka &nearr;
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- =================================================================
                 TAMPILAN LIST (Hairline Divider Tipis - Anti Card-Soup §5)
                 ================================================================= --}}
            <div class="bg-white border border-neutral-200 rounded-md divide-y divide-neutral-200 overflow-hidden">
                @foreach($documents as $doc)
                    @php
                        $isBookmarked = in_array($doc->id, $bookmarkedDocIds);
                    @endphp
                    <div wire:key="doc-list-{{ $doc->id }}" class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-neutral-50/50 transition-colors">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            {{-- Ikon Outline Tipe Materi di Kiri --}}
                            <div class="w-8 h-8 rounded-[2px] border border-neutral-200 bg-neutral-50 text-neutral-500 flex items-center justify-center shrink-0 mt-0.5"
                                 title="{{ $types[$doc->type] ?? ucfirst($doc->type) }}">
                                <x-layout.nav-icon :name="$doc->type" class="w-4 h-4 text-neutral-500" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="font-sans font-medium text-sm text-neutral-900 hover:text-brand transition-colors">
                                        {{ $doc->title }}
                                    </h2>

                                    @if($doc->division)
                                        <span class="px-1.5 py-0.5 border border-neutral-200 rounded-badge text-neutral-700 bg-neutral-50 font-sans text-[10px]">
                                            {{ $doc->division->name }}
                                        </span>
                                    @endif

                                    <span class="font-sans text-[10px] text-neutral-400 capitalize">
                                        {{ $types[$doc->type] ?? ucfirst($doc->type) }}
                                    </span>
                                </div>

                                @if(!empty($doc->description))
                                    <p class="font-sans text-xs text-neutral-500 line-clamp-1 mt-0.5">
                                        {{ $doc->description }}
                                    </p>
                                @endif

                                <div class="flex items-center gap-2 mt-1 text-[11px] font-sans text-neutral-400">
                                    <span>Oleh {{ $doc->creator?->name ?? 'Tim Internal' }}</span>
                                    <span>&middot;</span>
                                    <span class="font-mono">{{ $doc->created_at->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kanan List: Aksi External Link + Tombol Bookmark --}}
                        <div class="flex items-center gap-3 self-end sm:self-center shrink-0">
                            @if(!empty($doc->external_link))
                                <a href="{{ $doc->external_link }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-xs font-sans text-neutral-600 hover:text-brand font-medium underline decoration-neutral-200">
                                    Buka Materi &nearr;
                                </a>
                            @endif

                            <button type="button"
                                    wire:key="bm-list-{{ $doc->id }}-{{ $isBookmarked ? '1' : '0' }}"
                                    wire:click="toggleBookmark('{{ $doc->id }}')"
                                    class="p-1.5 rounded-[2px] text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 transition-colors focus:outline-none focus:ring-1 focus:ring-brand"
                                    title="{{ $isBookmarked ? 'Hapus bookmark' : 'Simpan bookmark' }}"
                                    aria-label="{{ $isBookmarked ? 'Hapus bookmark ' . $doc->title : 'Simpan bookmark ' . $doc->title }}">
                                @if($isBookmarked)
                                    <x-layout.nav-icon name="bookmark-solid" class="w-4 h-4 text-brand" />
                                @else
                                    <x-layout.nav-icon name="bookmark" class="w-4 h-4 text-neutral-400" />
                                @endif
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- =========================================================================
             4. PAGINASI REAKTIF LIVEWIRE (Tanpa Reload Halaman)
             ========================================================================= --}}
        <div class="pt-2">
            {{ $documents->links() }}
        </div>
    @endif
</div>
