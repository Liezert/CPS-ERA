<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-neutral-200">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900 tracking-tight">Mission & Game</h1>
            <p class="text-sm text-neutral-500 mt-1">
                Katalog tantangan studi kasus dan kuis manufaktur untuk mengasah pemecahan masalah serta memperoleh poin reward.
            </p>
        </div>
    </div>

    {{-- Metrics Summary Strip (Design System §5: List sederhana hairline divider, tanpa card-soup) --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-4 grid grid-cols-2 md:grid-cols-4 gap-4 divide-y md:divide-y-0 md:divide-x divide-neutral-200">
        <div class="px-2 py-1">
            <span class="text-xs text-neutral-500 uppercase tracking-wider block">Total Misi</span>
            <span class="text-xl font-semibold text-neutral-900 mt-0.5 block font-mono">{{ $totalMissions }}</span>
        </div>
        <div class="px-2 py-1 pt-3 md:pt-1">
            <span class="text-xs text-neutral-500 uppercase tracking-wider block">Misi Selesai</span>
            <span class="text-xl font-semibold text-brand mt-0.5 block font-mono">{{ $completedMissionsCount }}</span>
        </div>
        <div class="px-2 py-1 pt-3 md:pt-1">
            <span class="text-xs text-neutral-500 uppercase tracking-wider block">Poin Tersedia</span>
            <span class="text-xl font-semibold text-neutral-900 mt-0.5 block font-mono">{{ $totalPointsAvailable }}</span>
        </div>
        <div class="px-2 py-1 pt-3 md:pt-1">
            <span class="text-xs text-neutral-500 uppercase tracking-wider block">Poin Anda Peroleh</span>
            <span class="text-xl font-semibold text-brand mt-0.5 block font-mono">{{ $earnedPoints }}</span>
        </div>
    </div>

    {{-- Filter & Search Bar --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-3 sm:p-4 space-y-3">
        <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            {{-- Search Bar Reaktif --}}
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-400">
                    @include('components.layout.nav-icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                </span>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari judul misi, studi kasus, atau materi kuis..."
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-neutral-50 border border-neutral-200 rounded-md focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand text-neutral-900 placeholder:text-neutral-400 transition" />
            </div>

            {{-- Quick Filter Controls --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Filter Tipe Misi --}}
                <select wire:model.live="selectedType"
                        class="text-xs py-1.5 px-2.5 bg-white border border-neutral-200 rounded-md text-neutral-700 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand">
                    <option value="all">Semua Tipe (Quiz & Case Study)</option>
                    <option value="mission_quiz">Quiz Cepat</option>
                    <option value="mission_case_study">Studi Kasus</option>
                </select>

                {{-- Filter Status Pengerjaan --}}
                <select wire:model.live="selectedStatus"
                        class="text-xs py-1.5 px-2.5 bg-white border border-neutral-200 rounded-md text-neutral-700 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand">
                    <option value="all">Semua Status</option>
                    <option value="uncompleted">Belum Dikerjakan</option>
                    <option value="completed">Sudah Selesai</option>
                </select>

                {{-- Switcher Grid vs List --}}
                <div class="inline-flex border border-neutral-200 rounded-md overflow-hidden" role="group">
                    <button type="button"
                            wire:click="setViewMode('grid')"
                            class="p-1.5 text-xs {{ $viewMode === 'grid' ? 'bg-neutral-100 text-neutral-900' : 'bg-white text-neutral-500 hover:text-neutral-900' }} transition"
                            title="Tampilan Grid">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                    </button>
                    <button type="button"
                            wire:click="setViewMode('list')"
                            class="p-1.5 text-xs {{ $viewMode === 'list' ? 'bg-neutral-100 text-neutral-900' : 'bg-white text-neutral-500 hover:text-neutral-900' }} transition border-l border-neutral-200"
                            title="Tampilan List Tabel">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Misi: Grid Mode vs List Mode --}}
    @if ($missions->isEmpty())
        <div class="bg-white border border-neutral-200 rounded-lg p-12 text-center">
            <div class="w-12 h-12 rounded-full bg-neutral-50 border border-neutral-200 flex items-center justify-center mx-auto text-neutral-400 mb-3">
                @include('components.layout.nav-icon', ['name' => 'puzzle', 'class' => 'w-6 h-6'])
            </div>
            <h3 class="text-sm font-medium text-neutral-900">Tidak ada misi ditemukan</h3>
            <p class="text-xs text-neutral-500 mt-1 max-w-sm mx-auto">
                Coba sesuaikan kata kunci pencarian atau reset filter tipe dan status pengerjaan misi.
            </p>
            <button type="button"
                    wire:click="resetFilters"
                    class="mt-4 px-3 py-1.5 text-xs font-medium text-brand border border-brand rounded-md hover:bg-brand-tint transition">
                Reset Filter
            </button>
        </div>
    @elseif ($viewMode === 'grid')
        {{-- TAMPILAN GRID KARTU (Design System §4) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($missions as $mission)
                @php
                    $attempt = $mission->currentUserAttempt;
                    $isPassed = $attempt && $attempt->passed;
                    $isCaseStudy = $mission->isCaseStudy();
                @endphp
                <div wire:key="mission-grid-{{ $mission->id }}"
                     class="bg-white border border-neutral-200 rounded-lg p-5 flex flex-col justify-between hover:border-neutral-300 transition space-y-4">
                    
                    <div>
                        {{-- Meta Bar: Tipe & Reward Poin --}}
                        <div class="flex items-center justify-between gap-2 mb-2.5">
                            {{-- Badge Tipe (Stempel 2px) --}}
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-sans uppercase tracking-wider text-neutral-600 border border-neutral-200 px-2 py-0.5 rounded-[2px]">
                                @if ($isCaseStudy)
                                    @include('components.layout.nav-icon', ['name' => 'book', 'class' => 'w-3.5 h-3.5 text-neutral-500'])
                                    Studi Kasus
                                @else
                                    @include('components.layout.nav-icon', ['name' => 'puzzle', 'class' => 'w-3.5 h-3.5 text-neutral-500'])
                                    Quiz Cepat
                                @endif
                            </span>

                            {{-- Badge Reward Poin (Stempel 2px) --}}
                            <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-brand-dark bg-brand-tint border border-brand/30 px-2 py-0.5 rounded-[2px]">
                                +{{ $mission->points_reward }} Poin
                            </span>
                        </div>

                        {{-- Judul Misi --}}
                        <a href="{{ route('missions.show', $mission->id) }}"
                           class="block text-base font-semibold text-neutral-900 hover:text-brand transition line-clamp-2">
                            {{ $mission->title }}
                        </a>

                        {{-- Cuplikan Deskripsi Kasus / Soal --}}
                        <p class="text-xs text-neutral-500 mt-2 line-clamp-3 leading-relaxed">
                            {{ $mission->description ?: 'Tantangan evaluasi kompetensi manufaktur dan pemecahan masalah operasional pabrik.' }}
                        </p>
                    </div>

                    <div class="pt-3 border-t border-neutral-200 flex items-center justify-between">
                        {{-- Status Pengerjaan Pengguna --}}
                        <div>
                            @if ($isPassed)
                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-brand-dark border border-brand px-2 py-0.5 rounded-[2px]">
                                    <svg class="w-3 h-3 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    Selesai (Skor {{ $attempt->score }})
                                </span>
                            @elseif ($attempt)
                                <span class="inline-flex items-center text-[11px] font-medium text-neutral-600 border border-neutral-300 px-2 py-0.5 rounded-[2px]">
                                    Belum Lulus (Skor {{ $attempt->score }})
                                </span>
                            @else
                                <span class="inline-flex items-center text-[11px] text-neutral-500 border border-neutral-200 px-2 py-0.5 rounded-[2px]">
                                    Belum Dikerjakan
                                </span>
                            @endif
                        </div>

                        {{-- Tombol Aksi Utama --}}
                        <a href="{{ route('missions.show', $mission->id) }}"
                           class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium bg-brand text-white rounded-lg hover:bg-brand-dark transition focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-1">
                            {{ $isPassed ? 'Buka Kembali' : ($attempt ? 'Coba Ulang' : 'Mulai Misi') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- TAMPILAN LIST TABEL (Design System §5: Hairline divider, bukan card soup) --}}
        <div class="bg-white border border-neutral-200 rounded-lg overflow-hidden">
            <div class="divide-y divide-neutral-200">
                @foreach ($missions as $mission)
                    @php
                        $attempt = $mission->currentUserAttempt;
                        $isPassed = $attempt && $attempt->passed;
                        $isCaseStudy = $mission->isCaseStudy();
                    @endphp
                    <div wire:key="mission-list-{{ $mission->id }}"
                         class="p-4 hover:bg-neutral-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        
                        <div class="flex items-start gap-3 flex-1">
                            <div class="w-8 h-8 rounded-md bg-neutral-50 border border-neutral-200 flex items-center justify-center text-neutral-500 shrink-0 mt-0.5">
                                @if ($isCaseStudy)
                                    @include('components.layout.nav-icon', ['name' => 'book', 'class' => 'w-4 h-4'])
                                @else
                                    @include('components.layout.nav-icon', ['name' => 'puzzle', 'class' => 'w-4 h-4'])
                                @endif
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] uppercase tracking-wider text-neutral-500 font-sans">
                                        {{ $isCaseStudy ? 'Studi Kasus' : 'Quiz Cepat' }}
                                    </span>
                                    <span class="text-neutral-300">•</span>
                                    <span class="text-[11px] font-mono font-medium text-brand">
                                        +{{ $mission->points_reward }} Poin
                                    </span>
                                </div>
                                <a href="{{ route('missions.show', $mission->id) }}"
                                   class="text-sm font-semibold text-neutral-900 hover:text-brand transition block">
                                    {{ $mission->title }}
                                </a>
                                <p class="text-xs text-neutral-500 line-clamp-1">
                                    {{ $mission->description }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-neutral-100">
                            @if ($isPassed)
                                <span class="text-xs font-medium text-brand-dark border border-brand px-2 py-0.5 rounded-[2px]">
                                    Selesai ({{ $attempt->score }})
                                </span>
                            @elseif ($attempt)
                                <span class="text-xs font-medium text-neutral-600 border border-neutral-300 px-2 py-0.5 rounded-[2px]">
                                    Skor {{ $attempt->score }}
                                </span>
                            @else
                                <span class="text-xs text-neutral-400 border border-neutral-200 px-2 py-0.5 rounded-[2px]">
                                    Belum Mulai
                                </span>
                            @endif

                            <a href="{{ route('missions.show', $mission->id) }}"
                               class="px-3 py-1.5 text-xs font-medium bg-brand text-white rounded-lg hover:bg-brand-dark transition focus:outline-none">
                                {{ $isPassed ? 'Buka Kembali' : 'Kerjakan' }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Pagination --}}
    @if ($missions->hasPages())
        <div class="pt-2">
            {{ $missions->links() }}
        </div>
    @endif
</div>
