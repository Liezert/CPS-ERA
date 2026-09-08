<div>
    {{-- =========================================================================
         1. HEADER SAPAAN USER & TOMBOL AKSI UTAMA (Design System §2 & §5)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-md p-5 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        {{-- Kiri: Avatar + Sapaan + Chip Level --}}
        <div class="flex items-center gap-4">
            {{-- Avatar (Hijau diizinkan di avatar) --}}
            <div class="w-12 h-12 rounded-md bg-brand-tint border border-brand/20 text-brand-dark font-sans font-semibold text-base flex items-center justify-center shrink-0">
                {{ $initials }}
            </div>

            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="font-sans font-semibold text-lg text-neutral-900 leading-tight">
                        Selamat bertugas, {{ $user->name }}
                    </h1>
                    
                    {{-- Chip Level (Hijau diizinkan di chip level - TODO PRD §5.3) --}}
                    <!-- TODO: Menunggu keputusan PRD §5.3 (Poin 1: Formula skala level) -->
                    <span class="inline-flex items-center bg-brand-tint text-brand-dark border border-brand/20 font-mono text-xs font-medium px-2.5 py-0.5 rounded-badge">
                        Level {{ $currentLevel }}
                    </span>
                </div>

                <p class="font-sans text-xs text-neutral-500 mt-1 flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-neutral-700 bg-neutral-50 px-1.5 py-0.5 border border-neutral-200 rounded-badge">
                        {{ $user->employee_id ?? 'CPS-00124' }}
                    </span>
                    <span>&middot;</span>
                    <span>{{ $user->jabatan ?? 'Operator' }}</span>
                    @if ($user->division)
                        <span>&middot;</span>
                        <span class="font-medium text-neutral-700">{{ $user->division->name }}</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Kanan: Satu Tombol Aksi Utama (Tanpa Panah per Anti-AI-Slop §6 & DoD) --}}
        <div class="shrink-0">
            <x-ui.button href="{{ route('ba.create') }}" variant="primary">
                Buat BA Baru
            </x-ui.button>
        </div>
    </div>

    {{-- =========================================================================
         2. GRID METRIC CARD (4 Kolom Desktop, 2 Tablet, 1 Mobile)
         ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 desktop:grid-cols-4 gap-4 mb-6">
        {{-- Metric 1: Learning Progress % --}}
        <x-ui.metric-card label="Learning Progress" value="{{ $learningProgress }}%">
            <span class="text-neutral-500">Materi Pelatihan Selesai</span>
        </x-ui.metric-card>

        {{-- Metric 2: KPI Contribution % (TODO PRD §5.3) --}}
        <!-- TODO: Menunggu keputusan PRD §5.3 (Poin 2: Formula persentase KPI Contribution) -->
        <x-ui.metric-card label="KPI Contribution" value="--">
            <span class="text-neutral-500 italic">[Menunggu PRD §5.3]</span>
        </x-ui.metric-card>

        {{-- Metric 3: Akumulasi Poin (Agregasi Ledger point_transactions) --}}
        <x-ui.metric-card label="Total Poin Saya" value="{{ number_format($totalPoints) }} Pts" :is-technical="true">
            <span class="text-neutral-500">Agregasi ledger point_transactions</span>
        </x-ui.metric-card>

        {{-- Metric 4: Target Level Berikutnya & Progress Bar (Hijau diizinkan di progress bar) --}}
        <!-- TODO: Menunggu keputusan PRD §5.3 (Poin 1: Formula skala level & threshold XP) -->
        <div class="bg-white p-5 border border-neutral-200 rounded-md flex flex-col justify-between">
            <div>
                <p class="text-xs font-sans font-medium text-neutral-500 uppercase tracking-normal">
                    Target Level Berikutnya
                </p>
                <div class="mt-2 flex items-baseline justify-between">
                    <p class="text-2xl font-semibold text-neutral-900 tracking-tight font-sans">
                        Level {{ $nextLevel }}
                    </p>
                    <span class="text-xs font-mono text-neutral-500">{{ $levelProgressPercent }}%</span>
                </div>
            </div>

            <div class="mt-3">
                {{-- Progress bar menuju level berikutnya --}}
                <div class="w-full bg-neutral-200 h-2 rounded-full overflow-hidden">
                    <div class="bg-brand h-2 rounded-full transition-all duration-300" style="width: {{ $levelProgressPercent }}%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-neutral-500 mt-1.5">
                    <span>XP Level {{ $currentLevel }} &rarr; {{ $nextLevel }}</span>
                    <span class="italic">[Formula XP PRD §5.3]</span>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         3. DUA KOLOM LIST (Hairline Divider, BUKAN Card-Soup dengan Shadow)
         ========================================================================= --}}
    <div class="grid grid-cols-1 desktop:grid-cols-2 gap-6">
        
        {{-- Kolom 1: Knowledge Repository Terbaru --}}
        <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col">
            {{-- Header List --}}
            <div class="flex items-center justify-between pb-3 mb-1 border-b border-neutral-200">
                <div>
                    <h2 class="font-sans font-semibold text-sm text-neutral-900">
                        Knowledge Repository Terbaru
                    </h2>
                    <p class="font-sans text-xs text-neutral-500 mt-0.5">
                        Dokumen SOP, Best Practice &amp; Lesson Learned terverifikasi
                    </p>
                </div>
                <a href="{{ route('knowledge.index') }}" 
                   class="font-sans text-xs text-neutral-500 hover:text-brand font-medium transition-colors">
                    Lihat Semua
                </a>
            </div>

            {{-- List Rows ber-hairline divider tipis (Anti Card-Soup) --}}
            <div class="divide-y divide-neutral-200 flex-1">
                @forelse ($latestKnowledge as $doc)
                    <div class="py-3 flex items-start justify-between gap-3 hover:bg-neutral-50/50 transition-colors">
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('knowledge.index') }}" 
                               class="font-sans text-xs font-medium text-neutral-900 hover:text-brand transition-colors truncate block">
                                {{ $doc->title }}
                            </a>
                            <div class="flex items-center gap-2 mt-1 flex-wrap text-[11px] font-sans text-neutral-500">
                                @if ($doc->division)
                                    <span class="px-1.5 py-0.5 border border-neutral-200 rounded-badge text-neutral-700 bg-neutral-50">
                                        {{ $doc->division->name }}
                                    </span>
                                @endif
                                <span>{{ $doc->creator?->name ?? 'Tim Internal' }}</span>
                                <span>&middot;</span>
                                <span>{{ $doc->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="shrink-0 text-right">
                            <span class="text-[11px] font-mono text-neutral-400 uppercase">
                                {{ $doc->type ?? 'Dokumen' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <p class="font-sans text-xs text-neutral-500">
                            Belum ada dokumen knowledge yang dipublikasikan.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Kolom 2: BA & Lesson Learned Terbaru --}}
        <div class="bg-white border border-neutral-200 rounded-md p-5 flex flex-col">
            {{-- Header List --}}
            <div class="flex items-center justify-between pb-3 mb-1 border-b border-neutral-200">
                <div>
                    <h2 class="font-sans font-semibold text-sm text-neutral-900">
                        BA &amp; Lesson Learned Terbaru
                    </h2>
                    <p class="font-sans text-xs text-neutral-500 mt-0.5">
                        Pencatatan insiden operasional &amp; status verifikasi tindakan korektif
                    </p>
                </div>
                <a href="{{ route('ba.index') }}" 
                   class="font-sans text-xs text-neutral-500 hover:text-brand font-medium transition-colors">
                    Lihat Semua
                </a>
            </div>

            {{-- List Rows ber-hairline divider tipis dengan status badge tegas non-pill --}}
            <div class="divide-y divide-neutral-200 flex-1">
                @forelse ($latestBa as $ba)
                    <div class="py-3 flex items-start justify-between gap-3 hover:bg-neutral-50/50 transition-colors">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-mono text-xs font-medium text-neutral-600 bg-neutral-50 border border-neutral-200 px-1.5 py-0.5 rounded-badge">
                                    {{ $ba->nomor_ba }}
                                </span>
                                @if ($ba->division)
                                    <span class="text-[11px] font-sans text-neutral-500 truncate">
                                        Divisi {{ $ba->division->name }}
                                    </span>
                                @endif
                            </div>

                            <a href="{{ route('ba.show', $ba->id) }}" 
                               class="font-sans text-xs font-medium text-neutral-900 hover:text-brand transition-colors block truncate">
                                {{ $ba->title ?? 'Laporan Insiden Berita Acara' }}
                            </a>

                            <div class="flex items-center gap-2 mt-1 text-[11px] font-sans text-neutral-500">
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
                    <div class="py-10 text-center">
                        <p class="font-sans text-xs text-neutral-500">
                            Belum ada Berita Acara yang dilaporkan.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
