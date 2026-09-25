<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\Concerns\ShowsTemporaryPassword;
use App\Filament\Resources\Users\UserResource;
use App\Services\EmployeeAccountService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListUsers extends ListRecords
{
    use ShowsTemporaryPassword;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCsvTemplate')
                ->label('Template CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(fn (): StreamedResponse => response()->streamDownload(function (): void {
                    echo "Nama,Email,NIK,Nama Divisi,Jabatan,Role\n";
                    echo "Budi Santoso,budi.santoso@caturpilar.com,CPS-01001,Produksi,Operator Mesin,employee\n";
                }, 'template-import-karyawan.csv', ['Content-Type' => 'text/csv'])),

            Action::make('importCsv')
                ->label('Import Karyawan via CSV')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('Import Karyawan via CSV')
                ->modalDescription('Kolom wajib: Nama, Email, NIK, Nama Divisi, Jabatan, Role (employee / supervisor / quality / admin). Kalau ada satu baris tidak valid, tidak ada akun yang dibuat. Setiap akun baru mendapat kata sandi sementara acak yang wajib diganti saat login pertama; setelah import, berkas berisi kata sandi tiap akun otomatis diunduh.')
                ->schema([
                    FileUpload::make('file')
                        ->label('File CSV')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                        ->required(),
                ])
                ->action(function (array $data, Action $action): StreamedResponse {
                    $disk = Storage::disk('local');

                    try {
                        $result = app(EmployeeAccountService::class)->importCsv($disk->path($data['file']));
                    } finally {
                        // Data karyawan tidak disimpan setelah import selesai.
                        $disk->delete($data['file']);
                    }

                    if ($result['errors'] !== []) {
                        Notification::make()
                            ->title('Import dibatalkan, tidak ada akun yang dibuat')
                            ->body(implode("\n", array_slice($result['errors'], 0, 10))
                                .(count($result['errors']) > 10 ? "\n…dan ".(count($result['errors']) - 10).' kesalahan lain.' : ''))
                            ->danger()
                            ->persistent()
                            ->send();

                        $action->halt();
                    }

                    Notification::make()
                        ->title("{$result['created']} akun karyawan berhasil dibuat")
                        ->body('Berkas berisi kata sandi sementara tiap akun sedang diunduh. Serahkan ke masing-masing karyawan, lalu hapus berkasnya.')
                        ->success()
                        ->persistent()
                        ->send();

                    // Plaintext hanya ada di respons unduhan ini: tidak disimpan di disk, DB, log, atau notifikasi.
                    return response()->streamDownload(function () use ($result): void {
                        $output = fopen('php://output', 'w');
                        fputcsv($output, ['Nama', 'Email', 'NIK', 'Kata Sandi Sementara'], ',', '"', '');

                        foreach ($result['credentials'] as $credential) {
                            fputcsv($output, [$credential['name'], $credential['email'], $credential['employee_id'], $credential['password']], ',', '"', '');
                        }

                        fclose($output);
                    }, 'kata-sandi-sementara-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
                }),

            CreateAction::make()->label('Tambah Karyawan'),
        ];
    }
}
