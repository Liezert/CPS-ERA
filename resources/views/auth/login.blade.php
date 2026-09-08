<x-guest-layout>
    <div class="w-full sm:max-w-md">
        {{-- Session Status Alert --}}
        @if (session('status'))
            <div class="mb-4 p-3 border border-brand bg-brand-tint text-xs font-sans text-brand-dark rounded-md">
                {{ session('status') }}
            </div>
        @endif

        {{-- Single White Panel (Design System §2, §4, §6) --}}
        <div class="bg-white border border-neutral-200 rounded-lg p-6 sm:p-8">
            
            {{-- Brand Logo Header (Design System §2: Logo di atas putih, clear space 25%) --}}
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-20 h-20 rounded-xl bg-white border border-neutral-200/80 p-2 shadow-2xs flex items-center justify-center shrink-0 mb-3">
                    <img src="{{ asset('images/cps-logo.png') }}" alt="PT Catur Pilar Sejahtera" class="w-full h-full object-contain" />
                </div>
                
                <h1 class="font-sans font-semibold text-xl text-neutral-900 tracking-tight">
                    CPS ERA Hub
                </h1>
                
                <p class="font-sans text-xs text-neutral-500 mt-1">
                    PT Catur Pilar Sejahtera · Masuk ke Sistem
                </p>
            </div>

            {{-- Form Login --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                {{-- Email atau NIK Pegawai --}}
                <div>
                    <label for="email" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                        Email Pegawai atau NIK
                    </label>
                    <input id="email" 
                           type="text" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           autocomplete="username"
                           placeholder="nama@caturpilar.com atau CPS-00124"
                           class="block w-full px-3 py-2 text-sm font-sans text-neutral-900 bg-white border border-neutral-200 rounded-md placeholder:text-neutral-400 focus:outline-none focus:border-brand-dark focus:ring-2 focus:ring-brand-dark/20 transition-colors" />
                    @error('email')
                        <p class="mt-1 text-xs text-red-600 font-sans">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="password" class="block font-sans text-xs font-medium text-neutral-700">
                            Password
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" 
                               class="font-sans text-xs text-neutral-500 hover:text-neutral-900 transition-colors">
                                Lupa password?
                            </a>
                        @endif
                    </div>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           autocomplete="current-password"
                           placeholder="••••••••"
                           class="block w-full px-3 py-2 text-sm font-sans text-neutral-900 bg-white border border-neutral-200 rounded-md placeholder:text-neutral-400 focus:outline-none focus:border-brand-dark focus:ring-2 focus:ring-brand-dark/20 transition-colors" />
                    @error('password')
                        <p class="mt-1 text-xs text-red-600 font-sans">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember Me --}}
                <div class="flex items-center">
                    <input id="remember_me" 
                           type="checkbox" 
                           name="remember" 
                           class="w-4 h-4 rounded border-neutral-300 text-brand focus:ring-brand-dark focus:ring-offset-0 transition-colors">
                    <label for="remember_me" class="ml-2 block font-sans text-xs text-neutral-600 select-none">
                        Ingat sesi saya di perangkat ini
                    </label>
                </div>

                {{-- Submit Button using Stage 2 x-ui.button without arrow --}}
                <div class="pt-2">
                    <x-ui.button type="submit" variant="primary" class="w-full">
                        Masuk ke Sistem
                    </x-ui.button>
                </div>
            </form>

            {{-- Footer Info --}}
            <div class="mt-6 pt-4 border-t border-neutral-200 text-center">
                <p class="font-sans text-[11px] text-neutral-500">
                    Sistem Manajemen Pengetahuan &amp; Berita Acara Internal
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
