<?php

namespace App\Filament\Resources\BaIncidents\Pages;

use App\Enums\BaIncidentStatus;
use App\Filament\Resources\BaIncidents\BaIncidentResource;
use App\Services\BaIncidentExcelExport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListBaIncidents extends ListRecords
{
    protected static string $resource = BaIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportExcelAction(),
            CreateAction::make(),
        ];
    }

    /**
     * Unduh laporan CAPA (semua status) sebagai Excel, kolom mengikuti formulir CAPA/FTK.
     * Hanya admin: data keluar dari sistem dalam bentuk berkas.
     */
    private function exportExcelAction(): Action
    {
        return Action::make('exportExcel')
            ->label('Ekspor Excel')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false)
            ->modalHeading('Ekspor Laporan CAPA ke Excel')
            ->modalDescription('Data dipilih berdasarkan Tanggal Pengisian laporan. Kolom mengikuti urutan formulir CAPA.')
            ->modalSubmitActionLabel('Unduh Excel')
            ->schema([
                Select::make('rentang')
                    ->label('Rentang waktu')
                    ->options(BaIncidentExcelExport::rangeOptions())
                    ->default('12_bulan')
                    ->required()
                    ->live(),
                DatePicker::make('dari')
                    ->label('Dari tanggal')
                    ->visible(fn (Get $get): bool => $get('rentang') === 'kustom')
                    ->required(fn (Get $get): bool => $get('rentang') === 'kustom'),
                DatePicker::make('sampai')
                    ->label('Sampai tanggal')
                    ->visible(fn (Get $get): bool => $get('rentang') === 'kustom')
                    ->required(fn (Get $get): bool => $get('rentang') === 'kustom')
                    ->afterOrEqual('dari'),
            ])
            ->action(function (array $data, BaIncidentExcelExport $export) {
                [$from, $until] = BaIncidentExcelExport::range($data['rentang'], $data['dari'] ?? null, $data['sampai'] ?? null);
                $query = $export->query(auth()->user(), $from, $until);

                if (! $query->exists()) {
                    Notification::make()->title('Tidak ada laporan CAPA pada rentang waktu ini.')->warning()->send();

                    return null;
                }

                return response()->streamDownload(
                    fn () => $export->write($query, 'php://output'),
                    BaIncidentExcelExport::filename($from, $until),
                    ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                );
            });
    }

    /**
     * Tab antrean kerja di atas query resource. Query dasarnya sendiri tetap tanpa filter status
     * (Supervisor dibatasi divisinya, HR lintas divisi), supaya laporan yang sudah pindah tahap
     * tetap bisa dibuka lewat tab "Semua".
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'pending_supervisor' => $this->statusTab(BaIncidentStatus::PendingSupervisor),
            'pending_hr' => $this->statusTab(BaIncidentStatus::PendingHr),
            'all' => Tab::make('Semua'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return auth()->user()?->hasRole('supervisor') ? 'pending_supervisor' : 'pending_hr';
    }

    private function statusTab(BaIncidentStatus $status): Tab
    {
        return Tab::make($status->getLabel())
            ->badge(fn () => static::getResource()::getEloquentQuery()->where('status', $status->value)->count())
            ->badgeColor($status->getColor())
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status->value));
    }
}
