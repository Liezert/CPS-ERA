<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Verifikasi RBAC & Hak Akses (CPS ERA)') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                Route: /test-access
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Ringkasan Profil Pengguna -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            {{ $user->name }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->email }} &bull; ID Karyawan: <span class="font-mono font-semibold">{{ $user->employee_id ?? '-' }}</span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @forelse($rbacReport['user']['roles'] as $role)
                            <span class="px-3 py-1 text-xs font-bold uppercase rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                Role: {{ $role }}
                            </span>
                        @empty
                            <span class="px-3 py-1 text-xs font-bold uppercase rounded-md bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                Tidak ada role
                            </span>
                        @endforelse
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">Divisi</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200 text-base">
                            {{ $rbacReport['user']['division'] }}
                        </span>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">Jabatan</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200 text-base">
                            {{ $user->jabatan ?? '-' }}
                        </span>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">Level</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200 text-base">
                            Level {{ $user->level ?? 1 }}
                        </span>
                    </div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                        <span class="text-xs text-gray-500 dark:text-gray-400 block">Total Poin</span>
                        <span class="font-semibold text-gray-800 dark:text-gray-200 text-base">
                            {{ $user->total_points ?? 0 }} Poin
                        </span>
                    </div>
                </div>
            </div>

            <!-- Status Akses Filament Panel -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <span>Akses Filament Admin Panel (/admin)</span>
                </h3>

                @if($rbacReport['filament_access'])
                    <div class="p-4 rounded-lg bg-green-50 border border-green-200 dark:bg-green-950/30 dark:border-green-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center font-bold">✓</span>
                            <div>
                                <p class="text-sm font-semibold text-green-800 dark:text-green-300">
                                    DIIZINKAN MASUK KE FILAMENT ADMIN PANEL
                                </p>
                                <p class="text-xs text-green-600 dark:text-green-400">
                                    User memiliki role yang berwenang (Admin, Supervisor, atau Quality).
                                </p>
                            </div>
                        </div>
                        <a href="{{ url('/admin') }}" target="_blank" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-md shadow-sm transition">
                            Buka /admin &rarr;
                        </a>
                    </div>
                @else
                    <div class="p-4 rounded-lg bg-red-50 border border-red-200 dark:bg-red-950/30 dark:border-red-800 flex items-center gap-3">
                        <span class="flex-shrink-0 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center font-bold">✕</span>
                        <div>
                            <p class="text-sm font-semibold text-red-800 dark:text-red-300">
                                DITOLAK DARI FILAMENT ADMIN PANEL (403 Forbidden)
                            </p>
                            <p class="text-xs text-red-600 dark:text-red-400">
                                Role Employee tidak diizinkan masuk ke /admin. Employee akan menggunakan tampilan Livewire tersendiri.
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Status Evaluasi Gates -->
                <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        Pengecekan Gate Laravel (Gate::allows)
                    </h3>
                    <div class="space-y-2">
                        @foreach($rbacReport['gates'] as $gateName => $allowed)
                            <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
                                <span class="font-mono text-xs text-gray-700 dark:text-gray-300">
                                    Gate::allows('{{ $gateName }}')
                                </span>
                                @if($allowed)
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                        TRUE (Allow)
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                        FALSE (Deny)
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Simulasi DivisionScopedPolicy -->
                <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-1">
                        Simulasi DivisionScopedPolicy
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Pola dasar: membatasi akses data ke divisi yang sama (Admin bypass lintas divisi).
                    </p>

                    <div class="space-y-4">
                        <!-- Skenario 1: Divisi Sendiri -->
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-bold text-gray-800 dark:text-gray-200 mb-2">
                                Kasus A: Record dari Divisi Sendiri (ID: {{ $user->division_id ?? 'N/A' }})
                            </p>
                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="p-2 rounded {{ $rbacReport['policy_simulation']['same_division']['can_view'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800' }}">
                                    view(): {{ $rbacReport['policy_simulation']['same_division']['can_view'] ? 'ALLOW' : 'DENY' }}
                                </div>
                                <div class="p-2 rounded {{ $rbacReport['policy_simulation']['same_division']['can_update'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800' }}">
                                    update(): {{ $rbacReport['policy_simulation']['same_division']['can_update'] ? 'ALLOW' : 'DENY' }}
                                </div>
                                <div class="p-2 rounded {{ $rbacReport['policy_simulation']['same_division']['can_review'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800' }}">
                                    review(): {{ $rbacReport['policy_simulation']['same_division']['can_review'] ? 'ALLOW' : 'DENY' }}
                                </div>
                            </div>
                        </div>

                        <!-- Skenario 2: Divisi Lain -->
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900/40 border border-gray-200 dark:border-gray-700">
                            <p class="text-xs font-bold text-gray-800 dark:text-gray-200 mb-2">
                                Kasus B: Record dari Divisi Lain (ID Luar: {{ $user->division_id ? $user->division_id + 999 : 999 }})
                            </p>
                            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                                <div class="p-2 rounded {{ $rbacReport['policy_simulation']['other_division']['can_view'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950/30 dark:text-red-400' }}">
                                    view(): {{ $rbacReport['policy_simulation']['other_division']['can_view'] ? 'ALLOW' : 'DENY' }}
                                </div>
                                <div class="p-2 rounded {{ $rbacReport['policy_simulation']['other_division']['can_update'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950/30 dark:text-red-400' }}">
                                    update(): {{ $rbacReport['policy_simulation']['other_division']['can_update'] ? 'ALLOW' : 'DENY' }}
                                </div>
                                <div class="p-2 rounded {{ $rbacReport['policy_simulation']['other_division']['can_review'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-950/30 dark:text-red-400' }}">
                                    review(): {{ $rbacReport['policy_simulation']['other_division']['can_review'] ? 'ALLOW' : 'DENY' }}
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-2">
                                *Admin lolos semua. Quality lolos review() lintas divisi. Supervisor ditolak jika divisi berbeda.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Petunjuk Pengujian Antar Akun -->
            <div class="p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-2">
                    Petunjuk Pengujian Login Tiap Role
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
                    Semua akun dummy menggunakan password: <span class="font-mono font-bold text-gray-900 dark:text-gray-200">password</span>. Anda dapat logout dan login dengan masing-masing akun untuk melihat perubahan hak akses:
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs text-left">
                        <thead class="bg-gray-100 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 uppercase">
                            <tr>
                                <th class="py-2 px-3">Role</th>
                                <th class="py-2 px-3">Email</th>
                                <th class="py-2 px-3">Divisi</th>
                                <th class="py-2 px-3">Akses /admin</th>
                                <th class="py-2 px-3">Scope Divisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                            <tr>
                                <td class="py-2 px-3 font-semibold text-purple-600 dark:text-purple-400">Admin</td>
                                <td class="py-2 px-3 font-mono">admin@cps.test</td>
                                <td class="py-2 px-3">IT</td>
                                <td class="py-2 px-3 text-green-600 font-semibold">Ya (Akses Penuh)</td>
                                <td class="py-2 px-3">Semua Divisi (Global Override)</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-semibold text-blue-600 dark:text-blue-400">Supervisor</td>
                                <td class="py-2 px-3 font-mono">supervisor@cps.test</td>
                                <td class="py-2 px-3">Produksi</td>
                                <td class="py-2 px-3 text-green-600 font-semibold">Ya</td>
                                <td class="py-2 px-3">Hanya Divisi Sendiri (Produksi)</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-semibold text-amber-600 dark:text-amber-400">Quality</td>
                                <td class="py-2 px-3 font-mono">quality@cps.test</td>
                                <td class="py-2 px-3">Quality Control</td>
                                <td class="py-2 px-3 text-green-600 font-semibold">Ya</td>
                                <td class="py-2 px-3">Lintas Divisi (Validasi/Review)</td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 font-semibold text-gray-600 dark:text-gray-400">Employee</td>
                                <td class="py-2 px-3 font-mono">employee@cps.test</td>
                                <td class="py-2 px-3">Engineering</td>
                                <td class="py-2 px-3 text-red-600 font-semibold">Tidak (403 Forbidden)</td>
                                <td class="py-2 px-3">Data Sendiri / Divisi Sendiri</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
