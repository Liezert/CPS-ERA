<?php

namespace App\Filament\Resources\BaIncidents;

use App\Enums\BaIncidentStatus;
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
use DomainException;
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

    /**
     * Approve per tahap: Supervisor meneruskan ke HR (catatan lapangan opsional), HR menyetujui
     * final dengan evaluasi formal. Tahap ditentukan status laporan; otorisasinya di policy.
     */
    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label(fn (BaIncident $record): string => static::isHrStage($record) ? 'Setujui Final (HR)' : 'Setujui & Teruskan ke HR')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->visible(fn (BaIncident $record): bool => static::canReviewCurrentStage($record))
            ->requiresConfirmation()
            ->modalHeading(fn (BaIncident $record): string => static::isHrStage($record) ? 'Verifikasi & Setujui Final Laporan BA' : 'Setujui Laporan BA (Tahap Supervisor)')
            ->modalDescription(fn (BaIncident $record): string => static::isHrStage($record)
                ? trim(static::supervisorNoteSummary($record).' Poin pelapor akan otomatis diproses, dan hasil laporan akan terbit di Learning setelah post-test dibuat.')
                : 'Laporan akan diteruskan ke tim HR untuk review final.')
            ->schema(fn (BaIncident $record): array => static::isHrStage($record) ? [
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
            ] : [
                Textarea::make('catatan')
                    ->label('Catatan Lapangan untuk HR (opsional)')
                    ->placeholder('Temuan saat verifikasi di lapangan yang perlu diketahui tim HR...')
                    ->maxLength(1000),
            ])
            ->action(function (BaIncident $record, array $data, Action $action): void {
                $service = app(BaIncidentService::class);
                $isHrStage = static::isHrStage($record);

                try {
                    $isHrStage
                        ? $service->approve($record, auth()->user(), $data)
                        : $service->approveAsSupervisor($record, auth()->user(), $data['catatan'] ?? null);
                } catch (DomainException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()
                    ->title($isHrStage ? 'BA disetujui final & terverifikasi' : 'BA disetujui & diteruskan ke tim HR')
                    ->success()
                    ->send();
            });
    }

    /**
     * Reject per tahap: Supervisor meminta revisi ke pelapor, HR menolak permanen (final).
     */
    public static function rejectAction(): Action
    {
        return Action::make('reject')
            ->label(fn (BaIncident $record): string => static::isHrStage($record) ? 'Tolak Permanen' : 'Tolak / Minta Revisi')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->visible(fn (BaIncident $record): bool => static::canReviewCurrentStage($record))
            ->requiresConfirmation()
            ->modalHeading(fn (BaIncident $record): string => static::isHrStage($record) ? 'Tolak Permanen Laporan BA' : 'Tolak & Minta Perbaikan Laporan BA')
            ->modalDescription(fn (BaIncident $record): string => static::isHrStage($record)
                ? trim(static::supervisorNoteSummary($record).' Penolakan HR bersifat final: pelapor tidak bisa merevisi, dan video lampiran diarsipkan sebagai draf internal.')
                : 'Laporan dikembalikan ke pelapor untuk direvisi, lalu dikirim ulang ke Supervisor.')
            ->schema([
                Textarea::make('catatan_penolakan')
                    ->label('Catatan Alasan Penolakan / Revisi')
                    ->placeholder('Jelaskan bagian analisa atau tindakan yang perlu dilengkapi pembuat...')
                    ->required(),
            ])
            ->action(function (BaIncident $record, array $data, Action $action): void {
                $isHrStage = static::isHrStage($record);

                try {
                    app(BaIncidentService::class)->reject($record, auth()->user(), $data['catatan_penolakan']);
                } catch (DomainException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()
                    ->title($isHrStage ? 'BA ditolak permanen oleh HR' : 'BA dikembalikan ke pelapor untuk revisi')
                    ->warning()
                    ->send();
            });
    }

    /**
     * Hapus rekaman video arsip dari BA yang ditolak permanen HR.
     */
    public static function deleteArchivedVideoAction(): Action
    {
        return Action::make('delete_archived_video')
            ->label('Hapus Rekaman Video')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->visible(fn (BaIncident $record): bool => (bool) auth()->user()?->can('deleteArchivedVideo', $record))
            ->requiresConfirmation()
            ->modalHeading('Hapus Rekaman Video Arsip')
            ->modalDescription('Berkas video di Google Drive dan datanya akan dihapus permanen. Laporan BA tetap tersimpan.')
            ->action(function (BaIncident $record, Action $action): void {
                try {
                    app(BaIncidentService::class)->deleteArchivedVideo($record, auth()->user());
                } catch (DomainException $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();
                    $action->halt();
                }

                Notification::make()->title('Rekaman video arsip dihapus')->success()->send();
            });
    }

    private static function isHrStage(BaIncident $record): bool
    {
        return $record->status === BaIncidentStatus::PendingHr->value;
    }

    private static function canReviewCurrentStage(BaIncident $record): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('reviewAsSupervisor', $record) || $user?->can('reviewAsHr', $record));
    }

    /**
     * Catatan lapangan Supervisor, supaya terbaca HR sebelum memutuskan.
     */
    private static function supervisorNoteSummary(BaIncident $record): string
    {
        $log = app(BaIncidentService::class)->latestSupervisorNote($record);

        if ($log === null) {
            return '';
        }

        $supervisor = $log->actor?->name ?? 'Supervisor';

        return filled($log->note)
            ? "Catatan Supervisor ({$supervisor}): \"{$log->note}\"."
            : "{$supervisor} menyetujui tanpa catatan lapangan.";
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
