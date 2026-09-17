<div class="space-y-6">
    {{-- =========================================================================
         1. HEADER HALAMAN & KONTROL ADMIN KATEGORI (Design System §2 & §5)
         ========================================================================= --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="font-sans font-semibold text-xl text-neutral-900 leading-tight">
                    Learning
                </h1>
                {{-- Counter Badge Total Materi (font-mono netral) --}}
                <span class="inline-flex items-center px-2 py-0.5 border border-neutral-200 rounded-badge font-mono text-xs text-neutral-700 bg-white font-medium">
                    {{ $materials->total() }} Materi
                </span>
            </div>
            <p class="font-sans text-xs text-neutral-600 mt-1">
                Modul peningkatan kompetensi, standardisasi operasional, dan materi evaluasi berkelanjutan PT CPS.
            </p>
        </div>

        {{-- Toolbar Kanan: Metrik Belajar & Aksi Admin --}}
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
            {{-- Metrik Ringkas Pengguna --}}
            <div class="hidden sm:flex items-center gap-2.5 px-3.5 py-1.5 bg-white border border-neutral-200 rounded-md text-xs font-sans text-neutral-600 shadow-xs">
                <span class="font-medium text-neutral-600">Selesai:</span>
                <span class="font-mono font-bold text-sm text-brand">{{ $totalCompletedCount }}</span>
                <span class="text-neutral-300">&vert;</span>
                <span class="font-medium text-neutral-600">Sedang Berjalan:</span>
                <span class="font-mono font-bold text-sm text-neutral-900">{{ $totalInProgressCount }}</span>
            </div>

            {{-- Tombol Kelola Kategori (Hanya untuk Admin & Quality - DoD #3) --}}
            @if(auth()->user()?->hasAnyRole(['admin', 'quality']))
                <button type="button"
                        wire:click="openCategoryModal"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-neutral-200 hover:border-brand hover:text-brand text-neutral-700 rounded-md text-xs font-sans font-medium transition-colors focus:outline-none focus:ring-1 focus:ring-brand">
                    <x-layout.nav-icon name="folder-cog" class="w-4 h-4" />
                    <span>+ Kategori Baru</span>
                </button>
            @endif

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

    {{-- Banner Notifikasi Sukses Pembuatan Kategori --}}
    @if(session()->has('category_success'))
        <div x-data="{ show: true }"
             x-show="show"
             class="p-4 bg-brand-tint border border-brand/40 rounded-badge flex items-center justify-between gap-3 text-brand-dark">
            <div class="flex items-center gap-2.5">
                <x-layout.nav-icon name="badge-check" class="w-5 h-5 text-brand shrink-0" />
                <span class="font-sans text-xs font-medium">{{ session('category_success') }}</span>
            </div>
            <button type="button" @click="show = false" class="text-brand hover:text-brand-dark p-1">
                <x-layout.nav-icon name="x-mark" class="w-4 h-4" />
            </button>
        </div>
    @endif

    {{-- =========================================================================
         2. PENCARIAN REAKTIF & FILTER KATEGORI (ADMIN-MANAGED) & 7 JENIS MATERI
         ========================================================================= --}}
    <div class="bg-neutral-50/50 border border-neutral-200 rounded-md p-4 space-y-3">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
            {{-- Kolom Search Input: Reaktif tanpa reload via wire:model.live.debounce.300ms --}}
            <div class="md:col-span-4 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                    <x-layout.nav-icon name="search" class="w-4 h-4" />
                </div>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari judul materi, SOP, topik pembelajaran..."
                       class="w-full pl-9 pr-8 py-2 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors" />

                @if(!empty($search))
                    <button type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-neutral-400 hover:text-neutral-600 focus:outline-none"
                            title="Hapus pencarian">
                        <x-layout.nav-icon name="x-mark" class="w-3.5 h-3.5" />
                    </button>
                @endif
            </div>

            {{-- Kolom Filter Kategori Materi (Admin-Managed - DoD #3) --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedCategoryId"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter berdasarkan kategori">
                    <option value="">Semua Kategori ({{ $categories->count() }})</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }} ({{ $cat->materials_count }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Kolom Filter 7 Jenis Materi (Dokumen, Video, Presentasi, Artikel, Tutorial, Link, File Pendukung) --}}
            <div class="md:col-span-3">
                <select wire:model.live="selectedType"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter berdasarkan tipe materi">
                    <option value="">Semua Jenis Materi (7 Jenis)</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Kolom Filter Status Pembelajaran Pengguna --}}
            <div class="md:col-span-2">
                <select wire:model.live="selectedProgressFilter"
                        class="w-full py-2 px-3 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors"
                        aria-label="Filter status pembelajaran">
                    <option value="all">Semua Status</option>
                    <option value="not_started">Belum Dimulai</option>
                    <option value="in_progress">Sedang Dipelajari</option>
                    <option value="completed">Selesai (100%)</option>
                </select>
            </div>
        </div>

        {{-- Baris Filter Aktif & Reset --}}
        @if(!empty($search) || !empty($selectedCategoryId) || !empty($selectedType) || $selectedProgressFilter !== 'all')
            <div class="pt-2 border-t border-neutral-100 flex items-center justify-between flex-wrap gap-2 text-xs">
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

                    @if(!empty($selectedCategoryId))
                        @php
                            $activeCat = $categories->firstWhere('id', $selectedCategoryId);
                        @endphp
                        @if($activeCat)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-neutral-100 border border-neutral-200 rounded-badge text-neutral-700 text-[11px]">
                                <span>Kategori: {{ $activeCat->name }}</span>
                                <button type="button" wire:click="$set('selectedCategoryId', null)" class="hover:text-neutral-900">
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

                    @if($selectedProgressFilter !== 'all')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-brand-tint border border-brand/30 rounded-badge text-brand-dark text-[11px]">
                            <span>Status: {{ ucfirst(str_replace('_', ' ', $selectedProgressFilter)) }}</span>
                            <button type="button" wire:click="$set('selectedProgressFilter', 'all')" class="hover:text-neutral-900">
                                <x-layout.nav-icon name="x-mark" class="w-3 h-3" />
                            </button>
                        </span>
                    @endif
                </div>

                <button type="button"
                        wire:click="resetFilters"
                        class="text-xs font-sans text-neutral-500 hover:text-brand underline decoration-neutral-300 transition-colors focus:outline-none">
                    Reset Semua Filter
                </button>
            </div>
        @endif
    </div>

    {{-- =========================================================================
         3. DAFTAR MATERI DENGAN PROGRESS BAR TIPIS HIJAU PER MATERI PER USER (DoD #1)
         ========================================================================= --}}
    @if($materials->isEmpty())
        <div class="bg-white border border-neutral-200 rounded-md p-12 text-center">
            <div class="w-12 h-12 mx-auto rounded-md bg-neutral-100 border border-neutral-200 text-neutral-400 flex items-center justify-center mb-3">
                <x-layout.nav-icon name="academic" class="w-6 h-6" />
            </div>
            <h3 class="font-sans font-medium text-sm text-neutral-900">
                Tidak ada materi pembelajaran ditemukan
            </h3>
            <p class="font-sans text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                @if(!empty($search) || !empty($selectedCategoryId) || !empty($selectedType) || $selectedProgressFilter !== 'all')
                    Tidak ada materi yang sesuai dengan kata kunci atau filter saat ini.
                @else
                    Belum ada materi pembelajaran yang dipublikasikan pada sistem.
                @endif
            </p>
            @if(!empty($search) || !empty($selectedCategoryId) || !empty($selectedType) || $selectedProgressFilter !== 'all')
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
                 MODE GRID: 3 Kolom Desktop, 2 Tablet, 1 Mobile
                 ================================================================= --}}
            <div class="grid grid-cols-1 md:grid-cols-2 desktop:grid-cols-3 gap-4">
                @foreach($materials as $material)
                    @php
                        $userProg = $userProgresses->get($material->id);
                        $percent = $userProg ? $userProg->progress_percent : 0;
                        $isCompleted = $percent >= 100;
                        $hasPostTest = (bool) $material->postTest;
                    @endphp
                    <div wire:key="learning-grid-{{ $material->id }}"
                         class="bg-white border border-neutral-200 rounded-md p-4 flex flex-col justify-between hover:border-neutral-300 transition-colors group">
                        
                        <div>
                            {{-- Header Kartu: Kategori Badge + Ikon Outline Jenis Materi + Badge Post-Test --}}
                            <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-neutral-100">
                                <div class="flex items-center gap-2 min-w-0">
                                    {{-- Kategori Materi (Standar Regulasi / Kurikulum) --}}
                                    @if($material->category)
                                        <span class="truncate px-2 py-0.5 border border-neutral-300 rounded-badge text-neutral-800 bg-neutral-100 font-sans text-[11px] font-medium" title="{{ $material->category->name }}">
                                            {{ $material->category->name }}
                                        </span>
                                    @endif

                                    {{-- 7 Jenis Materi: Ikon Outline Netral + Teks Netral (Design System §5) --}}
                                    <div class="inline-flex items-center gap-1.5 text-neutral-600 text-[11px] font-sans shrink-0 bg-neutral-50 border border-neutral-200 px-2 py-0.5 rounded-badge">
                                        <x-layout.nav-icon :name="$material->type" class="w-3.5 h-3.5 text-neutral-500" />
                                        <span class="capitalize font-medium">{{ $types[$material->type] ?? ucfirst($material->type) }}</span>
                                    </div>
                                </div>

                                {{-- Indikator Post-Test Tersedia (Interactive Assessment) --}}
                                @if($hasPostTest)
                                    <span class="px-2 py-0.5 border border-brand/30 rounded-badge text-[10px] font-sans font-medium text-brand-dark bg-brand-tint shrink-0" title="Materi memiliki Post-Test">
                                        Post-Test
                                    </span>
                                @endif
                            </div>

                            {{-- Judul & Deskripsi Materi --}}
                            <div class="mt-3">
                                <a href="{{ route('learning.show', $material->id) }}" class="block focus:outline-none">
                                    <h2 class="font-sans font-semibold text-sm text-neutral-900 group-hover:text-brand transition-colors line-clamp-2 leading-snug">
                                        {{ $material->title }}
                                    </h2>
                                </a>
                                <p class="font-sans text-xs text-neutral-600 line-clamp-2 mt-1.5 leading-relaxed">
                                    {{ $material->description ?: 'Tidak ada deskripsi pengantar untuk materi ini.' }}
                                </p>
                            </div>
                        </div>

                        {{-- Footer Kartu: Progress Bar Tipis Hijau per Materi per User (DoD #1) --}}
                        <div class="pt-3 mt-4 border-t border-neutral-100 space-y-2.5">
                            <div>
                                <div class="flex items-center justify-between text-[11px] font-sans mb-1">
                                    <span class="text-neutral-500">
                                        @if($isCompleted)
                                            <span class="text-brand font-medium">Selesai</span>
                                        @elseif($percent > 0)
                                            <span class="text-neutral-700 font-medium">Sedang Berjalan</span>
                                        @else
                                            <span class="text-neutral-400">Belum Mulai</span>
                                        @endif
                                    </span>
                                    <span class="font-mono text-neutral-600 font-semibold">
                                        {{ $percent }}%
                                    </span>
                                </div>
                                
                                {{-- Progress Bar Tipis Hijau (#0B7840) sesuai Design System §5 --}}
                                <div class="w-full bg-neutral-100 rounded-[2px] h-1.5 overflow-hidden">
                                    <div class="bg-brand h-1.5 transition-all duration-300 rounded-[2px]"
                                         style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <span class="font-mono text-[10px] text-neutral-400">
                                    {{ $material->created_at->format('d/m/Y') }}
                                </span>
                                
                                <a href="{{ route('learning.show', $material->id) }}"
                                   class="inline-flex items-center gap-1 text-xs font-sans font-medium text-brand hover:text-brand-dark transition-colors">
                                    <span>Pelajari</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- =================================================================
                 MODE LIST: Hairline Divider Tipis (Anti Card-Soup Design System §5)
                 ================================================================= --}}
            <div class="bg-white border border-neutral-200 rounded-md divide-y divide-neutral-200 overflow-hidden">
                @foreach($materials as $material)
                    @php
                        $userProg = $userProgresses->get($material->id);
                        $percent = $userProg ? $userProg->progress_percent : 0;
                        $isCompleted = $percent >= 100;
                        $hasPostTest = (bool) $material->postTest;
                    @endphp
                    <div wire:key="learning-list-{{ $material->id }}"
                         class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-neutral-50/50 transition-colors">
                        
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            {{-- Ikon Outline Jenis Materi di Sisi Kiri --}}
                            <div class="w-8 h-8 rounded-[2px] border border-neutral-200 bg-neutral-50 text-neutral-500 flex items-center justify-center shrink-0 mt-0.5"
                                 title="{{ $types[$material->type] ?? ucfirst($material->type) }}">
                                <x-layout.nav-icon :name="$material->type" class="w-4 h-4 text-neutral-500" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('learning.show', $material->id) }}" class="focus:outline-none">
                                        <h2 class="font-sans font-medium text-sm text-neutral-900 hover:text-brand transition-colors">
                                            {{ $material->title }}
                                        </h2>
                                    </a>

                                    @if($material->category)
                                        <span class="px-2 py-0.5 border border-neutral-300 rounded-badge text-neutral-800 bg-neutral-100 font-sans text-[10px] font-medium">
                                            {{ $material->category->name }}
                                        </span>
                                    @endif

                                    <span class="font-sans text-[10px] text-neutral-600 bg-neutral-50 border border-neutral-200 px-1.5 py-0.5 rounded-badge capitalize">
                                        {{ $types[$material->type] ?? ucfirst($material->type) }}
                                    </span>

                                    @if($hasPostTest)
                                        <span class="px-2 py-0.5 border border-brand/30 rounded-badge text-[10px] font-sans font-medium text-brand-dark bg-brand-tint">
                                            Post-Test
                                        </span>
                                    @endif
                                </div>

                                @if(!empty($material->description))
                                    <p class="font-sans text-xs text-neutral-600 line-clamp-1 mt-0.5">
                                        {{ $material->description }}
                                    </p>
                                @endif

                                <div class="flex items-center gap-2 mt-1 text-[11px] font-sans text-neutral-400">
                                    <span>Oleh {{ $material->creator?->name ?? 'Tim Internal' }}</span>
                                    <span>&middot;</span>
                                    <span class="font-mono">{{ $material->created_at->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Progress Bar Tipis Hijau & Aksi Sisi Kanan --}}
                        <div class="flex items-center gap-4 shrink-0 sm:w-64 self-end sm:self-center">
                            <div class="flex-1 min-w-[120px]">
                                <div class="flex items-center justify-between text-[11px] font-sans mb-1">
                                    <span class="text-neutral-500 text-[10px]">
                                        {{ $isCompleted ? 'Selesai' : ($percent > 0 ? 'Sedang Berjalan' : 'Belum Mulai') }}
                                    </span>
                                    <span class="font-mono text-neutral-700 text-[10px] font-semibold">
                                        {{ $percent }}%
                                    </span>
                                </div>
                                <div class="w-full bg-neutral-100 rounded-[2px] h-1.5 overflow-hidden">
                                    <div class="bg-brand h-1.5 transition-all duration-300 rounded-[2px]"
                                         style="width: {{ $percent }}%;"></div>
                                </div>
                            </div>

                            <a href="{{ route('learning.show', $material->id) }}"
                               class="px-3 py-1.5 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 hover:text-brand transition-colors shrink-0">
                                Pelajari
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Pagination Navigasi --}}
        <div class="pt-2">
            {{ $materials->links() }}
        </div>
    @endif

    {{-- =========================================================================
         4. MODAL CEPAT TAMBAH KATEGORI (Khusus Role Admin & Quality - DoD #3)
         ========================================================================= --}}
    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                
                {{-- Backdrop Overlay --}}
                <div class="fixed inset-0 bg-neutral-900/40 transition-opacity"
                     wire:click="closeCategoryModal"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                {{-- Modal Card --}}
                <div class="inline-block align-bottom bg-white rounded-md text-left overflow-hidden border border-neutral-300 shadow-none transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-[2px] bg-brand-tint border border-brand/20 text-brand flex items-center justify-center">
                                <x-layout.nav-icon name="folder-cog" class="w-4 h-4" />
                            </div>
                            <h3 class="font-sans font-semibold text-sm text-neutral-900" id="modal-title">
                                Tambah Kategori Learning Baru (Admin)
                            </h3>
                        </div>
                        <button type="button"
                                wire:click="closeCategoryModal"
                                class="text-neutral-400 hover:text-neutral-700 p-1">
                            <x-layout.nav-icon name="x-mark" class="w-4 h-4" />
                        </button>
                    </div>

                    <form wire:submit="saveCategory" class="mt-4 space-y-4">
                        <div>
                            <label for="newCategoryName" class="block text-xs font-sans font-medium text-neutral-700 mb-1.5">
                                Nama Kategori Materi <span class="text-neutral-400">*</span>
                            </label>
                            <input type="text"
                                   id="newCategoryName"
                                   wire:model="newCategoryName"
                                   placeholder="Contoh: Standar Operasional & K3, Otomasi Mesin..."
                                   class="w-full px-3 py-2 text-xs font-sans bg-white border border-neutral-200 rounded-md text-neutral-900 placeholder-neutral-400 focus:outline-none focus:ring-1 focus:ring-brand focus:border-brand transition-colors" />
                            @error('newCategoryName')
                                <span class="text-[11px] font-sans text-neutral-600 font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                            <p class="text-[11px] font-sans text-neutral-400 mt-1">
                                Kategori yang dibuat akan langsung tersedia dalam filter materi bagi seluruh pegawai PT CPS.
                            </p>
                        </div>

                        <div class="pt-3 border-t border-neutral-100 flex items-center justify-end gap-2">
                            <button type="button"
                                    wire:click="closeCategoryModal"
                                    class="px-3 py-1.5 border border-neutral-200 rounded-md text-xs font-sans font-medium text-neutral-700 bg-white hover:bg-neutral-50 transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-1.5 bg-brand hover:bg-brand-dark text-white rounded-md text-xs font-sans font-medium transition-colors focus:outline-none focus:ring-1 focus:ring-brand disabled:opacity-50">
                                <span wire:loading.remove wire:target="saveCategory">Simpan Kategori</span>
                                <span wire:loading wire:target="saveCategory">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
