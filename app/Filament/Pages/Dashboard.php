<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Keputusan owner 2026-09-27: semua role memakai dashboard utama CPS ERA (/dashboard) agar
 * tampilan konsisten. Beranda panel /admin hanya meneruskan ke sana; menu kelola data
 * (resource Filament) tetap bisa dibuka seperti biasa.
 */
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard CPS ERA';

    public function mount(): void
    {
        $this->redirectRoute('dashboard');
    }
}
