<x-guest-layout>
    <div class="w-full sm:max-w-md">
        <div class="bg-white border border-neutral-200 rounded-lg p-6 sm:p-8">

            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-20 h-20 rounded-xl bg-white border border-neutral-200/80 p-2 shadow-2xs flex items-center justify-center shrink-0 mb-3">
                    <img src="{{ asset('images/cps-logo.png') }}" alt="PT Catur Pilar Sejahtera" class="w-full h-full object-contain" />
                </div>

                <h1 class="font-sans font-semibold text-xl text-neutral-900 tracking-tight">
                    Ganti Kata Sandi
                </h1>

                <p class="font-sans text-xs text-neutral-500 mt-1">
                    Akun Anda dibuat oleh admin dengan kata sandi sementara. Buat kata sandi pribadi untuk melanjutkan.
                </p>
            </div>

            <form method="POST" action="{{ route('password.change.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                @foreach ([
                    ['current_password', 'Kata Sandi Sementara', 'current-password'],
                    ['password', 'Kata Sandi Baru', 'new-password'],
                    ['password_confirmation', 'Ulangi Kata Sandi Baru', 'new-password'],
                ] as [$field, $label, $autocomplete])
                    <div>
                        <label for="{{ $field }}" class="block font-sans text-xs font-medium text-neutral-700 mb-1">
                            {{ $label }}
                        </label>
                        <input id="{{ $field }}"
                               type="password"
                               name="{{ $field }}"
                               required
                               @if ($loop->first) autofocus @endif
                               autocomplete="{{ $autocomplete }}"
                               class="block w-full px-3 py-2 text-sm font-sans text-neutral-900 bg-white border border-neutral-200 rounded-md placeholder:text-neutral-400 focus:outline-none focus:border-brand-dark focus:ring-2 focus:ring-brand-dark/20 transition-colors" />
                        @error($field)
                            <p class="mt-1 text-xs text-red-600 font-sans">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="pt-2">
                    <x-ui.button type="submit" variant="primary" class="w-full">
                        Simpan Kata Sandi Baru
                    </x-ui.button>
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" class="font-sans text-xs text-neutral-500 hover:text-neutral-900 transition-colors">
                    Keluar
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>
