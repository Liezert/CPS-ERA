<?php

namespace App\Livewire\Profile;

use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\LevelCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Index extends Component
{
    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $statusMessage = null;

    public ?string $passwordStatusMessage = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user) {
            $this->name = $user->name ?? '';
            $this->email = $user->email ?? '';
        }
    }

    /**
     * Perbarui informasi nama & email pengguna.
     */
    public function updateProfileInformation(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->statusMessage = 'Informasi profil berhasil diperbarui.';
        session()->flash('status', 'profile-updated');
    }

    /**
     * Perbarui kata sandi akun.
     */
    public function updatePassword(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->passwordStatusMessage = 'Kata sandi akun berhasil diperbarui.';
        session()->flash('status', 'password-updated');
    }

    /**
     * Render halaman Profile dengan identitas, metrik konsisten Dashboard, dan grafik performa 6 bulan.
     */
    public function render(LevelCalculator $levelCalculator): View
    {
        /** @var User $user */
        $user = Auth::user()->load('division');

        // 1. Employee ID dengan format baku CPS-00124
        $formattedEmployeeId = $user->employee_id ?? ('CPS-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT));

        // 2. Inisial nama untuk avatar box
        $initials = 'CP';
        if ($user && $user->name) {
            $parts = explode(' ', trim($user->name));
            $initials = strtoupper(substr($parts[0], 0, 1).(isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
        }

        // 3. Akumulasi Poin Ledger point_transactions
        $totalPoints = (int) $user->pointTransactions()->sum('points');

        // 4. Learning Progress rata-rata
        $learningProgress = (int) round(
            UserLearningProgress::where('user_id', $user->id)->avg('progress_percent') ?? 0
        );

        // 5. Level & Target Level (TODO PRD §5.3 Poin 1)
        $currentLevel = $user->level ?? 2;
        $nextLevel = $currentLevel + 1;
        $levelProgressPercent = 65; // Menunggu formula XP PRD §5.3

        // 6. KPI Contribution (TODO PRD §5.3 Poin 2)
        $kpiContribution = null; // Menunggu formula resmi PRD §5.3

        // 7. Grafik Performa Bulanan (6 bulan terakhir)
        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $transactions = PointTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->get();

        $monthlyPerformance = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $monthKey = $monthDate->format('Y-m');
            $monthLabel = $monthDate->translatedFormat('M Y');

            $monthPoints = (int) $transactions
                ->filter(fn ($tx) => $tx->created_at->format('Y-m') === $monthKey)
                ->sum('points');

            $monthlyPerformance[] = [
                'month' => $monthKey,
                'label' => $monthLabel,
                'points' => $monthPoints,
            ];
        }

        $maxMonthlyPoints = max(collect($monthlyPerformance)->max('points') ?? 0, 100);

        foreach ($monthlyPerformance as &$perf) {
            $perf['barPercent'] = $maxMonthlyPoints > 0
                ? (int) round(($perf['points'] / $maxMonthlyPoints) * 100)
                : 0;
        }
        unset($perf);

        return view('livewire.profile.index', [
            'user' => $user,
            'formattedEmployeeId' => $formattedEmployeeId,
            'initials' => $initials,
            'totalPoints' => $totalPoints,
            'learningProgress' => $learningProgress,
            'currentLevel' => $currentLevel,
            'nextLevel' => $nextLevel,
            'levelProgressPercent' => $levelProgressPercent,
            'kpiContribution' => $kpiContribution,
            'monthlyPerformance' => $monthlyPerformance,
            'maxMonthlyPoints' => $maxMonthlyPoints,
        ])->layout('layouts.app', [
            'title' => 'Profil Pengguna — CPS-ERA',
        ]);
    }
}
