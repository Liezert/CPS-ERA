<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-neutral-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'CPS ERA') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- Fonts: Inter (UI & Heading) & IBM Plex Mono (IDs, BA Codes) per Design System §3 -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|ibm-plex-mono:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-neutral-900 bg-neutral-50 antialiased min-h-full selection:bg-brand-tint selection:text-brand-dark">
        {{-- Root Layout Wrapper with Alpine.js state for mobile drawer & dropdowns --}}
        <div x-data="{ mobileMenuOpen: false }" 
             @keydown.escape.window="mobileMenuOpen = false" 
             class="min-h-screen bg-neutral-50 flex flex-col">
            
            {{-- 1. LEFT SIDEBAR (Desktop w-64, Tablet w-20, Mobile Drawer) --}}
            <x-layout.sidebar />

            {{-- 2. MAIN CONTENT AREA (Offset by sidebar on tablet & desktop) --}}
            <div class="min-h-screen flex flex-col tablet:pl-20 desktop:pl-64 transition-[padding] duration-200">
                
                {{-- Topbar Header (Sticky, with Notification & Profile Dropdowns) --}}
                <x-layout.header />

                {{-- Optional Sub-header Page Heading Slot --}}
                @isset($header)
                    <div class="bg-white border-b border-neutral-200">
                        <div class="max-w-7xl mx-auto py-4 px-4 tablet:px-6 desktop:px-8">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                {{-- Page Content Slot (Padding bottom 20 for mobile bottom nav, tablet/desktop 8) --}}
                <main class="flex-1 pb-20 tablet:pb-8">
                    <div class="max-w-7xl mx-auto px-4 tablet:px-6 desktop:px-8 py-6">
                        {{ $slot }}
                    </div>
                </main>
            </div>

            {{-- 3. MOBILE BOTTOM NAVIGATION (Fixed bottom, hidden on tablet & desktop) --}}
            <x-layout.bottom-nav />
        </div>
    </body>
</html>
