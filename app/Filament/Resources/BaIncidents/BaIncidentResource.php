<?php

namespace App\Filament\Resources\BaIncidents;

use App\Filament\Resources\BaIncidents\Pages\CreateBaIncident;
use App\Filament\Resources\BaIncidents\Pages\EditBaIncident;
use App\Filament\Resources\BaIncidents\Pages\ListBaIncidents;
use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Filament\Resources\BaIncidents\Schemas\BaIncidentForm;
use App\Filament\Resources\BaIncidents\Schemas\BaIncidentInfolist;
use App\Filament\Resources\BaIncidents\Tables\BaIncidentsTable;
use App\Models\BaIncident;
use App\Services\BaIncidentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BaIncidentResource extends Resource
{
    protected static ?string $model = BaIncident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Continuous Improvement';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Laporan CAPA';

    protected static ?string $modelLabel = 'Laporan CAPA';

    protected static ?string $pluralModelLabel = 'Laporan CAPA';

    /**
     * Scope query by role: Admin & Quality see all divisions; Supervisor sees own division.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->hasAnyRole(['admin', 'quality'])) {
            $query->where('division_id', $user->division_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return BaIncidentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BaIncidentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BaIncidentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Setujui & Verifikasi')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (BaIncident $record): bool => in_array($record->status, ['submitted', 'created', 'draft'], true) && auth()->user()->can('review', $record))
            ->requiresConfirmation()
            ->modalHeading('Verifikasi & Setujui Laporan BA')
            ->modalDescription('Reviewer wajib memverifikasi hasil efektivitas tindakan korektif. Materi Lesson Learned dan penambahan poin akan otomatis diproses.')
            ->schema([
                Radio::make('status_verifikasi')
                    ->label('Status Verifikasi Hasil')
                    ->options([
                        'efektif' => 'Diverifikasi Efektif',
                        'tidak_efektif' => 'Tidak Efektif',
                    ])
                    ->default('efektif')
                    ->required()
                    ->live(),
                Textarea::make('bukti_objektif')
                    ->label('Bukti Objektif Efektivitas')
                    ->placeholder('Sebutkan data hasil pengukuran / inspeksi QC...')
                    ->visible(fn ($get) => $get('status_verifikasi') === 'efektif')
                    ->required(fn ($get) => $get('status_verifikasi') === 'efektif'),
                Textarea::make('alasan_tidak_efektif')
                    ->label('Alasan Ketidakefektifan')
                    ->placeholder('Jelaskan parameter yang belum terpenuhi...')
                    ->visible(fn ($get) => $get('status_verifikasi') === 'tidak_efektif')
                    ->required(fn ($get) => $get('status_verifikasi') === 'tidak_efektif'),
            ])
            ->action(function (BaIncident $record, array $data): void {
                app(BaIncidentService::class)->approve($record, auth()->user(), $data);
                Notification::make()->title('BA berhasil disetujui & diverifikasi')->success()->send();
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Tolak / Revisi')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->visible(fn (BaIncident $record): bool => in_array($record->status, ['submitted', 'created', 'draft'], true) && auth()->user()->can('review', $record))
            ->requiresConfirmation()
            ->modalHeading('Tolak & Minta Perbaikan Laporan BA')
            ->schema([
                Textarea::make('catatan_penolakan')
                    ->label('Catatan Alasan Penolakan / Revisi')
                    ->placeholder('Jelaskan bagian analisa atau tindakan yang perlu dilengkapi pembuat...')
                    ->required(),
            ])
            ->action(function (BaIncident $record, array $data): void {
                app(BaIncidentService::class)->reject($record, auth()->user(), $data['catatan_penolakan']);
                Notification::make()->title('BA ditolak dan catatan revisi telah dikirim')->warning()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBaIncidents::route('/'),
            'create' => CreateBaIncident::route('/create'),
            'view' => ViewBaIncident::route('/{record}'),
            'edit' => EditBaIncident::route('/{record}/edit'),
        ];
    }
}
