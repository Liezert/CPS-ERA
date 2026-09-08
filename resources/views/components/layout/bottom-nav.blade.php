@php
    $isDashboard = request()->routeIs('dashboard');
    $isKnowledge = request()->is('knowledge*');
    $isBa = request()->is('ba-incidents*');
    $isLearning = request()->is('learning*');
@endphp

{{-- =========================================================================
     MOBILE BOTTOM NAVIGATION BAR (Design System §4)
     - Aktif di Mobile (< 820px / 375px), tersembunyi di tablet & desktop
     - 5 akses cepat jempol (Dashboard, Knowledge, BA, Learning, Menu Drawer)
     ========================================================================= --}}
<nav class="tablet:hidden fixed bottom-0 inset-x-0 z-40 h-16 bg-white border-t border-neutral-200 flex items-center justify-around px-2"
     aria-label="Navigasi cepat mobile">
    
    {{-- 1. Dashboard --}}
    <a href="{{ route('dashboard') }}"
       class="flex flex-col items-center justify-center min-w-[56px] h-full py-1 {{ $isDashboard ? 'text-brand font-medium' : 'text-neutral-500 hover:text-neutral-900' }} transition-colors">
        @include('components.layout.nav-icon', ['name' => 'home', 'class' => 'w-5 h-5'])
        <span class="text-[10px] font-sans mt-1">Beranda</span>
    </a>

    {{-- 2. Knowledge Repository --}}
    <a href="{{ route('knowledge.index') }}"
       class="flex flex-col items-center justify-center min-w-[56px] h-full py-1 {{ $isKnowledge ? 'text-brand font-medium' : 'text-neutral-500 hover:text-neutral-900' }} transition-colors">
        @include('components.layout.nav-icon', ['name' => 'book', 'class' => 'w-5 h-5'])
        <span class="text-[10px] font-sans mt-1">Knowledge</span>
    </a>

    {{-- 3. BA & Lesson Learned --}}
    <a href="{{ route('ba.index') }}"
       class="flex flex-col items-center justify-center min-w-[56px] h-full py-1 {{ $isBa ? 'text-brand font-medium' : 'text-neutral-500 hover:text-neutral-900' }} transition-colors">
        @include('components.layout.nav-icon', ['name' => 'shield-alert', 'class' => 'w-5 h-5'])
        <span class="text-[10px] font-sans mt-1">BA Insiden</span>
    </a>

    {{-- 4. Learning --}}
    <a href="{{ route('learning.index') }}"
       class="flex flex-col items-center justify-center min-w-[56px] h-full py-1 {{ $isLearning ? 'text-brand font-medium' : 'text-neutral-500 hover:text-neutral-900' }} transition-colors">
        @include('components.layout.nav-icon', ['name' => 'academic', 'class' => 'w-5 h-5'])
        <span class="text-[10px] font-sans mt-1">Learning</span>
    </a>

    {{-- 5. Menu Drawer Trigger --}}
    <button type="button"
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="flex flex-col items-center justify-center min-w-[56px] h-full py-1 text-neutral-500 hover:text-neutral-900 transition-colors focus:outline-none"
            aria-label="Buka menu lengkap">
        @include('components.layout.nav-icon', ['name' => 'bars-3', 'class' => 'w-5 h-5'])
        <span class="text-[10px] font-sans mt-1">Menu</span>
    </button>
</nav>
