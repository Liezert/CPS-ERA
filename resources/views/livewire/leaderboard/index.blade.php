<div class="space-y-6">
    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-neutral-200">
        <div>
            <h1 class="text-2xl font-semibold text-neutral-900 tracking-tight">Leaderboard Pegawai</h1>
            <p class="text-sm text-neutral-500 mt-1">
                Peringkat kontribusi dan akumulasi poin kompetensi seluruh pegawai PT Catur Pilar Sejahtera.
            </p>
        </div>
    </div>

    {{-- Banner Status Peringkat Pengguna Login (Design System §2: Highlight brand-tint halus) --}}
    @if ($currentUser)
        <div class="bg-brand-tint border border-brand/30 rounded-lg p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                {{-- Avatar Pegawai --}}
                <div class="w-12 h-12 rounded-full bg-brand text-white flex items-center justify-center font-semibold text-base shrink-0">
                    {{ strtoupper(substr($currentUser->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-semibold text-brand-dark">{{ $currentUser->name }}</h2>
                        <span class="inline-flex items-center text-[10px] font-sans font-medium text-brand-dark bg-white border border-brand/40 px-1.5 py-0.5 rounded-[2px]">
                            Akun Anda
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-neutral-600 mt-0.5">
                        <span class="font-mono text-neutral-700">{{ $currentUser->employee_id }}</span>
                        <span>•</span>
                        <span>{{ $currentUser->division?->name ?? 'Lintas Divisi' }}</span>
                        <span>•</span>
                        <span>{{ $currentUser->jabatan ?: 'Pegawai' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 sm:gap-6 divide-x divide-brand/20 pt-2 md:pt-0 border-t md:border-t-0 border-brand/20">
                <div class="text-left md:text-right">
                    <span class="text-[11px] uppercase tracking-wider text-neutral-500 font-sans block">Peringkat Anda</span>
                    <span class="text-xl font-bold font-mono text-brand-dark">#{{ $myRank ?? '-' }}</span>
                </div>
                <div class="pl-4 sm:pl-6 text-left md:text-right">
                    <span class="text-[11px] uppercase tracking-wider text-neutral-500 font-sans block">Total Poin</span>
                    <span class="text-xl font-bold font-mono text-brand-dark">{{ number_format($currentUser->total_points) }}</span>
                </div>
                <div class="pl-4 sm:pl-6 text-left md:text-right">
                    <span class="text-[11px] uppercase tracking-wider text-neutral-500 font-sans block">Level</span>
                    <span class="text-xl font-bold font-sans text-brand-dark">Lv.{{ $currentUser->level }}</span>
                </div>
            </div>
        </div>
    @endif

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
                       placeholder="Cari nama pegawai atau ID pegawai (CPS-XXXXX)..."
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-neutral-50 border border-neutral-200 rounded-md focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand text-neutral-900 placeholder:text-neutral-400 transition" />
            </div>

            {{-- Filter Controls --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Filter Divisi (13 Divisi Tetap) --}}
                <select wire:model.live="selectedDivision"
                        class="text-xs py-1.5 px-2.5 bg-white border border-neutral-200 rounded-md text-neutral-700 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand">
                    <option value="all">Semua Divisi (13 Divisi)</option>
                    @foreach ($divisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                </select>

                {{-- Tab Periode (Data Contract §2 Bucket A Item 5: All Time) --}}
                <span class="inline-flex items-center text-xs py-1.5 px-3 bg-neutral-50 border border-neutral-200 rounded-md text-neutral-600 font-sans">
                    Semua Waktu (All Time)
                </span>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         1. TAMPILAN DESKTOP & TABLET: SEMANTIC TABLE (hidden md:block)
         ========================================================================= --}}
    <div class="hidden md:block bg-white border border-neutral-200 rounded-lg overflow-hidden">
        <table class="w-full text-left border-collapse" id="leaderboard-table">
            <thead>
                <tr class="bg-neutral-50 border-b border-neutral-200 text-[11px] uppercase tracking-wider text-neutral-500 font-semibold font-sans">
                    <th scope="col" class="py-3 px-4 w-20 text-center">Rank</th>
                    <th scope="col" class="py-3 px-4">Nama Pegawai</th>
                    <th scope="col" class="py-3 px-4">Divisi</th>
                    <th scope="col" class="py-3 px-4 text-right">Points</th>
                    <th scope="col" class="py-3 px-4 text-center w-28">Level</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200">
                @forelse ($users as $index => $user)
                    @php
                        $rank = (($users->currentPage() - 1) * $users->perPage()) + $loop->iteration;
                        $isMe = ($currentUser && $user->id === $currentUser->id);
                    @endphp
                    {{-- DoD #2: Baris user sendiri di-highlight pakai brand-tint (bukan warna solid) --}}
                    <tr wire:key="user-row-{{ $user->id }}"
                        class="transition {{ $isMe ? 'bg-brand-tint border-l-4 border-l-brand' : 'bg-white hover:bg-neutral-50/70' }}">
                        
                        {{-- Kolom 1: Rank --}}
                        <td class="py-3.5 px-4 text-center font-mono text-sm">
                            @if ($rank === 1)
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-100 text-amber-800 font-bold text-xs border border-amber-300">
                                    #1
                                </span>
                            @elseif ($rank === 2)
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-100 text-slate-700 font-bold text-xs border border-slate-300">
                                    #2
                                </span>
                            @elseif ($rank === 3)
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-amber-50 text-amber-700 font-bold text-xs border border-amber-200">
                                    #3
                                </span>
                            @else
                                <span class="text-neutral-500 font-medium">#{{ $rank }}</span>
                            @endif
                        </td>

                        {{-- Kolom 2: Nama & Info Pegawai --}}
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full {{ $isMe ? 'bg-brand text-white' : 'bg-neutral-100 text-neutral-700' }} flex items-center justify-center text-xs font-semibold shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-semibold {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                                            {{ $user->name }}
                                        </span>
                                        @if ($isMe)
                                            <span class="inline-flex items-center text-[10px] font-sans font-medium text-brand-dark bg-white border border-brand/40 px-1 py-0.5 rounded-[2px]">
                                                (Anda)
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-neutral-500 font-mono">
                                        {{ $user->employee_id }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Kolom 3: Divisi --}}
                        <td class="py-3.5 px-4 text-xs text-neutral-700">
                            {{ $user->division?->name ?? 'Lintas Divisi' }}
                        </td>

                        {{-- Kolom 4: Points (Terurut Descending) --}}
                        <td class="py-3.5 px-4 text-right">
                            <span class="font-mono text-sm font-semibold {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                                {{ number_format($user->total_points) }}
                            </span>
                            <span class="text-xs text-neutral-500 font-sans ml-1">Poin</span>
                        </td>

                        {{-- Kolom 5: Level --}}
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded-[2px] border {{ $isMe ? 'border-brand text-brand-dark bg-white' : 'border-neutral-200 text-neutral-700 bg-neutral-50' }}">
                                Level {{ $user->level }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 px-4 text-center text-xs text-neutral-500">
                            Tidak ada data pegawai yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- =========================================================================
         2. TAMPILAN MOBILE: LIST CARD BERTUMPUK (block md:hidden)
         DoD #3: Table berubah jadi card per baris di mobile (Design System §4)
         ========================================================================= --}}
    <div class="block md:hidden space-y-3" id="leaderboard-mobile-cards">
        @forelse ($users as $index => $user)
            @php
                $rank = (($users->currentPage() - 1) * $users->perPage()) + $loop->iteration;
                $isMe = ($currentUser && $user->id === $currentUser->id);
            @endphp
            {{-- 1 Card per Baris Pegawai --}}
            <div wire:key="mobile-card-{{ $user->id }}"
                 class="rounded-lg p-4 border transition space-y-3 {{ $isMe ? 'bg-brand-tint border-brand/50' : 'bg-white border-neutral-200' }}">
                
                {{-- Baris Atas Card: Rank & Level --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-sm font-bold {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                            #{{ $rank }}
                        </span>
                        @if ($isMe)
                            <span class="text-[10px] font-sans font-medium text-brand-dark bg-white border border-brand/40 px-1.5 py-0.5 rounded-[2px]">
                                (Anda)
                            </span>
                        @endif
                    </div>

                    <span class="text-xs font-medium px-2 py-0.5 rounded-[2px] border {{ $isMe ? 'border-brand text-brand-dark bg-white' : 'border-neutral-200 text-neutral-700 bg-neutral-50' }}">
                        Level {{ $user->level }}
                    </span>
                </div>

                {{-- Baris Tengah Card: Avatar, Nama, ID, Divisi --}}
                <div class="flex items-center gap-3 pt-1">
                    <div class="w-10 h-10 rounded-full {{ $isMe ? 'bg-brand text-white' : 'bg-neutral-100 text-neutral-700' }} flex items-center justify-center text-xs font-semibold shrink-0">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="text-sm font-semibold {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                            {{ $user->name }}
                        </div>
                        <div class="flex items-center gap-2 text-xs text-neutral-500 mt-0.5">
                            <span class="font-mono">{{ $user->employee_id }}</span>
                            <span>•</span>
                            <span>{{ $user->division?->name ?? 'Lintas Divisi' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Baris Bawah Card: Akumulasi Poin --}}
                <div class="pt-2 border-t {{ $isMe ? 'border-brand/20' : 'border-neutral-100' }} flex items-center justify-between">
                    <span class="text-xs text-neutral-500 font-sans">Total Akumulasi Poin:</span>
                    <div class="font-mono font-bold text-sm {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                        {{ number_format($user->total_points) }} <span class="text-xs font-normal text-neutral-500 font-sans">Poin</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white border border-neutral-200 rounded-lg p-8 text-center text-xs text-neutral-500">
                Tidak ada data pegawai yang sesuai dengan filter pencarian.
            </div>
        @endforelse
    </div>

    {{-- Pagination Controls --}}
    @if ($users->hasPages())
        <div class="pt-2">
            {{ $users->links() }}
        </div>
    @endif
</div>
