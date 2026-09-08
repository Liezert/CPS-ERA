@php
    $user = auth()->user();
    $roles = $user ? $user->getRoleNames() : collect(['employee']);
    $primaryRole = $roles->first() ?? 'employee';

    $isEmployee = $roles->contains('employee');
    $isSupervisor = $roles->contains('supervisor');
    $isQuality = $roles->contains('quality');
    $isAdmin = $roles->contains('admin');

    // Menu navigasi utama yang disepakati di Data Contract §4 & Design System §8
    $navItems = [
        [
            'title' => 'Dashboard',
            'route' => 'dashboard',
            'url' => route('dashboard'),
            'icon' => 'home',
            'active' => request()->routeIs('dashboard'),
            'roles' => ['employee', 'supervisor', 'quality', 'admin'],
        ],
        [
            'title' => 'Knowledge Repository',
            'route' => 'knowledge.index',
            'url' => route('knowledge.index'),
            'icon' => 'book',
            'active' => request()->is('knowledge*'),
            'roles' => ['employee', 'supervisor', 'quality', 'admin'],
        ],
        [
            'title' => 'BA & Lesson Learned',
            'route' => 'ba.index',
            'url' => route('ba.index'),
            'icon' => 'shield-alert',
            'active' => request()->is('ba-incidents*'),
            'badge' => $isSupervisor ? 'Divisi' : null,
            'roles' => ['employee', 'supervisor', 'quality', 'admin'],
        ],
        [
            'title' => 'Learning',
            'route' => 'learning.index',
            'url' => route('learning.index'),
            'icon' => 'academic',
            'active' => request()->is('learning*'),
            'roles' => ['employee', 'supervisor', 'quality', 'admin'],
        ],
        [
            'title' => 'Mission & Game',
            'route' => 'missions.index',
            'url' => route('missions.index'),
            'icon' => 'puzzle',
            'active' => request()->is('missions*'),
            'roles' => ['employee', 'supervisor', 'quality', 'admin'],
        ],
        [
            'title' => 'Leaderboard',
            'route' => 'leaderboard.index',
            'url' => route('leaderboard.index'),
            'icon' => 'chart',
            'active' => request()->is('leaderboard*'),
            'roles' => ['employee', 'supervisor', 'quality', 'admin'],
        ],
        [
            'title' => 'Achievement',
            'route' => 'achievements.index',
            'url' => route('achievements.index'),
            'icon' => 'badge-check',
            'active' => request()->is('achievements*'),
            'roles' => ['employee', 'supervisor', 'quality', 'admin'],
        ],
    ];
@endphp

{{-- =========================================================================
     1. DESKTOP & TABLET SIDEBAR (Fixed Left Sidebar)
     - Desktop (>= 1440px): Full sidebar w-64 (label teks + ikon)
     - Tablet (820px - 1439px): Icon-only sidebar w-20
     - Mobile (< 820px): Hidden (digantikan drawer & bottom-nav)
     ========================================================================= --}}
<aside class="hidden tablet:flex flex-col fixed inset-y-0 left-0 z-30 bg-white border-r border-neutral-200 transition-all duration-200 tablet:w-20 desktop:w-64">
    
    {{-- Header Logo Brand (Design System §2: Logo di atas putih, clear space 25%) --}}
    <div class="h-16 flex items-center px-4 tablet:justify-center desktop:justify-start border-b border-neutral-200 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            {{-- Logo Mark CPS Resmi (#0B7840) --}}
            <div class="w-10 h-10 rounded-md bg-white border border-neutral-200 p-1 flex items-center justify-center shrink-0 shadow-2xs">
                <img src="{{ asset('images/cps-logo.png') }}" alt="PT. Catur Pilar Sejahtera" class="w-full h-full object-contain" />
            </div>
            
            {{-- Wordmark: font-bold sesuai Design System §3 --}}
            <div class="hidden desktop:block">
                <div class="font-sans font-semibold text-base text-neutral-900 tracking-tight leading-none flex items-center gap-1.5">
                    <span>CPS ERA</span>
                    <span class="text-[10px] font-mono px-1 py-0.2 bg-brand-tint text-brand-dark border border-brand/20 rounded-[2px]">Hub</span>
                </div>
                <div class="font-sans text-[10px] text-neutral-500 mt-1 uppercase tracking-wider leading-none">
                    PT Catur Pilar Sejahtera
                </div>
            </div>
        </a>
    </div>

    {{-- Daftar Navigasi --}}
    <div class="flex-1 overflow-y-auto py-5 px-3 space-y-1">
        <div class="hidden desktop:block px-3 mb-2">
            <span class="text-[11px] font-sans font-medium text-neutral-500 uppercase tracking-wider">
                Menu Utama
            </span>
        </div>

        @foreach ($navItems as $item)
            @if ($roles->intersect($item['roles'])->isNotEmpty())
                <a href="{{ $item['url'] }}"
                   title="{{ $item['title'] }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-md font-sans text-sm transition-colors duration-150 {{ $item['active'] ? 'bg-brand-tint text-brand-dark font-medium' : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50' }} tablet:justify-center desktop:justify-between group">
                    <div class="flex items-center gap-3">
                        {{-- Ikon Navigasi --}}
                        <div class="shrink-0 {{ $item['active'] ? 'text-brand' : 'text-neutral-500 group-hover:text-neutral-900' }}">
                            @include('components.layout.nav-icon', ['name' => $item['icon']])
                        </div>
                        
                        {{-- Label Teks (Hidden di Tablet, Muncul di Desktop) --}}
                        <span class="hidden desktop:inline truncate">
                            {{ $item['title'] }}
                        </span>
                    </div>

                    {{-- Badge Khusus Role (Misal: Supervisor Divisi) --}}
                    @if (!empty($item['badge']))
                        <span class="hidden desktop:inline-flex">
                            <x-ui.badge status="brand" class="text-[10px] px-1.5 py-0">
                                {{ $item['badge'] }}
                            </x-ui.badge>
                        </span>
                    @endif
                </a>
            @endif
        @endforeach

        {{-- Area Khusus Role Quality & Admin (RBAC Design System §8) --}}
        @if ($isQuality || $isAdmin)
            <div class="pt-5 mt-5 border-t border-neutral-200">
                <div class="hidden desktop:block px-3 mb-2">
                    <span class="text-[11px] font-sans font-medium text-neutral-500 uppercase tracking-wider">
                        {{ $isAdmin ? 'Administrasi' : 'Validasi Kualitas' }}
                    </span>
                </div>

                @if ($isQuality || $isAdmin)
                    <a href="{{ route('management.learning-categories') }}"
                       title="Kelola Materi Learning"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-md font-sans text-sm transition-colors duration-150 text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50 tablet:justify-center desktop:justify-start">
                        <div class="shrink-0">
                            @include('components.layout.nav-icon', ['name' => 'folder-cog'])
                        </div>
                        <span class="hidden desktop:inline truncate">
                            Kategori Learning
                        </span>
                    </a>
                @endif

                @if ($isAdmin)
                    <a href="/admin"
                       title="Panel Admin Filament"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-md font-sans text-sm transition-colors duration-150 text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50 tablet:justify-center desktop:justify-between group">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 text-brand">
                                @include('components.layout.nav-icon', ['name' => 'cog'])
                            </div>
                            <span class="hidden desktop:inline truncate font-medium text-neutral-900">
                                Master Data
                            </span>
                        </div>
                        <span class="hidden desktop:inline-flex">
                            <x-ui.badge status="reviewed" class="text-[10px] px-1 py-0">Filament</x-ui.badge>
                        </span>
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Footer Info User Singkat --}}
    <div class="p-3 border-t border-neutral-200 bg-neutral-50/50 tablet:hidden desktop:block">
        <div class="flex items-center justify-between text-xs text-neutral-500">
            <span class="font-sans">Role:</span>
            <x-ui.badge status="{{ $isAdmin ? 'closed' : ($isSupervisor ? 'reviewed' : 'neutral') }}">
                {{ ucfirst($primaryRole) }}
            </x-ui.badge>
        </div>
    </div>
</aside>

{{-- =========================================================================
     2. MOBILE DRAWER (Off-canvas Slide-over Menu)
     - Aktif di Mobile (< 820px / 375px) via Alpine.js `mobileMenuOpen`
     ========================================================================= --}}
<div x-show="mobileMenuOpen"
     x-cloak
     class="tablet:hidden fixed inset-0 z-50 flex"
     role="dialog"
     aria-modal="true">
    
    {{-- Backdrop Overlay --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileMenuOpen = false"
         class="fixed inset-0 bg-neutral-900/40 backdrop-blur-sm"></div>

    {{-- Drawer Panel --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-in-out duration-200 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative max-w-xs w-full bg-white h-full flex flex-col z-10 border-r border-neutral-200 shadow-lg">
        
        {{-- Drawer Header --}}
        <div class="h-16 px-5 border-b border-neutral-200 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-md bg-white border border-neutral-200 p-1 flex items-center justify-center shrink-0">
                    <img src="{{ asset('images/cps-logo.png') }}" alt="PT. Catur Pilar Sejahtera" class="w-full h-full object-contain" />
                </div>
                <div>
                    <span class="font-sans font-semibold text-neutral-900 text-sm">CPS ERA</span>
                    <p class="text-[10px] text-neutral-500 leading-none mt-0.5">PT Catur Pilar Sejahtera</p>
                </div>
            </div>

            <button @click="mobileMenuOpen = false"
                    type="button"
                    class="p-2 text-neutral-500 hover:text-neutral-900 rounded-md focus:outline-none"
                    aria-label="Tutup menu">
                @include('components.layout.nav-icon', ['name' => 'x-mark', 'class' => 'w-5 h-5'])
            </button>
        </div>

        {{-- Drawer Menu Items --}}
        <div class="flex-1 overflow-y-auto p-4 space-y-1">
            <div class="px-2 mb-2">
                <span class="text-[11px] font-sans font-medium text-neutral-500 uppercase tracking-wider">
                    Navigasi Utama
                </span>
            </div>

            @foreach ($navItems as $item)
                @if ($roles->intersect($item['roles'])->isNotEmpty())
                    <a href="{{ $item['url'] }}"
                       @click="mobileMenuOpen = false"
                       class="flex items-center justify-between px-3 py-2.5 rounded-md font-sans text-sm transition-colors duration-150 {{ $item['active'] ? 'bg-brand-tint text-brand-dark font-medium' : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50' }}">
                        <div class="flex items-center gap-3">
                            <div class="shrink-0 {{ $item['active'] ? 'text-brand' : 'text-neutral-500' }}">
                                @include('components.layout.nav-icon', ['name' => $item['icon']])
                            </div>
                            <span>{{ $item['title'] }}</span>
                        </div>
                        @if (!empty($item['badge']))
                            <x-ui.badge status="brand" class="text-[10px] px-1.5 py-0">
                                {{ $item['badge'] }}
                            </x-ui.badge>
                        @endif
                    </a>
                @endif
            @endforeach

            {{-- Role-based drawer sections --}}
            @if ($isQuality || $isAdmin)
                <div class="pt-4 mt-4 border-t border-neutral-200">
                    <div class="px-2 mb-2">
                        <span class="text-[11px] font-sans font-medium text-neutral-500 uppercase tracking-wider">
                            {{ $isAdmin ? 'Administrasi' : 'Validasi Kualitas' }}
                        </span>
                    </div>

                    @if ($isQuality || $isAdmin)
                        <a href="{{ route('management.learning-categories') }}"
                           @click="mobileMenuOpen = false"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-md font-sans text-sm text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50">
                            @include('components.layout.nav-icon', ['name' => 'folder-cog'])
                            <span>Kategori Learning</span>
                        </a>
                    @endif

                    @if ($isAdmin)
                        <a href="/admin"
                           class="flex items-center justify-between px-3 py-2.5 rounded-md font-sans text-sm text-neutral-900 hover:bg-neutral-50 font-medium">
                            <div class="flex items-center gap-3">
                                <span class="text-brand">@include('components.layout.nav-icon', ['name' => 'cog'])</span>
                                <span>Master Data (Filament)</span>
                            </div>
                            <x-ui.badge status="reviewed" class="text-[10px] px-1 py-0">Admin</x-ui.badge>
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Drawer User Identity --}}
        @if ($user)
            <div class="p-4 border-t border-neutral-200 bg-neutral-50 flex items-center justify-between">
                <div>
                    <p class="font-sans font-medium text-sm text-neutral-900">{{ $user->name }}</p>
                    <p class="font-mono text-xs text-neutral-500">{{ $user->employee_id ?? 'CPS-00124' }}</p>
                </div>
                <x-ui.badge status="{{ $isAdmin ? 'closed' : ($isSupervisor ? 'reviewed' : 'neutral') }}">
                    {{ ucfirst($primaryRole) }}
                </x-ui.badge>
            </div>
        @endif
    </div>
</div>
