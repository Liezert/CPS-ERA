<div class="relative" x-data="{ notifDropdownOpen: false }">
    {{-- Bell Icon Button Trigger --}}
    <button type="button"
            @click="notifDropdownOpen = !notifDropdownOpen"
            @click.outside="notifDropdownOpen = false"
            class="relative p-2 text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-brand-dark"
            aria-label="Lihat notifikasi"
            :aria-expanded="notifDropdownOpen.toString()">
        @include('components.layout.nav-icon', ['name' => 'bell', 'class' => 'w-5 h-5'])

        {{-- Notification Indicator Badge (Sharp corners per DS §5) --}}
        @if ($unreadCount > 0)
            <span class="absolute top-1.5 right-1.5 flex items-center justify-center min-w-[16px] h-4 px-1 bg-brand text-white font-mono text-[10px] font-bold rounded-badge leading-none">
                {{ $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown Panel Notifikasi --}}
    <div x-show="notifDropdownOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-150 transform"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100 transform"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-neutral-200 rounded-lg shadow-sm z-50 overflow-hidden">
        
        {{-- Header Notifikasi --}}
        <div class="px-4 py-3 border-b border-neutral-200 bg-neutral-50/70 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="font-sans font-semibold text-sm text-neutral-900">Notifikasi</span>
                @if ($unreadCount > 0)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-badge text-[10px] font-bold font-mono bg-brand text-white">
                        {{ $unreadCount }} Baru
                    </span>
                @endif
            </div>

            <button type="button" 
                    wire:click="markAllAsRead"
                    class="text-xs font-sans text-brand hover:text-brand-dark transition-colors font-medium focus:outline-none">
                Tandai dibaca
            </button>
        </div>

        {{-- List Notifikasi (Hairline divider tipis: divide-y divide-neutral-200 per Design System §5) --}}
        <div class="max-h-96 overflow-y-auto divide-y divide-neutral-200">
            @forelse ($notifications as $item)
                <div wire:key="notif-{{ $item->id }}"
                     wire:click="markAsRead('{{ $item->id }}')"
                     class="p-3.5 flex items-start gap-3 hover:bg-neutral-50 transition-colors cursor-pointer {{ is_null($item->read_at) ? 'bg-brand-tint/25' : 'bg-white' }}">
                    
                    {{-- Ikon Penanda Tipe Event (Netral outline per Design System §5 & Anti-AI-Slop) --}}
                    <div class="w-8 h-8 rounded-md bg-neutral-100 border border-neutral-200 flex items-center justify-center shrink-0 text-neutral-600 mt-0.5">
                        @switch($item->type)
                            @case('knowledge_baru')
                                @include('components.layout.nav-icon', ['name' => 'book', 'class' => 'w-4 h-4 text-neutral-600'])
                                @break

                            @case('misi_baru')
                                @include('components.layout.nav-icon', ['name' => 'puzzle', 'class' => 'w-4 h-4 text-neutral-600'])
                                @break

                            @case('ba_review')
                            @case('ba_ditolak_hr')
                            @case('ba_revisi')
                                @include('components.layout.nav-icon', ['name' => 'shield-alert', 'class' => 'w-4 h-4 text-neutral-600'])
                                @break

                            @case('achievement_baru')
                                @include('components.layout.nav-icon', ['name' => 'badge-check', 'class' => 'w-4 h-4 text-neutral-600'])
                                @break

                            @default
                                @include('components.layout.nav-icon', ['name' => 'bell', 'class' => 'w-4 h-4 text-neutral-600'])
                        @endswitch
                    </div>

                    {{-- Konten Notifikasi --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-1.5">
                            <p class="font-sans text-xs font-semibold text-neutral-900 leading-snug">
                                {{ $item->title }}
                            </p>
                            @if (is_null($item->read_at))
                                <span class="w-2 h-2 rounded-full bg-brand shrink-0 mt-1" title="Belum dibaca" aria-label="Belum dibaca"></span>
                            @endif
                        </div>
                        <p class="font-sans text-[11px] text-neutral-600 mt-0.5 line-clamp-2 leading-relaxed">
                            {{ $item->message }}
                        </p>
                        <span class="font-mono text-[10px] text-neutral-500 font-medium mt-1 block">
                            {{ $item->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center">
                    <div class="w-10 h-10 mx-auto rounded-full bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400 mb-2">
                        @include('components.layout.nav-icon', ['name' => 'bell', 'class' => 'w-5 h-5'])
                    </div>
                    <p class="font-sans text-xs font-medium text-neutral-700">Tidak ada notifikasi</p>
                    <p class="font-sans text-[11px] text-neutral-500 mt-0.5">Semua pembaruan aktivitas pabrik akan muncul di sini.</p>
                </div>
            @endforelse
        </div>

        {{-- Footer Notifikasi --}}
        <div class="p-2.5 border-t border-neutral-200 bg-neutral-50 text-center">
            <a href="{{ route('achievements.index') }}" 
               class="font-sans text-xs font-medium text-neutral-700 hover:text-brand transition-colors block">
                Lihat Semua Notifikasi
            </a>
        </div>
    </div>
</div>
