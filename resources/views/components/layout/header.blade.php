@props([
    'title' => null,
])

@php
    $user = auth()->user();
    $roles = $user ? $user->getRoleNames() : collect();
    $primaryRole = $roles->first() ?? 'employee';
    $isAdmin = $user?->hasRole('admin') ?? false;
    $isQuality = $user?->hasRole('quality') ?? false;
    $isSupervisor = $user?->hasRole('supervisor') ?? false;

    // Inisial nama user untuk avatar
    $initials = 'CP';
    if ($user && $user->name) {
        $parts = explode(' ', trim($user->name));
        $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
    }
@endphp

<header class="sticky top-0 z-20 h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-4 tablet:px-6 desktop:px-8">
    {{-- Left Area: Hamburger (Mobile) + Page Title / Context --}}
    <div class="flex items-center gap-3">
        {{-- Mobile Drawer Trigger --}}
        <button type="button"
                @click="mobileMenuOpen = true"
                class="tablet:hidden p-2 -ml-2 text-neutral-500 hover:text-neutral-900 rounded-md focus:outline-none focus:ring-2 focus:ring-brand-dark"
                aria-label="Buka navigasi menu">
            @include('components.layout.nav-icon', ['name' => 'bars-3', 'class' => 'w-6 h-6'])
        </button>

        {{-- Page / Brand Context --}}
        <div>
            @if ($title)
                <h1 class="font-sans font-semibold text-lg text-neutral-900 leading-tight">
                    {{ $title }}
                </h1>
            @else
                <div class="flex items-center gap-2">
                    <span class="font-sans font-semibold text-base text-neutral-900 leading-tight">
                        CPS ERA
                    </span>
                    <span class="hidden sm:inline-block text-neutral-300">/</span>
                    <span class="hidden sm:inline-block text-xs font-sans text-neutral-500">
                        {{ $user?->division ? $user->division->name : 'Corporate Knowledge Hub' }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Right Area: User Division Badge + Notification Bell + Profile Dropdown --}}
    <div class="flex items-center gap-2 sm:gap-4">
        {{-- Divisi Tag (Desktop only) --}}
        @if ($user?->division)
            <div class="hidden desktop:flex items-center gap-1.5 px-2.5 py-1 border border-neutral-200 rounded-badge bg-neutral-50 text-neutral-700 text-xs font-sans">
                <span class="text-neutral-500">Divisi:</span>
                <span class="font-medium text-neutral-900">{{ $user->division->name }}</span>
            </div>
        @endif

        {{-- 1. NOTIFIKASI BELL DROPDOWN (Design System §5 & Livewire Component) --}}
        <livewire:notification.dropdown />

        {{-- 2. PROFILE MENU DROPDOWN --}}
        <div class="relative" x-data="{ profileDropdownOpen: false }">
            <button type="button"
                    @click="profileDropdownOpen = !profileDropdownOpen"
                    @click.outside="profileDropdownOpen = false"
                    class="flex items-center gap-2.5 p-1 rounded-md hover:bg-neutral-50 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-dark"
                    aria-label="Menu pengguna"
                    :aria-expanded="profileDropdownOpen.toString()">
                
                {{-- Avatar User (Initials) --}}
                <div class="w-8 h-8 rounded-md bg-brand-tint border border-brand/20 text-brand-dark font-sans font-semibold text-xs flex items-center justify-center shrink-0">
                    {{ $initials }}
                </div>

                {{-- User Info (Desktop only) --}}
                <div class="hidden desktop:block text-left">
                    <div class="font-sans font-medium text-xs text-neutral-900 leading-tight max-w-[120px] truncate">
                        {{ $user?->name ?? 'User CPS' }}
                    </div>
                    <div class="font-mono text-[11px] text-neutral-500 leading-tight mt-0.5">
                        {{ $user?->employee_id ?? 'CPS-00124' }}
                    </div>
                </div>

                {{-- Chevron icon --}}
                <div class="hidden desktop:block text-neutral-400">
                    @include('components.layout.nav-icon', ['name' => 'chevron-down', 'class' => 'w-3.5 h-3.5'])
                </div>
            </button>

            {{-- Dropdown Panel Profil --}}
            <div x-show="profileDropdownOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150 transform"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-100 transform"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute right-0 mt-2 w-64 bg-white border border-neutral-200 rounded-lg shadow-sm z-50 overflow-hidden divide-y divide-neutral-200">
                
                {{-- User Header Details --}}
                <div class="p-4 bg-neutral-50/50">
                    <p class="font-sans font-medium text-sm text-neutral-900 truncate">
                        {{ $user?->name ?? 'Operator CPS' }}
                    </p>
                    <p class="font-sans text-xs text-neutral-500 truncate mt-0.5">
                        {{ $user?->email ?? 'operator@caturpilar.com' }}
                    </p>
                    
                    <div class="mt-2.5 flex items-center justify-between">
                        <span class="font-mono text-xs text-neutral-700 bg-white border border-neutral-200 px-1.5 py-0.5 rounded-badge">
                            {{ $user?->employee_id ?? 'CPS-00124' }}
                        </span>
                        <x-ui.badge status="{{ $isAdmin ? 'closed' : ($isSupervisor ? 'reviewed' : 'neutral') }}">
                            {{ ucfirst($primaryRole) }}
                        </x-ui.badge>
                    </div>
                </div>

                {{-- Links --}}
                <div class="py-1">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-2.5 px-4 py-2 font-sans text-xs text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 transition-colors">
                        @include('components.layout.nav-icon', ['name' => 'user', 'class' => 'w-4 h-4 text-neutral-400'])
                        <span>Profil &amp; Pengaturan Akun</span>
                    </a>

                    @if ($isAdmin)
                        <a href="/admin"
                           class="flex items-center justify-between px-4 py-2 font-sans text-xs text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 transition-colors">
                            <div class="flex items-center gap-2.5">
                                @include('components.layout.nav-icon', ['name' => 'cog', 'class' => 'w-4 h-4 text-brand'])
                                <span class="font-medium text-neutral-900">Panel Admin (Filament)</span>
                            </div>
                            <x-ui.badge status="reviewed" class="text-[9px] px-1 py-0">Admin</x-ui.badge>
                        </a>
                    @endif

                    @if ($isQuality || $isAdmin)
                        <a href="{{ route('management.learning-categories') }}"
                           class="flex items-center gap-2.5 px-4 py-2 font-sans text-xs text-neutral-700 hover:bg-neutral-50 hover:text-neutral-900 transition-colors">
                            @include('components.layout.nav-icon', ['name' => 'folder-cog', 'class' => 'w-4 h-4 text-neutral-400'])
                            <span>Kategori Learning</span>
                        </a>
                    @endif
                </div>

                {{-- Logout Button --}}
                <div class="p-1">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="w-full flex items-center gap-2.5 px-4 py-2 font-sans text-xs text-neutral-700 hover:bg-neutral-50 hover:text-brand-dark rounded-md transition-colors text-left">
                            @include('components.layout.nav-icon', ['name' => 'arrow-right-on-rectangle', 'class' => 'w-4 h-4 text-neutral-400'])
                            <span>Keluar dari Sistem</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
