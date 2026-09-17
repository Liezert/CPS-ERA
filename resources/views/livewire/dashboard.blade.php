<div>
    {{-- =========================================================================
         1. HEADER SAPAAN USER & TOMBOL AKSI UTAMA (Design System §2 & §5)
         ========================================================================= --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        {{-- Kiri: Avatar + Sapaan + Chip Level --}}
        <div class="flex items-center gap-4">
            {{-- Avatar (Foto Profil atau Inisial jika belum ada foto) --}}
            @if ($user->avatar_url)
                <img src="{{ asset($user->avatar_url) }}"
                     alt="{{ $user->name }}"
                     class="w-12 h-12 rounded-md object-cover border border-neutral-200 shrink-0">
            @else
                <div class="w-12 h-12 rounded-md bg-brand-tint border border-brand/20 text-brand-dark font-sans font-semibold text-base flex items-center justify-center shrink-0">
                    {{ $initials }}
                </div>
            @endif

            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="font-sans font-semibold text-lg text-neutral-900 leading-tight">
                        Selamat bertugas, {{ $user->name }}
                    </h1>
                    
                    {{-- Chip Level (Hijau diizinkan di chip level) --}}
                    <span class="inline-flex items-center bg-brand-tint text-brand-dark border border-brand/20 font-mono text-xs font-medium px-2.5 py-0.5 rounded-badge">
                        Level {{ $currentLevel }}
                    </span>
                </div>

                <p class="font-sans text-xs text-neutral-600 mt-1 flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-neutral-800 bg-white px-1.5 py-0.5 border border-neutral-200 rounded-badge">
                        {{ $user->employee_id ?? 'CPS-00124' }}
                    </span>
                    <span>&middot;</span>
                    <span class="font-medium text-neutral-700">{{ $user->jabatan ?? 'Operator' }}</span>
                    @if ($user->division)
                        <span>&middot;</span>
                        <span class="font-medium text-neutral-800">{{ $user->division->name }}</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Kanan: Satu Tombol Aksi Utama (Tanpa Panah per Anti-AI-Slop §6 & DoD) --}}
        <div class="shrink-0">
            <x-ui.button href="{{ route('ba.create') }}" variant="primary">
                Buat Laporan CAPA
            </x-ui.button>
        </div>
    </div>

    {{-- =========================================================================
         2. GRID METRIC CARD (4 Kolom Desktop, 2 Tablet, 1 Mobile)
         Sesuai PRD v2.0 §3.6: XP & Level, Learning Progress, KPI Contribution, Poin CPS ERA Tahun Ini
         ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 desktop:grid-cols-4 gap-4 mb-6">
        {{-- Metric 1: XP & Level --}}
        <x-ui.metric-card label="XP & Level" value="{{ number_format($xp) }}" unit="XP">
            <div class="space-y-1.5 w-full">
                <div class="flex items-center justify-between text-[11px] text-neutral-600 font-sans">
                    <span class="font-medium text-neutral-700">Level {{ $currentLevel }} &rarr; Lv. {{ $nextLevel }}</span>
                    <span class="font-mono font-medium">{{ $levelProgressPercent }}%</span>
                </div>
                <div class="w-full bg-neutral-200 h-1.5 rounded-full overflow-hidden"
                     role="progressbar"
                     aria-valuenow="{{ $levelProgressPercent }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="Progres XP level berikutnya">
                    <div class="bg-brand h-1.5 rounded-full transition-all duration-300" style="width: {{ $levelProgressPercent }}%"></div>
                </div>
            </div>
        </x-ui.metric-card>

        {{-- Metric 2: Learning Progress % --}}
        <x-ui.metric-card label="Learning Progress" value="{{ $learningProgress }}%">
            <div class="space-y-1.5 w-full">
                <div class="flex items-center justify-between text-[11px] text-neutral-600 font-sans">
                    <span class="font-medium text-neutral-700">Rata-rata modul selesai</span>
                    <span class="font-mono font-medium">{{ $learningProgress }}%</span>
                </div>
                <div class="w-full bg-neutral-200 h-1.5 rounded-full overflow-hidden"
                     role="progressbar"
                     aria-valuenow="{{ $learningProgress }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="Progress modul pembelajaran {{ $learningProgress }}%">
                    <div class="bg-brand h-1.5 rounded-full transition-all duration-300" style="width: {{ $learningProgress }}%"></div>
                </div>
            </div>
        </x-ui.metric-card>

        {{-- Metric 3: KPI Contribution ("X dari 5 materi") --}}
        @php
            $materialsCount = $kpiYearly->materials_completed_count ?? 0;
            $kpiPercent = min(100, (int) round(($materialsCount / 5) * 100));
        @endphp
        <x-ui.metric-card label="KPI Contribution" value="{{ $materialsCount }} dari 5 materi">
            <div class="space-y-1.5 w-full">
                <div class="flex items-center justify-between text-[11px] text-neutral-600 font-sans">
                    <span class="font-medium text-neutral-700">Jalur B (Post-Test 100%)</span>
                    <span class="font-mono font-medium">{{ $kpiPercent }}%</span>
                </div>
                <div class="w-full bg-neutral-200 h-1.5 rounded-full overflow-hidden"
                     role="progressbar"
                     aria-valuenow="{{ $kpiPercent }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="Progres bundle materi KPI {{ $materialsCount }} dari 5">
                    <div class="bg-brand h-1.5 rounded-full transition-all duration-300" style="width: {{ $kpiPercent }}%"></div>
                </div>
            </div>
        </x-ui.metric-card>

        {{-- Metric 4: Poin CPS ERA Tahun Ini ("X / 3" + Breakdown) --}}
        <x-ui.metric-card label="Poin CPS ERA (Tahun Ini)" value="{{ $kpiYearly->poin_cps_era_earned ?? 0 }} / 3">
            <div class="flex items-center justify-between text-[11px] text-neutral-600 font-sans w-full flex-wrap gap-1">
                <span>BA: <strong class="text-neutral-800 font-semibold font-mono">{{ $kpiYearly->poin_from_ba ?? 0 }}</strong></span>
                <span>&middot;</span>
                <span>Materi: <strong class="text-neutral-800 font-semibold font-mono">{{ $kpiYearly->poin_from_materi ?? 0 }}</strong></span>
                <span>&middot;</span>
                <span class="text-neutral-500 font-mono text-[10px]">Cap 3/thn</span>
            </div>
        </x-ui.metric-card>
    </div>

    {{-- =========================================================================
         3. DUA KOLOM: LANJUTKAN PEMBELAJARAN & MISI YANG PERLU DISELESAIKAN (BARU)
         - 2 Kolom di Desktop, Stack Vertikal di Mobile
         - Card Putih Standar CPS ERA (Border tipis neutral-200, radius rounded-md, shadow halus)
         ========================================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        
        {{-- Kolom 1: Card "Lanjutkan Pembelajaran" --}}
        <div class="flex flex-col">
            <h2 class="font-sans font-semibold text-sm text-neutral-900 mb-2">
                Lanjutkan Pembelajaran
            </h2>

            <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col justify-between flex-1 hover:border-neutral-300 transition-colors">
                @if ($continueLearning && $continueLearning->material)
                    <div>
                        {{-- Badge kategori/divisi & Progres % --}}
                        <div class="flex items-center justify-between gap-2 mb-3">
                            @if ($continueLearning->material->category)
                                <span class="px-2 py-0.5 border border-neutral-300 rounded-badge text-neutral-800 bg-neutral-100 font-sans text-[11px] font-medium truncate max-w-[200px]" title="{{ $continueLearning->material->category->name }}">
                                    {{ $continueLearning->material->category->name }}
                                </span>
                            @elseif ($continueLearning->material->division)
                                <span class="px-2 py-0.5 border border-neutral-300 rounded-badge text-neutral-800 bg-neutral-100 font-sans text-[11px] font-medium truncate max-w-[200px]">
                                    Divisi {{ $continueLearning->material->division->name }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 border border-neutral-200 rounded-badge text-neutral-700 bg-neutral-50 font-sans text-[11px] font-medium">
                                    Modul Pelatihan
                                </span>
                            @endif

                            <span class="font-mono text-xs font-semibold text-brand-dark bg-brand-tint border border-brand/20 px-2 py-0.5 rounded-badge tabular-nums">
                                {{ $continueLearning->progress_percent }}% selesai
                            </span>
                        </div>

                        {{-- Judul Materi (Bold) --}}
                        <a href="{{ route('learning.show', $continueLearning->learning_material_id) }}" class="block group focus:outline-none">
                            <h3 class="font-sans font-bold text-base text-neutral-900 group-hover:text-brand transition-colors line-clamp-1">
                                {{ $continueLearning->material->title }}
                            </h3>
                        </a>

                        {{-- Progress Bar Horizontal (#0B7840) --}}
                        <div class="mt-4">
                            <div class="w-full bg-neutral-200 h-2 rounded-full overflow-hidden"
                                 role="progressbar"
                                 aria-valuenow="{{ $continueLearning->progress_percent }}"
                                 aria-valuemin="0"
                                 aria-valuemax="100"
                                 aria-label="Progres materi {{ $continueLearning->progress_percent }}%">
                                <div class="bg-brand h-2 rounded-full transition-all duration-300" style="width: {{ $continueLearning->progress_percent }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Tombol "Lanjutkan" Solid Hijau Tanpa Panah --}}
                    <div class="pt-4 mt-4 border-t border-neutral-100 flex items-center justify-start">
                        <x-ui.button href="{{ route('learning.show', $continueLearning->learning_material_id) }}" variant="primary" size="sm">
                            Lanjutkan
                        </x-ui.button>
                    </div>
                @else
                    {{-- Empty State jika tidak ada materi in-progress --}}
                    <div class="py-5 px-2 flex flex-col justify-between h-full text-left">
                        <div>
                            <div class="w-9 h-9 rounded-md bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3">
                                <x-layout.nav-icon name="academic" class="w-4 h-4" />
                            </div>
                            <h3 class="font-sans font-semibold text-sm text-neutral-900">
                                Belum ada materi yang sedang dipelajari
                            </h3>
                            <p class="font-sans text-xs text-neutral-500 mt-1 leading-relaxed">
                                Jelajahi katalog modul pelatihan mandiri untuk meningkatkan keterampilan dan kontribusi KPI Anda.
                            </p>
                        </div>
                        <div class="pt-4 mt-4 border-t border-neutral-100 flex items-center justify-start">
                            <x-ui.button href="{{ route('learning.index') }}" variant="secondary" size="sm">
                                Jelajahi Modul Learning
                            </x-ui.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Kolom 2: Card "Misi yang Perlu Diselesaikan" --}}
        <div class="flex flex-col">
            <h2 class="font-sans font-semibold text-sm text-neutral-900 mb-2">
                Misi yang Perlu Diselesaikan
            </h2>

            <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col justify-between flex-1 hover:border-neutral-300 transition-colors">
                @if ($pendingMission)
                    <div>
                        {{-- Badge Poin Reward & Meta Info --}}
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold text-brand-dark bg-brand-tint border border-brand/30 px-2 py-0.5 rounded-badge">
                                +{{ $pendingMission->points_reward }} Points
                            </span>

                            <span class="text-xs font-sans text-neutral-500 font-medium">
                                {{ $pendingMission->isCaseStudy() ? 'Case Study' : 'Quiz Cepat' }} &middot; 5 menit
                            </span>
                        </div>

                        {{-- Judul Misi (Bold) --}}
                        <a href="{{ route('missions.show', $pendingMission->id) }}" class="block group focus:outline-none">
                            <h3 class="font-sans font-bold text-base text-neutral-900 group-hover:text-brand transition-colors line-clamp-1">
                                {{ $pendingMission->title }}
                            </h3>
                        </a>

                        {{-- Cuplikan Deskripsi Kasus / Misi --}}
                        <p class="font-sans text-xs text-neutral-600 line-clamp-2 mt-2 leading-relaxed">
                            {{ $pendingMission->description ?: 'Tantangan evaluasi kompetensi manufaktur dan penanganan insiden operasional pabrik.' }}
                        </p>
                    </div>

                    {{-- Tombol "Mulai" Solid Hijau Tanpa Panah --}}
                    <div class="pt-4 mt-4 border-t border-neutral-100 flex items-center justify-start">
                        <x-ui.button href="{{ route('missions.show', $pendingMission->id) }}" variant="primary" size="sm">
                            Mulai
                        </x-ui.button>
                    </div>
                @else
                    {{-- Empty State jika semua misi sudah selesai --}}
                    <div class="py-5 px-2 flex flex-col justify-between h-full text-left">
                        <div>
                            <div class="w-9 h-9 rounded-md bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-3">
                                <x-layout.nav-icon name="puzzle" class="w-4 h-4" />
                            </div>
                            <h3 class="font-sans font-semibold text-sm text-neutral-900">
                                Semua misi selesai &mdash; mantap!
                            </h3>
                            <p class="font-sans text-xs text-neutral-500 mt-1 leading-relaxed">
                                Anda telah menyelesaikan seluruh misi aktif. Cek kembali secara berkala untuk tantangan studi kasus baru.
                            </p>
                        </div>
                        <div class="pt-4 mt-4 border-t border-neutral-100 flex items-center justify-start">
                            <x-ui.button href="{{ route('missions.index') }}" variant="secondary" size="sm">
                                Buka Mission &amp; Game
                            </x-ui.button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- =========================================================================
         4. SECTION KNOWLEDGE TERBARU (BARU: GRID KARTU 3 KOLOM, FULL WIDTH)
         - Menggantikan tampilan list lama dengan grid kartu modern sesuai mockup
         - Menampilkan 3 item terpublikasi terbaru
         - Tombol "Lihat Materi" bergaya secondary/outline tanpa panah
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-5 mb-6 flex flex-col">
        {{-- Header Section dengan Link "Lihat Semua" --}}
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-neutral-200">
            <div>
                <h2 class="font-sans font-semibold text-sm text-neutral-900">
                    Knowledge Terbaru
                </h2>
                <p class="font-sans text-xs text-neutral-600 mt-0.5">
                    Knowledge Repository Terbaru: SOP, Best Practice &amp; Lesson Learned terverifikasi
                </p>
            </div>
            <a href="{{ route('knowledge.index') }}" 
               class="font-sans text-xs text-neutral-600 hover:text-brand font-medium transition-colors">
                Lihat Semua
            </a>
        </div>

        {{-- Grid 3 Kolom Desktop, 1 Kolom Mobile --}}
        @if ($latestKnowledge->isEmpty())
            <div class="py-10 px-4 flex flex-col items-center justify-center text-center">
                <div class="w-10 h-10 rounded-md bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-2.5">
                    <x-layout.nav-icon name="dokumen" class="w-5 h-5" />
                </div>
                <p class="font-sans font-medium text-xs text-neutral-800">
                    Belum Ada Dokumen Tersedia
                </p>
                <p class="font-sans text-[11px] text-neutral-500 mt-1 max-w-xs leading-relaxed">
                    Belum ada dokumen knowledge yang dipublikasikan.
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($latestKnowledge as $doc)
                    <div class="bg-white border border-neutral-200 rounded-md p-4 flex flex-col justify-between hover:border-neutral-300 transition-colors group">
                        <div>
                            {{-- Header Kartu: Ikon tipe materi + Badge Divisi --}}
                            <div class="flex items-center justify-between gap-2 pb-2.5 border-b border-neutral-100">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <x-layout.nav-icon :name="$doc->type" class="w-4 h-4 text-neutral-500 shrink-0" />
                                    @if ($doc->topic)
                                        <span class="truncate px-1.5 py-0.5 border border-brand/20 rounded-badge text-brand-dark bg-brand-tint font-sans text-[11px] font-medium" title="{{ $doc->topic->name }}">
                                            {{ $doc->topic->name }}
                                        </span>
                                    @elseif ($doc->division)
                                        <span class="truncate px-1.5 py-0.5 border border-neutral-200 rounded-badge text-neutral-700 bg-neutral-50 font-sans text-[11px] font-medium" title="{{ $doc->division->name }}">
                                            {{ $doc->division->name }}
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 border border-neutral-200 rounded-badge text-neutral-700 bg-neutral-50 font-sans text-[11px] font-medium">
                                            Umum
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[10px] font-mono text-neutral-500 uppercase tracking-tight shrink-0">
                                    {{ $doc->type ?? 'Dokumen' }}
                                </span>
                            </div>

                            {{-- Judul & Deskripsi Dokumen (1 baris truncate) --}}
                            <div class="mt-3">
                                <a href="{{ route('knowledge.index') }}" class="block focus:outline-none">
                                    <h3 class="font-sans font-semibold text-xs text-neutral-900 group-hover:text-brand transition-colors truncate" title="{{ $doc->title }}">
                                        {{ $doc->title }}
                                    </h3>
                                </a>
                                <p class="font-sans text-xs text-neutral-600 truncate mt-1" title="{{ $doc->description ?: 'Dokumen standar operasional prosedur dan best practice internal.' }}">
                                    {{ $doc->description ?: 'Dokumen standar operasional prosedur dan best practice internal.' }}
                                </p>
                            </div>
                        </div>

                        {{-- Footer Tombol: Outline / Secondary (bukan solid hijau) tanpa panah --}}
                        <div class="pt-3 mt-3 border-t border-neutral-100 flex items-center justify-start">
                            <x-ui.button href="{{ route('knowledge.index') }}" variant="secondary" size="sm">
                                Lihat Materi
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- =========================================================================
         5. SECTION BA & LESSON LEARNED TERBARU (TETAP, DIPINDAHKAN KE BAWAH FULL WIDTH)
         - List rows ber-hairline divider tipis (neutral-200)
         - Status badge tegas non-pill per Design System §5
         - Lebar penuh (full width) di bawah section Knowledge Terbaru
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col">
        {{-- Header List --}}
        <div class="flex items-center justify-between pb-3 mb-1 border-b border-neutral-200">
            <div>
                <h2 class="font-sans font-semibold text-sm text-neutral-900">
                    Laporan CAPA Terbaru
                </h2>
                <p class="font-sans text-xs text-neutral-600 mt-0.5">
                    Pencatatan insiden operasional &amp; status verifikasi tindakan korektif
                </p>
            </div>
            <a href="{{ route('ba.index') }}" 
               class="font-sans text-xs text-neutral-600 hover:text-brand font-medium transition-colors">
                Lihat Semua
            </a>
        </div>

        {{-- List Rows ber-hairline divider tipis dengan status badge tegas non-pill --}}
        <div class="divide-y divide-neutral-200 flex-1">
            @forelse ($latestBa as $ba)
                <div class="py-3 flex items-start justify-between gap-3 hover:bg-neutral-50/50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs font-medium text-neutral-700 bg-neutral-50 border border-neutral-200 px-1.5 py-0.5 rounded-badge">
                                {{ $ba->nomor_ba }}
                            </span>
                            @if ($ba->division)
                                <span class="text-[11px] font-sans text-neutral-600 truncate font-medium">
                                    Divisi {{ $ba->division->name }}
                                </span>
                            @endif
                        </div>

                        <a href="{{ route('ba.show', $ba->id) }}" 
                           class="font-sans text-xs font-medium text-neutral-900 hover:text-brand transition-colors block truncate">
                            {{ $ba->title ?? 'Laporan Insiden Berita Acara' }}
                        </a>

                        <div class="flex items-center gap-2 mt-1 text-[11px] font-sans text-neutral-600">
                            <span>Oleh {{ $ba->creator?->name ?? 'Pelapor' }}</span>
                            <span>&middot;</span>
                            <span>{{ $ba->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    {{-- Status BA di list pakai badge/status tag sesuai §5 Design System --}}
                    <div class="shrink-0 pt-0.5">
                        <x-ui.badge status="{{ strtolower($ba->status) }}" />
                    </div>
                </div>
            @empty
                <div class="py-10 px-4 flex flex-col items-center justify-center text-center">
                    <div class="w-10 h-10 rounded-md bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-2.5">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                    </div>
                    <p class="font-sans font-medium text-xs text-neutral-800">
                        Belum Ada Berita Acara
                    </p>
                    <p class="font-sans text-[11px] text-neutral-500 mt-1 max-w-xs leading-relaxed">
                        Belum ada Berita Acara yang dilaporkan.
                    </p>
                </div>
            @endforelse
        </div>
    </div>
</div>
