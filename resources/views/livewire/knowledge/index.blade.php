<div class="space-y-6">
    {{-- =========================================================================
         1. HEADER HALAMAN & TOGGLE BOOKMARK / VIEW MODE (Design System §2 & §5)
         ========================================================================= --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                    Knowledge Repository
                </h1>
                {{-- Counter Badge Total Dokumen (font-mono netral) --}}
                <span class="inline-flex items-center px-2 py-0.5 border border-neutral-200 rounded-badge font-mono text-xs text-neutral-700 bg-white font-medium">
                    {{ $documents->total() }} Materi
                </span>
            </div>
            <p class="font-sans text-xs text-neutral-600 mt-1">
                Pustaka pengetahuan formal perusahaan (SOP, instruksi kerja, peraturan perusahaan, dan kebijakan mutu).
            </p>
            <div class="inline-flex items-center gap-1.5 mt-2 px-2.5 py-1 bg-neutral-100 border border-neutral-200 rounded-badge text-[11px] font-sans text-neutral-600">
                <x-layout.nav-icon name="information-circle" class="w-3.5 h-3.5 text-neutral-500 shrink-0" />
                <span>Referensi resmi korporat. Akses materi bersifat pasif (tidak memberikan poin KPI/XP).</span>
            </div>
        </div>

        {{-- Toolbar Kanan: Filter Cepat Bookmark & Mode Grid/List --}}
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
            {{-- Tombol Toggle Hanya Bookmark --}}
            <button type="button"
                    wire:click="toggleOnlyBookmarks"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-sans font-medium rounded-md border transition-colors focus:outline-none focus:ring-1 focus:ring-brand {{ $onlyBookmarks ? 'bg-brand-tint border-brand/30 text-brand-dark' : 'bg-white border-neutral-200 text-neutral-700 hover:bg-neutral-50' }}">
                @if($onlyBookmarks)
                    <x-layout.nav-icon name="bookmark-solid" class="w-3.5 h-3.5 text-brand" />
                @else
                    <x-layout.nav-icon name="bookmark" class="w-3.5 h-3.5 text-neutral-600" />
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
                        class="p-1.5 rounded-[2px] transition-colors {{ $viewMode === 'grid' ? 'bg-white shadow-none text-neutral-900 font-medium' : 'text-neutral-500 hover:text-neutral-800' }}"
                        title="Tampilan Grid"
                        aria-label="Tampilan Grid">
                    <x-layout.nav-icon name="grid" class="w-4 h-4" />
                </button>
                <button type="button"
                        wire:click="$set('viewMode', 'list')"
                        class="p-1.5 rounded-[2px] transition-colors {{ $viewMode === 'list' ? 'bg-white shadow-none text-neutral-900 font-medium' : 'text-neutral-500 hover:text-neutral-800' }}"
                        title="Tampilan List"
                        aria-label="Tampilan List">
                    <x-layout.nav-icon name="list" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         2. SEARCH BAR REAKTIF & FILTER DIVISI & TIPE MATERI (Design System §8)
         ========================================================================= --}}
    <div class="bg-neutral-50/50 border border-neutral-200 rounded-md p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            {{-- Kolom Search Input: Reaktif tanpa reload via wire:model.live.debounce.300ms --}}
            <div class="md:col-span-4 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                    <x-layout.nav-icon name="search" class="w-4 h-4" />
                </div>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari judul materi, SOP, topik, kata kunci..."
                       class="w-full pl-9 pr-8 py-2 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
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

            {{-- Kolom Filter Topik (PRD v2.0 §3.2) --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedTopicId"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter berdasarkan topik pengetahuan">
                    <option value="">Semua Topik Pengetahuan</option>
                    @foreach($topics as $tpc)
                        <option value="{{ $tpc->id }}">{{ $tpc->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Kolom Filter Divisi --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedDivisionId"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter berdasarkan divisi">
                    <option value="">Semua Divisi ({{ $divisions->count() }} Divisi)</option>
                    @foreach($divisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Kolom Filter Tipe Materi --}}
            <div class="md:col-span-2">
                <select wire:model.live="selectedType"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter berdasarkan tipe materi">
                    <option value="">Semua Tipe</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Baris Filter Aktif & Reset (jika ada filter yang sedang aktif) --}}
        @if(!empty($search) || !empty($selectedTopicId) || !empty($selectedDivisionId) || !empty($selectedType) || $onlyBookmarks)
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

                    @if(!empty($selectedTopicId))
                        @php
                            $activeTopic = $topics->firstWhere('id', $selectedTopicId);
                        @endphp
                        @if($activeTopic)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-brand-tint border border-brand/30 rounded-badge text-brand-dark text-[11px]">
                                <span>Topik: {{ $activeTopic->name }}</span>
                                <button type="button" wire:click="$set('selectedTopicId', null)" class="hover:text-neutral-900">
                                    <x-layout.nav-icon name="x-mark" class="w-3 h-3" />
                                </button>
                            </span>
                        @endif
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
                                <div class="flex items-center gap-1.5 min-w-0 flex-wrap">
                                    {{-- Tag Topik Resmi (PRD v2.0 §3.2) --}}
                                    @if($doc->topic)
                                        <span class="truncate px-2 py-0.5 border border-brand/20 rounded-badge text-brand-dark bg-brand-tint font-sans text-[11px] font-semibold" title="{{ $doc->topic->name }}">
                                            {{ $doc->topic->name }}
                                        </span>
                                    @endif

                                    {{-- Tag Divisi --}}
                                    @if($doc->division)
                                        <span class="truncate px-2 py-0.5 border border-neutral-300 rounded-badge text-neutral-800 bg-neutral-100 font-sans text-[11px] font-medium" title="{{ $doc->division->name }}">
                                            {{ $doc->division->name }}
                                        </span>
                                    @endif

                                    {{-- Tipe Materi: Ikon Outline Netral + Teks Tanpa Warna-Warni --}}
                                    <div class="inline-flex items-center gap-1.5 text-neutral-600 text-[11px] font-sans shrink-0 bg-neutral-50 border border-neutral-200 px-2 py-0.5 rounded-badge">
                                        <x-layout.nav-icon :name="$doc->type" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span class="capitalize font-medium">{{ $types[$doc->type] ?? ucfirst($doc->type) }}</span>
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

                            {{-- Judul & Deskripsi Dokumen (Klik judul untuk membaca) --}}
                            <div class="mt-3">
                                <h2 class="font-sans font-semibold text-sm text-neutral-900 group-hover:text-brand transition-colors line-clamp-2 leading-snug cursor-pointer"
                                    wire:click="showDocument('{{ $doc->id }}')">
                                    {{ $doc->title }}
                                </h2>
                                <p class="font-sans text-xs text-neutral-600 line-clamp-3 mt-1.5 leading-relaxed">
                                    {{ $doc->description ?: 'Tidak ada ringkasan deskripsi untuk materi ini.' }}
                                </p>
                            </div>
                        </div>

                        {{-- Footer Kartu: Pembuat, Tanggal, & Link Aksi --}}
                        <div class="pt-3 mt-3 border-t border-neutral-100 flex items-center justify-between text-[11px] font-sans text-neutral-600 flex-wrap gap-2">
                            <span class="truncate max-w-[120px] text-neutral-700 font-medium">
                                {{ $doc->creator?->name ?? 'Tim Internal' }}
                            </span>
                            <div class="flex items-center gap-2 shrink-0">
                                @if(!empty($doc->download_url))
                                    <a href="{{ $doc->download_url }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       download
                                       class="inline-flex items-center gap-1 text-neutral-700 hover:text-brand font-medium underline decoration-neutral-300"
                                       title="Unduh / Buka Berkas PDF/Office">
                                        <x-layout.nav-icon name="arrow-down-tray" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span>Berkas</span>
                                    </a>
                                @endif
                                @if(!empty($doc->external_link))
                                    <a href="{{ $doc->external_link }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="text-neutral-700 hover:text-brand font-medium underline decoration-neutral-300"
                                       title="Buka Tautan Eksternal">
                                        Tautan
                                    </a>
                                @endif
                                @if($doc->source_ba_id)
                                    <a href="{{ route('ba.show', $doc->source_ba_id) }}"
                                       class="inline-flex items-center gap-0.5 text-brand hover:underline font-mono font-medium"
                                       title="Lihat Berita Acara Terkait">
                                        <x-layout.nav-icon name="document-text" class="w-3.5 h-3.5 text-brand" />
                                        <span>BA</span>
                                    </a>
                                @endif
                                <button type="button"
                                        wire:click="showDocument('{{ $doc->id }}')"
                                        class="text-brand hover:underline font-medium ml-1">
                                    Baca
                                </button>
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
                                    <h2 class="font-sans font-medium text-sm text-neutral-900 hover:text-brand transition-colors cursor-pointer"
                                        wire:click="showDocument('{{ $doc->id }}')">
                                        {{ $doc->title }}
                                    </h2>

                                    @if($doc->topic)
                                        <span class="px-2 py-0.5 border border-brand/20 rounded-badge text-brand-dark bg-brand-tint font-sans text-[10px] font-semibold">
                                            {{ $doc->topic->name }}
                                        </span>
                                    @endif

                                    @if($doc->division)
                                        <span class="px-2 py-0.5 border border-neutral-300 rounded-badge text-neutral-800 bg-neutral-100 font-sans text-[10px] font-medium">
                                            {{ $doc->division->name }}
                                        </span>
                                    @endif

                                    <span class="font-sans text-[10px] text-neutral-600 bg-neutral-50 border border-neutral-200 px-1.5 py-0.5 rounded-badge capitalize">
                                        {{ $types[$doc->type] ?? ucfirst($doc->type) }}
                                    </span>

                                    @if($doc->source_ba_id)
                                        <a href="{{ route('ba.show', $doc->source_ba_id) }}"
                                           class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge text-[10px] text-brand hover:underline font-mono">
                                            <x-layout.nav-icon name="document-text" class="w-3 h-3 text-brand" />
                                            <span>BA #{{ $doc->sourceBa?->nomor_ba ?? 'Terkait' }}</span>
                                        </a>
                                    @endif
                                </div>

                                @if(!empty($doc->description))
                                    <p class="font-sans text-xs text-neutral-600 line-clamp-1 mt-0.5">
                                        {{ $doc->description }}
                                    </p>
                                @endif

                                <div class="flex items-center gap-2 mt-1 text-[11px] font-sans text-neutral-600">
                                    <span>Oleh {{ $doc->creator?->name ?? 'Tim Internal' }}</span>
                                    <span>&middot;</span>
                                    <span class="font-mono text-neutral-500">{{ $doc->created_at->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Kanan List: Aksi External Link + Berkas + Tombol Bookmark --}}
                        <div class="flex items-center gap-3 self-end sm:self-center shrink-0 flex-wrap">
                            @if(!empty($doc->download_url))
                                <a href="{{ $doc->download_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   download
                                   class="inline-flex items-center gap-1 text-xs font-sans text-neutral-700 hover:text-brand font-medium underline decoration-neutral-300"
                                   title="Unduh / Buka Berkas PDF/Office">
                                    <x-layout.nav-icon name="arrow-down-tray" class="w-3.5 h-3.5 text-neutral-500" />
                                    <span>Unduh Berkas</span>
                                </a>
                            @endif

                            @if(!empty($doc->external_link))
                                <a href="{{ $doc->external_link }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="text-xs font-sans text-neutral-600 hover:text-brand font-medium underline decoration-neutral-200">
                                    Tautan
                                </a>
                            @endif

                            <button type="button"
                                    wire:click="showDocument('{{ $doc->id }}')"
                                    class="text-xs font-sans text-brand hover:underline font-medium">
                                Baca
                            </button>

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

    {{-- =========================================================================
         5. MODAL DETAIL DOKUMEN / SOP (PRD v2.0 §3.2)
         ========================================================================= --}}
    @if($viewingDocument)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-neutral-900/50 backdrop-blur-sm transition-opacity" wire:click="closeDocument"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-md bg-white border border-neutral-200 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
                    {{-- Modal Header --}}
                    <div class="bg-neutral-50 px-5 py-4 border-b border-neutral-200 flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1.5">
                                @if($viewingDocument->topic)
                                    <span class="px-2 py-0.5 border border-brand/30 rounded-badge text-brand-dark bg-brand-tint font-sans text-xs font-semibold">
                                        Topik: {{ $viewingDocument->topic->name }}
                                    </span>
                                @endif
                                @if($viewingDocument->division)
                                    <span class="px-2 py-0.5 border border-neutral-200 rounded-badge text-neutral-800 bg-neutral-100 font-sans text-xs font-medium">
                                        Divisi: {{ $viewingDocument->division->name }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 border border-neutral-200 rounded-badge text-neutral-600 bg-neutral-100 font-sans text-xs font-medium">
                                        Umum / Seluruh Perusahaan
                                    </span>
                                @endif
                                <span class="px-2 py-0.5 border border-neutral-200 rounded-badge text-neutral-700 bg-white font-sans text-xs capitalize">
                                    {{ $types[$viewingDocument->type] ?? ucfirst($viewingDocument->type) }}
                                </span>
                            </div>
                            <h2 class="font-sans font-semibold text-base text-neutral-900 leading-snug">
                                {{ $viewingDocument->title }}
                            </h2>
                        </div>
                        <button type="button" wire:click="closeDocument" class="text-neutral-400 hover:text-neutral-600 p-1 focus:outline-none">
                            <x-layout.nav-icon name="x-mark" class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-6 py-5 space-y-4">
                        {{-- Passive Reference Info Banner --}}
                        <div class="flex items-start gap-2.5 p-3 bg-neutral-50 border border-neutral-200 rounded-md text-xs text-neutral-600 font-sans">
                            <x-layout.nav-icon name="information-circle" class="w-4 h-4 text-neutral-500 shrink-0 mt-0.5" />
                            <div>
                                <span class="font-medium text-neutral-800">Referensi Resmi Pengetahuan:</span>
                                <span>Dokumen ini bersifat rujukan pasif untuk menunjang kepatuhan dan mutu kerja PT CPS (tidak memberikan penambahan poin KPI atau XP).</span>
                            </div>
                        </div>

                        {{-- Metadata Dibuat & Tanggal --}}
                        <div class="flex items-center justify-between text-xs font-sans text-neutral-500 border-b border-neutral-100 pb-3">
                            <div>
                                <span>Diterbitkan oleh:</span>
                                <span class="font-medium text-neutral-800">{{ $viewingDocument->creator?->name ?? 'Admin / HRD' }}</span>
                            </div>
                            <div>
                                <span>Tanggal Terbit:</span>
                                <span class="font-mono text-neutral-700">{{ $viewingDocument->created_at->format('d F Y H:i') }}</span>
                            </div>
                        </div>

                        {{-- Deskripsi / Isi Dokumen --}}
                        <div>
                            <h3 class="font-sans font-semibold text-xs text-neutral-700 uppercase tracking-wider mb-1.5">
                                Deskripsi & Instruksi Kerja
                            </h3>
                            <div class="bg-neutral-50/50 p-4 rounded-md border border-neutral-200 font-sans text-xs text-neutral-800 whitespace-pre-line leading-relaxed max-h-60 overflow-y-auto">
                                {{ $viewingDocument->description ?: 'Tidak ada ringkasan teks untuk dokumen ini. Silakan unduh atau buka berkas lampiran di bawah.' }}
                            </div>
                        </div>

                        {{-- Jika Lesson Learned dari BA --}}
                        @if($viewingDocument->sourceBa)
                            <div class="p-3.5 bg-neutral-50 border border-neutral-200 rounded-md flex items-center justify-between gap-3 text-xs font-sans">
                                <div>
                                    <div class="font-medium text-neutral-800">Lesson Learned Terkait Berita Acara:</div>
                                    <div class="text-neutral-600 mt-0.5">Nomor BA: {{ $viewingDocument->sourceBa->nomor_ba }} (Disetujui)</div>
                                </div>
                                <a href="{{ route('ba.show', $viewingDocument->source_ba_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-neutral-300 rounded-md text-neutral-700 hover:text-brand hover:border-brand font-medium transition-colors">
                                    <x-layout.nav-icon name="document-text" class="w-3.5 h-3.5" />
                                    <span>Lihat BA</span>
                                </a>
                            </div>
                        @endif

                        {{-- Pratinjau Berkas Drive (PDF / Office) langsung di halaman --}}
                        @if(!empty($viewingDocument->preview_url))
                            <div class="space-y-2">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-neutral-900 font-sans">
                                    Pratinjau Berkas
                                </h3>
                                <div class="rounded-md border border-neutral-200 overflow-hidden bg-neutral-50"
                                     style="aspect-ratio: 4 / 3;">
                                    <iframe src="{{ $viewingDocument->preview_url }}"
                                            class="w-full h-full border-0"
                                            title="Pratinjau {{ $viewingDocument->title }}"
                                            loading="lazy"></iframe>
                                </div>
                                <p class="text-[11px] text-neutral-500 font-sans">
                                    Pratinjau disediakan Google Drive. Gunakan tombol di bawah bila berkas tidak tampil.
                                </p>
                            </div>
                        @endif

                        {{-- Tombol Aksi Berkas / Tautan Eksternal --}}
                        <div class="flex items-center gap-3 pt-2 flex-wrap">
                            @if(!empty($viewingDocument->download_url))
                                <a href="{{ $viewingDocument->download_url }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   download
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white text-xs font-sans font-medium rounded-md hover:bg-brand-dark transition-colors shadow-none">
                                    <x-layout.nav-icon name="arrow-down-tray" class="w-4 h-4 text-white" />
                                    <span>Unduh / Buka Berkas (PDF / Office)</span>
                                </a>
                            @endif

                            @if(!empty($viewingDocument->external_link))
                                <a href="{{ $viewingDocument->external_link }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-neutral-300 text-neutral-700 text-xs font-sans font-medium rounded-md hover:bg-neutral-50 hover:text-brand transition-colors">
                                    <x-layout.nav-icon name="link" class="w-4 h-4 text-neutral-500" />
                                    <span>Buka Tautan Eksternal</span>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-neutral-50 px-6 py-3 border-t border-neutral-200 flex items-center justify-between">
                        <button type="button"
                                wire:click="toggleBookmark('{{ $viewingDocument->id }}')"
                                class="inline-flex items-center gap-1.5 text-xs font-sans text-neutral-700 hover:text-brand font-medium">
                            <x-layout.nav-icon :name="in_array($viewingDocument->id, $bookmarkedDocIds) ? 'bookmark-solid' : 'bookmark'" class="w-4 h-4 {{ in_array($viewingDocument->id, $bookmarkedDocIds) ? 'text-brand' : 'text-neutral-500' }}" />
                            <span>{{ in_array($viewingDocument->id, $bookmarkedDocIds) ? 'Tersimpan di Bookmark' : 'Simpan ke Bookmark' }}</span>
                        </button>
                        <button type="button" wire:click="closeDocument" class="px-4 py-1.5 text-xs font-sans font-medium border border-neutral-300 rounded-md bg-white hover:bg-neutral-100 text-neutral-700 transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
