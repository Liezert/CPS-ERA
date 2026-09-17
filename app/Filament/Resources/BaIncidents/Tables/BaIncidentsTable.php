<?php

namespace App\Filament\Resources\BaIncidents\Tables;

use App\Models\BaIncident;
use App\Services\BaIncidentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BaIncidentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_ba')
                    ->label('Nomor BA')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'warning',
                        'submitted', 'created' => 'info',
                        'approved', 'reviewed', 'closed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('status_verifikasi')
                    ->label('Verifikasi')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'efektif' => 'success',
                        'tidak_efektif' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : '-')
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Waktu Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reviewed_at')
                    ->label('Waktu Review')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'created' => 'Created (Legacy)',
                        'reviewed' => 'Reviewed (Legacy)',
                        'closed' => 'Closed (Legacy)',
                    ]),
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name'),
            ])
            ->recordActions([
                Action::make('approve')
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
                    }),

                Action::make('reject')
                    ->label('Tolak / Revisi')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (BaIncident $record): bool => in_array($record->status, ['submitted', 'created'], true) && auth()->user()->can('review', $record))
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
                    }),

                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn (BaIncident $record): bool => in_array($record->status, ['draft', 'created', 'rejected'], true) && auth()->user()->can('update', $record)),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
                ]),
            ]);
    }
}
