<div class="space-y-6">
    {{-- Breadcrumb & Judul Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-200 pb-5">
        <div>
            <nav class="flex items-center gap-2 font-mono text-xs text-neutral-500 mb-1.5" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}" class="hover:text-brand transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-neutral-900 font-medium">Profil Pegawai</span>
            </nav>
            <h1 class="font-sans font-bold text-2xl text-neutral-900 tracking-tight">
                Profil Pegawai
            </h1>
            <p class="font-sans text-xs sm:text-sm text-neutral-600 mt-1">
                Informasi identitas, akumulasi performa, dan riwayat perolehan poin Anda.
            </p>
        </div>
    </div>

    {{-- =========================================================================
         1. CARD IDENTITAS PEGAWAI (Nama, Employee ID, Jabatan, Divisi, Avatar, Level)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            {{-- Bagian Kiri: Avatar + Info Identitas --}}
            <div class="flex items-center gap-4 sm:gap-5">
                {{-- Avatar Box Inisial (Konsisten dengan Header & Dashboard Stage 5) --}}
                <div class="w-16 h-16 rounded-md bg-brand-tint border border-brand/20 text-brand-dark font-sans font-bold text-2xl flex items-center justify-center shrink-0">
                    {{ $initials }}
                </div>

                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 class="font-sans font-bold text-xl text-neutral-900 leading-tight">
                            {{ $user->name }}
                        </h2>

                        {{-- Chip Level (Warna hijau brand-tint diizinkan pada chip level per DS §2) --}}
                        <!-- TODO: Menunggu keputusan PRD §5.3 (Poin 1: Formula skala level) -->
                        <span class="inline-flex items-center bg-brand-tint text-brand-dark border border-brand/20 font-mono text-xs font-medium px-2.5 py-0.5 rounded-badge">
                            Level {{ $currentLevel }}
                        </span>
                    </div>

                    {{-- Metadata: Employee ID (format CPS-00124, IBM Plex Mono) + Jabatan + Divisi --}}
                    <div class="flex items-center gap-2 sm:gap-3 flex-wrap mt-2 text-xs font-sans text-neutral-600">
                        {{-- DoD #2: Employee ID pakai mono font, format CPS-00124 --}}
                        <span class="font-mono text-neutral-800 bg-neutral-100 px-2 py-0.5 border border-neutral-200 rounded-badge font-medium">
                            {{ $formattedEmployeeId }}
                        </span>
                        <span class="text-neutral-300">&middot;</span>
                        <span>{{ $user->jabatan ?? 'Engineering Staff' }}</span>
                        <span class="text-neutral-300">&middot;</span>
                        <span class="font-medium text-neutral-900">{{ $user->division ? $user->division->name : 'Divisi Umum' }}</span>
                        <span class="text-neutral-300">&middot;</span>
                        <span class="text-neutral-500 font-mono">{{ $user->email }}</span>
                    </div>
                </div>
            </div>

            {{-- Bagian Kanan: Level & Progress Bar XP --}}
            <div class="w-full sm:w-64 pt-4 sm:pt-0 border-t sm:border-t-0 border-neutral-100 flex flex-col justify-center">
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="font-sans font-medium text-neutral-600">Progress Level {{ $currentLevel }} &rarr; {{ $nextLevel }}</span>
                    <span class="font-mono text-neutral-500 font-medium">{{ $levelProgressPercent }}%</span>
                </div>
                {{-- Progress Bar Tipis Hijau (Kepatuhan Design System §5) --}}
                <div class="w-full bg-neutral-200 h-2 rounded-full overflow-hidden">
                    <div class="bg-brand h-2 rounded-full transition-all duration-300" style="width: {{ $levelProgressPercent }}%"></div>
                </div>
                <span class="font-mono text-[10px] text-neutral-400 mt-1 text-right">
                    Formula XP PRD §5.3
                </span>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         2. RINGKASAN METRIK (DoD #1: Metric card konsisten visual dengan Dashboard Stage 5)
         ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 desktop:grid-cols-4 gap-4">
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

        {{-- Metric 4: Target Level Berikutnya & Progress Bar (Gaya persis Dashboard Stage 5) --}}
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
         3. GRAFIK PERFORMA BULANAN (DoD #3: Satu warna hijau, TANPA gradient/dekorasi)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-neutral-200 gap-2">
            <div>
                <h3 class="font-sans font-semibold text-base text-neutral-900">
                    Grafik Performa Bulanan
                </h3>
                <p class="font-sans text-xs text-neutral-500 mt-0.5">
                    Akumulasi perolehan poin penghargaan pegawai selama 6 bulan terakhir.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 bg-brand rounded-sm inline-block"></span>
                <span class="font-sans text-xs text-neutral-600">Poin Diperoleh (Hijau Tunggal)</span>
            </div>
        </div>

        {{-- Area Visual Bar Chart Sederhana (Clean, Minimalist, No Confetti, No 3D Shadows) --}}
        <div class="mt-6 pt-2">
            <div class="h-48 flex items-end justify-between gap-3 sm:gap-6 px-2 sm:px-6">
                @foreach ($monthlyPerformance as $item)
                    <div wire:key="month-{{ $item['month'] }}" class="flex-1 flex flex-col items-center h-full justify-end group">
                        
                        {{-- Angka Poin di Atas Batang (font-mono) --}}
                        <span class="font-mono text-xs font-semibold text-neutral-800 mb-2 transition-transform group-hover:-translate-y-0.5">
                            {{ $item['points'] > 0 ? number_format($item['points']) : '0' }}
                        </span>

                        {{-- Batang Grafik: SATU WARNA HIJAU TUNGGAL (#0B7840 / bg-brand), TANPA GRADIENT --}}
                        <div class="w-full max-w-[48px] bg-neutral-100 rounded-t-sm flex items-end overflow-hidden" style="height: 100%;">
                            @if ($item['points'] > 0)
                                <div class="w-full bg-brand rounded-t-sm transition-all duration-500 group-hover:opacity-90"
                                     style="height: {{ max($item['barPercent'], 6) }}%;"
                                     title="{{ $item['label'] }}: {{ $item['points'] }} Pts">
                                </div>
                            @else
                                <div class="w-full bg-neutral-200 h-1 rounded-t-sm" title="{{ $item['label'] }}: 0 Pts"></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Garis Dasar Sumbu X --}}
            <div class="border-b border-neutral-200 w-full mt-0"></div>

            {{-- Label Bulan Sumbu X (font-mono) --}}
            <div class="flex items-center justify-between gap-3 sm:gap-6 px-2 sm:px-6 mt-2.5">
                @foreach ($monthlyPerformance as $item)
                    <div wire:key="label-{{ $item['month'] }}" class="flex-1 text-center">
                        <span class="font-mono text-[11px] sm:text-xs text-neutral-600 block">
                            {{ $item['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- =========================================================================
         4. PENGATURAN AKUN (Pembaruan Profil & Ubah Password)
         ========================================================================= --}}
    <div class="grid grid-cols-1 desktop:grid-cols-2 gap-6">
        {{-- Form 1: Ubah Informasi Profil --}}
        <div class="bg-white border border-neutral-200 rounded-lg p-5 sm:p-6">
            <h3 class="font-sans font-semibold text-base text-neutral-900 border-b border-neutral-200 pb-3">
                Perbarui Informasi Profil
            </h3>

            @if ($statusMessage)
                <div class="mt-4 p-3 bg-brand-tint border border-brand/20 text-brand-dark rounded-md text-xs font-sans">
                    {{ $statusMessage }}
                </div>
            @endif

            <form wire:submit="updateProfileInformation" class="mt-4 space-y-4">
                <div>
                    <label for="profile_name" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                        Nama Lengkap
                    </label>
                    <input type="text"
                           id="profile_name"
                           wire:model="name"
                           class="w-full px-3 py-2 border border-neutral-300 rounded-md text-sm font-sans focus:ring-1 focus:ring-brand focus:border-brand text-neutral-900"
                           required>
                    @error('name')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="profile_email" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                        Alamat Email Resmi
                    </label>
                    <input type="email"
                           id="profile_email"
                           wire:model="email"
                           class="w-full px-3 py-2 border border-neutral-300 rounded-md text-sm font-sans focus:ring-1 focus:ring-brand focus:border-brand text-neutral-900"
                           required>
                    @error('email')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block font-sans text-xs font-medium text-neutral-500 mb-1">
                        Employee ID (Read-only)
                    </label>
                    <input type="text"
                           value="{{ $formattedEmployeeId }}"
                           disabled
                           class="w-full px-3 py-2 bg-neutral-50 border border-neutral-200 rounded-md text-sm font-mono text-neutral-500 cursor-not-allowed">
                </div>

                <div class="pt-2">
                    <x-ui.button type="submit" variant="primary">
                        Simpan Perubahan
                    </x-ui.button>
                </div>
            </form>
        </div>

        {{-- Form 2: Ubah Kata Sandi Akun --}}
        <div class="bg-white border border-neutral-200 rounded-lg p-5 sm:p-6">
            <h3 class="font-sans font-semibold text-base text-neutral-900 border-b border-neutral-200 pb-3">
                Ganti Kata Sandi Akun
            </h3>

            @if ($passwordStatusMessage)
                <div class="mt-4 p-3 bg-brand-tint border border-brand/20 text-brand-dark rounded-md text-xs font-sans">
                    {{ $passwordStatusMessage }}
                </div>
            @endif

            <form wire:submit="updatePassword" class="mt-4 space-y-4">
                <div>
                    <label for="current_password" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                        Kata Sandi Saat Ini
                    </label>
                    <input type="password"
                           id="current_password"
                           wire:model="current_password"
                           class="w-full px-3 py-2 border border-neutral-300 rounded-md text-sm font-sans focus:ring-1 focus:ring-brand focus:border-brand text-neutral-900"
                           required>
                    @error('current_password')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="new_password" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                        Kata Sandi Baru
                    </label>
                    <input type="password"
                           id="new_password"
                           wire:model="password"
                           class="w-full px-3 py-2 border border-neutral-300 rounded-md text-sm font-sans focus:ring-1 focus:ring-brand focus:border-brand text-neutral-900"
                           required>
                    @error('password')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                        Konfirmasi Kata Sandi Baru
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           wire:model="password_confirmation"
                           class="w-full px-3 py-2 border border-neutral-300 rounded-md text-sm font-sans focus:ring-1 focus:ring-brand focus:border-brand text-neutral-900"
                           required>
                    @error('password_confirmation')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>

                <div class="pt-2">
                    <x-ui.button type="submit" variant="secondary">
                        Perbarui Kata Sandi
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>
