<?php

namespace App\Filament\Pages;

use App\Models\GoogleDriveToken;
use App\Services\GoogleDriveService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Halaman status & penghubungan akun Google Drive tempat berkas CAPA
 * dan dokumen Knowledge Repository diunggah.
 */
class GoogleDrive extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowUp;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan & Master Data';

    protected static ?string $navigationLabel = 'Google Drive';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Koneksi Google Drive';

    protected string $view = 'filament.pages.google-drive';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $connection = GoogleDriveToken::active();

        return [
            'connection' => $connection,
            'scope' => GoogleDriveService::SCOPE,
            'folderBa' => config('services.google_drive.folder_id_ba'),
            'folderKnowledge' => config('services.google_drive.folder_id_knowledge'),
            'redirectUri' => config('services.google_drive.redirect_uri'),
            'clientConfigured' => filled(config('services.google_drive.client_id'))
                && filled(config('services.google_drive.client_secret')),
        ];
    }
}
