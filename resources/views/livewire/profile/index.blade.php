<div class="space-y-6">
    {{-- Breadcrumb & Judul Halaman --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-md p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <nav class="flex items-center gap-2 font-mono text-xs text-neutral-600 font-medium mb-1.5" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}" class="hover:text-brand transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-neutral-900 font-semibold">Profil Pegawai</span>
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
         1. CARD IDENTITAS PEGAWAI (Nama, Employee ID, Jabatan, Divisi, Avatar)
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            {{-- Bagian Kiri: Avatar + Info Identitas --}}
            <div class="flex items-center gap-4 sm:gap-5">
                {{-- Avatar Box (Foto Profil atau Inisial jika belum ada foto) --}}
                @if ($user->avatar_url)
                    <img src="{{ asset($user->avatar_url) }}"
                         alt="{{ $user->name }}"
                         class="w-16 h-16 rounded-md object-cover border border-neutral-200 shrink-0">
                @else
                    <div class="w-16 h-16 rounded-md bg-brand-tint border border-brand/20 text-brand-dark font-sans font-bold text-2xl flex items-center justify-center shrink-0">
                        {{ $initials }}
                    </div>
                @endif

                <div>
                    <h2 class="font-sans font-bold text-xl text-neutral-900 leading-tight">
                        {{ $user->name }}
                    </h2>

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
                        <span class="text-neutral-600 font-mono">{{ $user->email }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         2. RINGKASAN METRIK (DoD #1: Metric card konsisten visual dengan Dashboard Stage 5)
         ========================================================================= --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Metric 1: Learning Progress % --}}
        <x-ui.metric-card label="Learning Progress" value="{{ $learningProgress }}%">
            <span class="text-neutral-600">Materi Pelatihan Selesai</span>
        </x-ui.metric-card>

        {{-- Metric 2: KPI Contribution ("X dari N materi" periode aktif, sumber sama dengan Dashboard) --}}
        <x-ui.metric-card label="KPI Contribution" value="{{ $kpiData['summary'] }}">
            <div class="space-y-1.5 w-full">
                <div class="flex items-center justify-between text-[11px] text-neutral-600 font-sans">
                    <span class="font-medium text-neutral-700">{{ $kpiData['is_complete'] ? 'Target periode tercapai' : 'Post-test 100%' }}</span>
                    <span class="font-mono font-medium">{{ $kpiContribution }}%</span>
                </div>
                <div class="w-full bg-neutral-200 h-1.5 rounded-full overflow-hidden"
                     role="progressbar"
                     aria-valuenow="{{ $kpiContribution }}"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     aria-label="Progres KPI {{ $kpiData['summary'] }}">
                    <div class="bg-brand h-1.5 rounded-full transition-all duration-300" style="width: {{ $kpiContribution }}%"></div>
                </div>
                <p class="text-[10px] text-neutral-500 font-mono">Periode {{ $kpiData['period_label'] }}</p>
            </div>
        </x-ui.metric-card>

        {{-- Metric 3: Akumulasi Poin (Agregasi Ledger point_transactions) --}}
        <x-ui.metric-card label="Total Poin Saya" value="{{ number_format($totalPoints) }} Pts" :is-technical="true">
            <span class="text-neutral-600">Agregasi ledger point_transactions</span>
        </x-ui.metric-card>
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
            <div class="h-48 flex items-end justify-between gap-1.5 sm:gap-6 px-1 sm:px-6">
                @foreach ($monthlyPerformance as $item)
                    <div wire:key="month-{{ $item['month'] }}" class="flex-1 flex flex-col items-center h-full justify-end group">
                        
                        {{-- Angka Poin di Atas Batang (font-mono) --}}
                        <span class="font-mono text-[11px] sm:text-xs font-semibold text-neutral-800 mb-2 transition-transform group-hover:-translate-y-0.5">
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
            <div class="flex items-center justify-between gap-1.5 sm:gap-6 px-1 sm:px-6 mt-2.5">
                @foreach ($monthlyPerformance as $item)
                    <div wire:key="label-{{ $item['month'] }}" class="flex-1 text-center">
                        <span class="font-mono text-[10px] sm:text-xs text-neutral-600 block">
                            {{ $item['label'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- =========================================================================
         4. KELOLA FOTO & AVATAR PROFIL
         ========================================================================= --}}
    <div class="bg-white border border-neutral-200 rounded-lg p-5 sm:p-6 space-y-5">
        <div class="border-b border-neutral-200 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="font-sans font-semibold text-base text-neutral-900">
                    Foto & Avatar Profil
                </h3>
                <p class="text-xs text-neutral-500 font-sans mt-0.5">
                    Unggah foto diri atau rancang avatar inisial berlatar warna pilihan Anda.
                </p>
            </div>

            {{-- Mode Switcher (Tab Button) --}}
            <div class="w-full sm:w-auto grid grid-cols-2 sm:inline-flex p-1 bg-neutral-100 rounded-md border border-neutral-200 text-xs font-sans"
                 role="tablist"
                 aria-label="Pilihan mode foto profil">
                <button type="button"
                        id="tab-avatar-upload"
                        role="tab"
                        aria-selected="{{ $avatarMode === 'upload' ? 'true' : 'false' }}"
                        aria-controls="panel-avatar-upload"
                        wire:click="$set('avatarMode', 'upload')"
                        class="px-3 py-2 sm:py-1 rounded-[4px] font-medium text-center transition-colors focus:outline-none focus:ring-1 focus:ring-brand {{ $avatarMode === 'upload' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900' }}">
                    Unggah Foto Pribadi
                </button>
                <button type="button"
                        id="tab-avatar-generate"
                        role="tab"
                        aria-selected="{{ $avatarMode === 'generate' ? 'true' : 'false' }}"
                        aria-controls="panel-avatar-generate"
                        wire:click="$set('avatarMode', 'generate')"
                        class="px-3 py-2 sm:py-1 rounded-[4px] font-medium text-center transition-colors focus:outline-none focus:ring-1 focus:ring-brand {{ $avatarMode === 'generate' ? 'bg-white text-neutral-900 shadow-sm' : 'text-neutral-600 hover:text-neutral-900' }}">
                    Rancang Avatar Kustom
                </button>
            </div>
        </div>

        @if ($photoStatusMessage)
            <div class="p-3 bg-brand-tint border border-brand/20 text-brand-dark rounded-md text-xs font-sans">
                {{ $photoStatusMessage }}
            </div>
        @endif

        {{-- MODE 1: UNGGAH FOTO PRIBADI --}}
        @if ($avatarMode === 'upload')
            <div id="panel-avatar-upload" role="tabpanel" aria-labelledby="tab-avatar-upload" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    {{-- Pratinjau Foto --}}
                    <div class="shrink-0 relative">
                        @php
                            $canPreview = $photo && ! $errors->has('photo') && in_array(strtolower($photo->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'webp']);
                        @endphp

                        @if ($canPreview)
                            <img src="{{ $photo->temporaryUrl() }}"
                                 alt="Pratinjau Foto"
                                 class="w-20 h-20 rounded-md object-cover border-2 border-brand shrink-0">
                        @elseif ($user->avatar_url)
                            <img src="{{ asset($user->avatar_url) }}"
                                 alt="{{ $user->name }}"
                                 class="w-20 h-20 rounded-md object-cover border border-neutral-200 shrink-0">
                        @else
                            <div class="w-20 h-20 rounded-md bg-brand-tint border border-brand/20 text-brand-dark font-sans font-bold text-2xl flex items-center justify-center shrink-0">
                                {{ $initials }}
                            </div>
                        @endif

                        <div wire:loading wire:target="photo" class="absolute inset-0 bg-white/80 rounded-md flex items-center justify-center">
                            <svg class="animate-spin h-5 w-5 text-brand" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="space-y-1.5 flex-1 text-xs font-sans">
                        <span class="font-semibold text-neutral-900 block text-sm">
                            {{ $canPreview ? 'Pratinjau Foto Baru (Belum Disimpan)' : ($user->avatar_url ? 'Foto Profil Aktif' : 'Avatar Inisial Sistem') }}
                        </span>
                        <p class="text-neutral-500 leading-relaxed">
                            Pilih berkas foto berformat <strong>JPG, JPEG, PNG, atau WEBP</strong> dengan ukuran maksimal <strong>2MB</strong>. Pratinjau langsung tampil sebelum Anda menyimpan perubahan.
                        </p>
                    </div>
                </div>

                <div>
                    <label for="profile_photo_file" class="block font-sans text-xs font-medium text-neutral-700 mb-1.5">
                        Pilih Berkas Foto dari Perangkat
                    </label>
                    <input type="file"
                           id="profile_photo_file"
                           wire:model="photo"
                           accept="image/png, image/jpeg, image/jpg, image/webp"
                           class="block w-full text-xs text-neutral-700 file:mr-3 file:py-2 file:px-3 file:rounded-md file:border file:border-neutral-200 file:text-xs file:font-medium file:bg-neutral-50 file:text-neutral-800 hover:file:bg-neutral-100 cursor-pointer border border-neutral-300 rounded-md p-1 focus:outline-none focus:ring-1 focus:ring-brand">
                    @error('photo')
                        <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2">
                    <button type="button"
                            wire:click="saveProfilePhoto"
                            wire:loading.attr="disabled"
                            {{ ! $photo ? 'disabled' : '' }}
                            class="w-full sm:w-auto px-4 py-2.5 sm:py-2 text-xs font-medium bg-brand hover:bg-brand-dark text-white rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-1 focus:ring-brand shadow-none flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="saveProfilePhoto">Simpan Foto Profil</span>
                        <span wire:loading wire:target="saveProfilePhoto">Menyimpan...</span>
                    </button>

                    @if ($user->avatar_url)
                        <button type="button"
                                wire:click="deleteProfilePhoto"
                                wire:loading.attr="disabled"
                                wire:confirm="Apakah Anda yakin ingin menghapus foto profil ini dan kembali ke avatar default?"
                                class="w-full sm:w-auto px-4 py-2.5 sm:py-2 text-xs font-medium border border-red-300 text-red-700 hover:bg-red-50 rounded-md transition-colors focus:outline-none focus:ring-1 focus:ring-red-400 text-center">
                            Hapus Foto Profil
                        </button>
                    @endif
                </div>
            </div>
        {{-- MODE 2: RANCANG AVATAR KUSTOM --}}
        @else
            <div id="panel-avatar-generate" role="tabpanel" aria-labelledby="tab-avatar-generate" class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                    {{-- Pratinjau Avatar Kustom Vektor --}}
                    <div class="shrink-0">
                        <div class="w-20 h-20 rounded-md flex items-center justify-center text-white font-sans font-bold text-2xl border border-neutral-200 shadow-none transition-colors"
                             style="background-color: {{ $selectedBgColor }};">
                            {{ strtoupper($customInitials ?: $initials) }}
                        </div>
                    </div>

                    <div class="space-y-1 text-xs font-sans flex-1">
                        <span class="font-semibold text-neutral-900 block text-sm">
                            Pratinjau Avatar Vektor
                        </span>
                        <p class="text-neutral-500 leading-relaxed">
                            Avatar ini dibuat secara otomatis dengan grafis vektor SVG tajam menggunakan kombinasi warna latar dan inisial huruf yang Anda tentukan di bawah.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    {{-- Pilihan Palet Warna --}}
                    <div>
                        <label class="block font-sans text-xs font-medium text-neutral-700 mb-2">
                            Pilih Warna Latar Belakang
                        </label>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            @php
                                $colorPalettes = [
                                    ['hex' => '#0B7840', 'name' => 'Brand Green'],
                                    ['hex' => '#085C30', 'name' => 'Dark Green'],
                                    ['hex' => '#334155', 'name' => 'Slate'],
                                    ['hex' => '#52525B', 'name' => 'Zinc'],
                                    ['hex' => '#92400E', 'name' => 'Amber Bronze'],
                                    ['hex' => '#0F766E', 'name' => 'Teal'],
                                    ['hex' => '#1E3A8A', 'name' => 'Navy'],
                                ];
                            @endphp

                            @foreach ($colorPalettes as $palette)
                                <div class="w-11 h-11 flex items-center justify-center">
                                    <button type="button"
                                            wire:key="color-{{ $palette['hex'] }}"
                                            wire:click="$set('selectedBgColor', '{{ $palette['hex'] }}')"
                                            aria-label="Pilih warna latar {{ $palette['name'] }} ({{ $palette['hex'] }})"
                                            aria-pressed="{{ $selectedBgColor === $palette['hex'] ? 'true' : 'false' }}"
                                            title="{{ $palette['name'] }} ({{ $palette['hex'] }})"
                                            class="w-8 h-8 rounded-md border-2 transition-transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2 {{ $selectedBgColor === $palette['hex'] ? 'border-neutral-900 ring-2 ring-neutral-400 ring-offset-1 scale-105' : 'border-transparent' }}"
                                            style="background-color: {{ $palette['hex'] }};">
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Inisial Huruf --}}
                    <div>
                        <label for="custom_initials" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                            Inisial Huruf (1–3 Karakter)
                        </label>
                        <input type="text"
                               id="custom_initials"
                               wire:model.live="customInitials"
                               maxlength="3"
                               placeholder="Contoh: CP"
                               class="w-full uppercase px-3 py-2 border border-neutral-300 rounded-md text-sm font-sans focus:ring-1 focus:ring-brand focus:border-brand text-neutral-900">
                        @error('customInitials')
                            <span class="text-xs text-red-600 mt-1 block font-sans">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 pt-2">
                    <button type="button"
                            wire:click="generateCustomAvatar"
                            wire:loading.attr="disabled"
                            class="w-full sm:w-auto px-4 py-2.5 sm:py-2 text-xs font-medium bg-brand hover:bg-brand-dark text-white rounded-md transition-colors focus:outline-none focus:ring-1 focus:ring-brand shadow-none flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="generateCustomAvatar">Terapkan Avatar Kustom</span>
                        <span wire:loading wire:target="generateCustomAvatar">Memproses...</span>
                    </button>

                    @if ($user->avatar_url)
                        <button type="button"
                                wire:click="deleteProfilePhoto"
                                wire:loading.attr="disabled"
                                wire:confirm="Apakah Anda yakin ingin menghapus foto profil dan kembali ke avatar default?"
                                class="w-full sm:w-auto px-4 py-2.5 sm:py-2 text-xs font-medium border border-neutral-200 text-neutral-700 hover:bg-neutral-50 rounded-md transition-colors text-center">
                            Kembalikan ke Default
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- =========================================================================
         5. PENGATURAN AKUN (Pembaruan Profil & Ubah Password)
         ========================================================================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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

                <div class="pt-2 flex items-center gap-3">
                    <x-ui.button type="submit" variant="primary">
                        Simpan Perubahan
                    </x-ui.button>
                    <x-ui.button type="button" variant="secondary" wire:click="cancelProfileEdit">
                        Batal
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
