<?php

namespace App\Livewire\Profile;

use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserLearningProgress;
use App\Services\KpiContributionCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public ?string $statusMessage = null;

    public ?string $passwordStatusMessage = null;

    /**
     * Properti Unggah & Rancang Foto Profil
     */
    public $photo;

    public string $avatarMode = 'upload'; // 'upload' atau 'generate'

    public string $selectedBgColor = '#0B7840';

    public string $customInitials = '';

    public ?string $photoStatusMessage = null;

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user) {
            $this->name = $user->name ?? '';
            $this->email = $user->email ?? '';
            $this->customInitials = $user->initials ?? 'CP';
        }
    }

    /**
     * Validasi langsung saat file foto dipilih.
     */
    public function updatedPhoto(): void
    {
        $this->validate([
            'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal adalah 2MB.',
            'photo.mimes' => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
        ]);
    }

    /**
     * Simpan file foto profil yang diunggah pengguna.
     */
    public function saveProfilePhoto(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->validate([
            'photo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'photo.required' => 'Pilih file foto terlebih dahulu.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.max' => 'Ukuran foto maksimal adalah 2MB.',
            'photo.mimes' => 'Format foto harus berupa JPG, JPEG, PNG, atau WEBP.',
        ]);

        // Hapus file lama jika ada
        $this->cleanupExistingAvatar($user->avatar_url);

        // Simpan foto baru ke disk public
        $path = $this->photo->store('avatars', 'public');
        $user->avatar_url = 'storage/'.$path;
        $user->save();

        $this->reset('photo');
        $this->photoStatusMessage = 'Foto profil berhasil diperbarui.';
        session()->flash('status', 'photo-updated');
    }

    /**
     * Rancang dan buat avatar vektor (SVG) kustom berlatar warna pilihan.
     */
    public function generateCustomAvatar(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->validate([
            'selectedBgColor' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'customInitials' => ['required', 'string', 'min:1', 'max:3'],
        ], [
            'selectedBgColor.required' => 'Pilih warna latar belakang avatar.',
            'customInitials.required' => 'Inisial huruf harus diisi.',
            'customInitials.max' => 'Inisial maksimal 3 karakter.',
        ]);

        $initials = strtoupper(trim($this->customInitials));
        $bgColor = $this->selectedBgColor;

        // Generate SVG berkualitas tajam (200x200)
        $svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
    <rect width="200" height="200" rx="24" fill="{$bgColor}" />
    <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#FFFFFF" font-family="'Inter', system-ui, sans-serif" font-weight="700" font-size="76" letter-spacing="-1">
        {$initials}
    </text>
</svg>
SVG;

        $fileName = 'avatars/avatar-'.Str::uuid().'.svg';
        Storage::disk('public')->put($fileName, $svgContent);

        // Hapus foto lama jika ada
        $this->cleanupExistingAvatar($user->avatar_url);

        $user->avatar_url = 'storage/'.$fileName;
        $user->save();

        $this->photoStatusMessage = 'Avatar kustom berhasil dibuat dan diterapkan.';
        session()->flash('status', 'photo-updated');
    }

    /**
     * Hapus foto profil dan kembalikan ke avatar inisial standar.
     */
    public function deleteProfilePhoto(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->cleanupExistingAvatar($user->avatar_url);

        $user->avatar_url = null;
        $user->save();

        $this->reset('photo');
        $this->photoStatusMessage = 'Foto profil berhasil dihapus. Profil kembali menggunakan avatar default.';
        session()->flash('status', 'photo-deleted');
    }

    /**
     * Hapus file avatar lama dari storage public jika tersimpan lokal.
     */
    protected function cleanupExistingAvatar(?string $avatarUrl): void
    {
        if (! $avatarUrl) {
            return;
        }

        if (str_starts_with($avatarUrl, 'storage/avatars/')) {
            $relativePath = str_replace('storage/', '', $avatarUrl);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
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
     * Batalkan pengeditan informasi profil dan kembalikan ke data asli.
     */
    public function cancelProfileEdit(): void
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->name = $user->name;
        $this->email = $user->email;
        $this->resetValidation(['name', 'email']);
        $this->statusMessage = null;
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
    public function render(KpiContributionCalculator $kpiCalculator): View
    {
        /** @var User $user */
        $user = Auth::user()->load('division');

        // 1. Employee ID dengan format baku CPS-00124
        $formattedEmployeeId = $user->employee_id ?? ('CPS-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT));

        // 2. Inisial nama untuk avatar box
        $initials = $user->initials;

        // 3. Akumulasi Poin Ledger point_transactions
        $totalPoints = (int) $user->pointTransactions()->sum('points');

        // 4. Learning Progress rata-rata
        $learningProgress = (int) round(
            UserLearningProgress::where('user_id', $user->id)->avg('progress_percent') ?? 0
        );

        // 5. KPI Contribution (materi Learning per periode, sumber sama dengan Dashboard)
        $kpiData = $kpiCalculator->calculate($user);
        $kpiContribution = $kpiData['percentage'];

        // 6. Grafik Performa Bulanan (6 bulan terakhir)
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
            'kpiContribution' => $kpiContribution,
            'kpiData' => $kpiData,
            'monthlyPerformance' => $monthlyPerformance,
            'maxMonthlyPoints' => $maxMonthlyPoints,
        ])->layout('layouts.app', [
            'title' => 'Profil Pegawai — CPS-ERA',
        ]);
    }
}
