<div class="space-y-6">
    {{-- Breadcrumb & Header Halaman --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-neutral-200 pb-5">
        <div>
            <nav class="flex items-center gap-2 font-mono text-xs text-neutral-500 mb-1.5" aria-label="Breadcrumb">
                <a href="{{ route('dashboard') }}" class="hover:text-brand transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-neutral-900 font-medium">Achievement</span>
            </nav>
            <h1 class="font-sans font-bold text-2xl text-neutral-900 tracking-tight">
                Pencapaian & Lencana
            </h1>
            <p class="font-sans text-xs sm:text-sm text-neutral-600 mt-1">
                Katalog lencana prestasi dan pencapaian profesional pegawai PT Catur Pilar Sejahtera.
            </p>
        </div>

        {{-- Ringkasan Metrik Sederhana (Sharp, Minimalist) --}}
        <div class="flex items-center gap-3">
            <div class="px-3.5 py-2 bg-white border border-neutral-200 rounded-lg text-center min-w-[90px]">
                <div class="font-mono text-xs text-neutral-500 uppercase tracking-wider">Total</div>
                <div class="font-mono font-bold text-lg text-neutral-900">{{ $totalCount }}</div>
            </div>
            <div class="px-3.5 py-2 bg-white border border-neutral-200 rounded-lg text-center min-w-[90px]">
                <div class="font-mono text-xs text-brand uppercase tracking-wider">Unlocked</div>
                <div class="font-mono font-bold text-lg text-brand">{{ $unlockedCount }}</div>
            </div>
            <div class="px-3.5 py-2 bg-white border border-neutral-200 rounded-lg text-center min-w-[90px]">
                <div class="font-mono text-xs text-neutral-400 uppercase tracking-wider">Locked</div>
                <div class="font-mono font-bold text-lg text-neutral-500">{{ $lockedCount }}</div>
            </div>
        </div>
    </div>

    {{-- DoD #3: Kriteria unlock ditandai TODO sesuai PRD §5.3 (Poin 5) --}}
    {{-- TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement) --}}
    <div class="p-3.5 bg-neutral-50 border border-neutral-200 rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs font-sans text-neutral-600">
        <div class="flex items-center gap-2">
            <div class="w-2 h-2 rounded-full bg-neutral-400 shrink-0"></div>
            <span>
                <strong>Catatan Kriteria:</strong> [Menunggu Keputusan PRD §5.3: Kriteria Otomatisasi Unlock Achievement]
            </span>
        </div>
        <span class="font-mono text-[11px] text-neutral-400">
            <!-- TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement) -->
            Interim: Status diverifikasi langsung dari basis data user_achievements
        </span>
    </div>

    {{-- Grid Achievement (Design System §4: 3-4 kolom desktop, 2 kolom tablet, 1 kolom mobile) --}}
    {{-- DoD #2: Unlocked/Locked dibedakan LEWAT WARNA IKON SAJA (bukan dekorasi tambahan) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 desktop:grid-cols-3 gap-5">
        @foreach ($achievements as $item)
            <div wire:key="achievement-{{ $item->id }}"
                 class="bg-white border border-neutral-200 rounded-lg p-5 flex flex-col items-center text-center">
                
                {{-- Lingkaran Wadah Ikon (Styling identik: bg-neutral-50 border border-neutral-200) --}}
                {{-- PEMBEDA SATU-SATUNYA: Unlocked = text-brand, Locked = text-neutral-400 opacity-40 --}}
                <div class="w-16 h-16 rounded-full bg-neutral-50 border border-neutral-200 flex items-center justify-center {{ $item->is_unlocked ? 'text-brand' : 'text-neutral-400 opacity-40' }}">
                    @switch($item->icon)
                        @case('heroicon-o-book-open')
                        @case('book')
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                            @break

                        @case('heroicon-o-academic-cap')
                        @case('academic')
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-5.25 6.557c0 1.838 2.35 3.325 5.25 3.325s5.25-1.487 5.25-3.325m0-6.557v3.675" />
                            </svg>
                            @break

                        @case('heroicon-o-sparkles')
                        @case('sparkles')
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                            </svg>
                            @break

                        @case('heroicon-o-shield-check')
                        @case('shield-check')
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            @break

                        @case('heroicon-o-puzzle-piece')
                        @case('puzzle')
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.75.75 0 01-.75.75H4.5A2.25 2.25 0 002.25 9v1.5a.75.75 0 00.75.75h0c.355 0 .676-.186.959-.401.29-.221.634-.349 1.003-.349 1.035 0 1.875 1.007 1.875 2.25s-.84 2.25-1.875 2.25c-.369 0-.713-.128-1.003-.349-.283-.215-.604-.401-.959-.401h0a.75.75 0 00-.75.75V18A2.25 2.25 0 004.5 20.25h6a.75.75 0 00.75-.75v0c0-.355-.186-.676-.401-.959a2.235 2.235 0 01-.349-1.003c0-1.035 1.007-1.875 2.25-1.875s2.25.84 2.25 1.875c0 .369-.128.713-.349 1.003-.215.283-.401.604-.401.959v0a.75.75 0 00.75.75h1.5A2.25 2.25 0 0021.75 18v-6a.75.75 0 00-.75-.75h0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.036 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.369 0 .713.128 1.003.349.283.215.604.401.959.401h0a.75.75 0 00.75-.75V9A2.25 2.25 0 0019.5 6.75h-4.5a.75.75 0 01-.75-.75v0z" />
                            </svg>
                            @break

                        @case('heroicon-o-check-badge')
                        @case('badge-check')
                        @default
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                            </svg>
                    @endswitch
                </div>

                {{-- Konten Nama & Deskripsi --}}
                <h3 class="font-sans font-semibold text-sm text-neutral-900 mt-3.5">
                    {{ $item->name }}
                </h3>
                <p class="font-sans text-xs text-neutral-600 mt-1.5 leading-relaxed line-clamp-3">
                    {{ $item->description }}
                </p>

                {{-- Status Footnote (Clean, Non-decorative) --}}
                <div class="mt-4 pt-3 border-t border-neutral-100 w-full flex items-center justify-between text-[11px] font-mono">
                    <span class="text-neutral-400">Status:</span>
                    @if ($item->is_unlocked)
                        <span class="text-brand font-medium">
                            Unlocked ({{ $item->unlocked_at ? $item->unlocked_at->format('d M Y') : 'Aktif' }})
                        </span>
                    @else
                        <span class="text-neutral-400">
                            Locked
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
