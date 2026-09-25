<div class="space-y-6">
    {{-- Header Section --}}
    <div class="bg-neutral-50/70 border border-neutral-200 rounded-lg p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 tracking-tight">Leaderboard Pegawai</h1>
            <p class="text-xs sm:text-sm text-neutral-600 mt-1">
                Peringkat kontribusi dan akumulasi poin kompetensi seluruh pegawai PT Catur Pilar Sejahtera.
            </p>
        </div>
    </div>

    {{-- Banner Status Peringkat Pengguna Login (Design System §2: Highlight brand-tint halus) --}}
    @if ($currentUser)
        <div class="bg-brand-tint border border-brand/30 rounded-lg p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                {{-- Avatar Pegawai (Foto Profil atau Inisial) --}}
                @if ($currentUser->avatar_url)
                    <img src="{{ asset($currentUser->avatar_url) }}"
                         alt="{{ $currentUser->name }}"
                         class="w-12 h-12 rounded-full object-cover border border-neutral-200 shrink-0">
                @else
                    <div class="w-12 h-12 rounded-full bg-brand text-white flex items-center justify-center font-semibold text-base shrink-0">
                        {{ strtoupper(substr($currentUser->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-semibold text-brand-dark">{{ $currentUser->name }}</h2>
                        <span class="inline-flex items-center text-[10px] font-sans font-medium text-brand-dark bg-white border border-brand/40 px-1.5 py-0.5 rounded-[2px]">
                            Akun Anda
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-neutral-700 mt-0.5">
                        <span class="font-medium">{{ $currentUser->division?->name ?? 'Lintas Divisi' }}</span>
                        <span>•</span>
                        <span>{{ $currentUser->jabatan ?: 'Pegawai' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 sm:gap-6 divide-x divide-brand/20 pt-2 md:pt-0 border-t md:border-t-0 border-brand/20">
                <div class="text-left md:text-right">
                    <span class="text-[11px] uppercase tracking-wider text-brand-dark/75 font-semibold font-sans block">Peringkat Anda</span>
                    <span class="text-2xl sm:text-3xl font-extrabold font-mono text-brand-dark">#{{ $myRank ?? '-' }}</span>
                </div>
                <div class="pl-4 sm:pl-6 text-left md:text-right">
                    <span class="text-[11px] uppercase tracking-wider text-brand-dark/75 font-semibold font-sans block">Total XP</span>
                    <span class="text-2xl sm:text-3xl font-extrabold font-mono text-brand-dark">{{ number_format((int) ($currentUser->xp ?? 0)) }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Filter & Search Bar --}}
    <div class="bg-neutral-50/60 border border-neutral-200 rounded-lg p-3 sm:p-4 space-y-3">
        <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
            {{-- Search Bar Reaktif --}}
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-neutral-500">
                    @include('components.layout.nav-icon', ['name' => 'search', 'class' => 'w-4 h-4'])
                </span>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Cari nama pegawai..."
                       class="w-full pl-9 pr-3 py-1.5 text-sm bg-white border border-neutral-200 rounded-md focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand text-neutral-900 placeholder:text-neutral-500 transition" />
            </div>

            {{-- Filter Controls --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Filter Divisi --}}
                <select wire:model.live="selectedDivision"
                        class="text-xs py-1.5 px-2.5 bg-white border border-neutral-200 rounded-md text-neutral-800 font-medium focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand">
                    <option value="all">Semua Divisi ({{ $divisions->count() }} Divisi)</option>
                    @foreach ($divisions as $div)
                        <option value="{{ $div->id }}">{{ $div->name }}</option>
                    @endforeach
                </select>

                {{-- Tab Periode (Data Contract §2 Bucket A Item 5: All Time) --}}
                <span class="inline-flex items-center text-xs py-1.5 px-3 bg-white border border-neutral-200 rounded-md text-neutral-700 font-sans font-medium">
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
                <tr class="bg-neutral-50 border-b border-neutral-200 text-[11px] uppercase tracking-wider text-neutral-700 font-bold font-sans">
                    <th scope="col" class="py-3 px-4 w-20 text-center">Rank</th>
                    <th scope="col" class="py-3 px-4">Nama Pegawai</th>
                    <th scope="col" class="py-3 px-4">Divisi</th>
                    <th scope="col" class="py-3 px-4 text-right">Points</th>
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
                        class="transition {{ $isMe ? 'bg-brand-tint border-l-4 border-l-brand' : ($rank === 1 ? 'bg-amber-50/25 hover:bg-amber-50/40 border-l-4 border-l-amber-400' : 'bg-white hover:bg-neutral-50/70') }}">
                        
                        {{-- Kolom 1: Rank (Industrial Insignia untuk Top 3) --}}
                        <td class="py-3.5 px-4 text-center font-mono text-sm">
                            @if ($rank === 1)
                                <span class="inline-flex items-center justify-center gap-1 px-2.5 py-1 rounded-badge bg-amber-50 text-amber-900 font-bold text-xs border border-amber-400/90 shadow-2xs group-hover:scale-105 transition-transform" title="Peringkat 1 (Juara Performa)">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    <span>#1</span>
                                </span>
                            @elseif ($rank === 2)
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-badge bg-slate-50 text-slate-800 font-bold text-xs border border-slate-300 shadow-xs" title="Peringkat 2">
                                    #2
                                </span>
                            @elseif ($rank === 3)
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-badge bg-orange-50 text-orange-900 font-bold text-xs border border-orange-300 shadow-xs" title="Peringkat 3">
                                    #3
                                </span>
                            @else
                                <span class="text-neutral-500 font-medium">#{{ $rank }}</span>
                            @endif
                        </td>

                        {{-- Kolom 2: Nama & Info Pegawai --}}
                        <td class="py-3.5 px-4">
                            <div class="flex items-center gap-3">
                                @if ($user->avatar_url)
                                    <img src="{{ asset($user->avatar_url) }}"
                                         alt="{{ $user->name }}"
                                         class="w-8 h-8 rounded-full object-cover border {{ $rank === 1 ? 'border-amber-400 ring-2 ring-amber-200/50' : 'border-neutral-200' }} shrink-0">
                                @else
                                    <div class="w-8 h-8 rounded-full {{ $isMe ? 'bg-brand text-white' : ($rank === 1 ? 'bg-amber-100 text-amber-900 border border-amber-300 font-bold' : 'bg-neutral-100 text-neutral-700') }} flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-sm font-semibold {{ $isMe ? 'text-brand-dark' : ($rank === 1 ? 'text-amber-950 font-bold' : 'text-neutral-900') }}">
                                            {{ $user->name }}
                                        </span>
                                        @if ($isMe)
                                            <span class="inline-flex items-center text-[10px] font-sans font-medium text-brand-dark bg-white border border-brand/40 px-1 py-0.5 rounded-[2px]">
                                                (Anda)
                                            </span>
                                        @endif
                                        @if ($rank === 1)
                                            <span class="inline-flex items-center gap-1 text-[10px] font-mono font-bold text-amber-900 bg-amber-100/90 border border-amber-300 px-1.5 py-0.5 rounded-[2px] shadow-2xs">
                                                Top Leader
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Kolom 3: Divisi --}}
                        <td class="py-3.5 px-4 text-xs text-neutral-700">
                            {{ $user->division?->name ?? 'Lintas Divisi' }}
                        </td>

                        {{-- Kolom 4: XP (Terurut Descending dengan tabular-nums) --}}
                        <td class="py-3.5 px-4 text-right">
                            <span class="font-mono text-sm font-bold tracking-tight tabular-nums {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                                {{ number_format($user->xp) }}
                            </span>
                            <span class="text-xs text-neutral-500 font-sans ml-1">XP</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-10 px-4 text-center text-xs text-neutral-500">
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
                 class="rounded-lg p-4 border transition space-y-3 {{ $isMe ? 'bg-brand-tint border-brand/50' : ($rank === 1 ? 'bg-amber-50/20 border-amber-300/80 shadow-2xs' : 'bg-white border-neutral-200') }}">
                
                {{-- Baris Atas Card: Rank --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        @if ($rank === 1)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-badge bg-amber-50 text-amber-900 font-mono font-bold text-xs border border-amber-400/90 shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-amber-500 fill-current shrink-0" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                #1
                            </span>
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-[2px] bg-amber-100/70 border border-amber-300/80 text-[10px] font-semibold text-amber-800 tracking-wide uppercase">
                                Top Leader
                            </span>
                        @elseif ($rank === 2)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-badge bg-slate-50 text-slate-800 font-mono font-bold text-xs border border-slate-300 shadow-xs">
                                #2
                            </span>
                        @elseif ($rank === 3)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-badge bg-orange-50 text-orange-900 font-mono font-bold text-xs border border-orange-300 shadow-xs">
                                #3
                            </span>
                        @else
                            <span class="font-mono text-sm font-bold {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                                #{{ $rank }}
                            </span>
                        @endif
                        @if ($isMe)
                            <span class="text-[10px] font-sans font-medium text-brand-dark bg-white border border-brand/40 px-1.5 py-0.5 rounded-[2px]">
                                (Anda)
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Baris Tengah Card: Avatar, Nama, Divisi --}}
                <div class="flex items-center gap-3 pt-1">
                    @if ($user->avatar_url)
                        <img src="{{ asset($user->avatar_url) }}"
                             alt="{{ $user->name }}"
                             class="w-10 h-10 rounded-full object-cover border {{ $rank === 1 ? 'border-amber-400 ring-2 ring-amber-200/60' : 'border-neutral-200' }} shrink-0">
                    @else
                        <div class="w-10 h-10 rounded-full {{ $isMe ? 'bg-brand text-white' : ($rank === 1 ? 'bg-amber-100 text-amber-900 border border-amber-300 ring-2 ring-amber-200/60' : 'bg-neutral-100 text-neutral-700') }} flex items-center justify-center text-xs font-semibold shrink-0">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                    <div>
                        <div class="text-sm font-semibold {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                            {{ $user->name }}
                        </div>
                        <div class="text-xs text-neutral-500 mt-0.5">
                            {{ $user->division?->name ?? 'Lintas Divisi' }}
                        </div>
                    </div>
                </div>

                {{-- Baris Bawah Card: Akumulasi XP --}}
                <div class="pt-2 border-t {{ $isMe ? 'border-brand/20' : 'border-neutral-100' }} flex items-center justify-between">
                    <span class="text-xs text-neutral-500 font-sans">Total Akumulasi XP:</span>
                    <div class="font-mono font-bold text-sm {{ $isMe ? 'text-brand-dark' : 'text-neutral-900' }}">
                        {{ number_format($user->xp) }} <span class="text-xs font-normal text-neutral-500 font-sans">XP</span>
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
