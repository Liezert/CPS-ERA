<?php

namespace App\Filament\Resources\BaIncidents\Tables;

use App\Models\BaIncident;
use App\Services\BaIncidentService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                        'created' => 'warning',
                        'reviewed' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Waktu Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('Waktu Review')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'created' => 'Created',
                        'reviewed' => 'Reviewed',
                        'closed' => 'Closed',
                    ]),
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name'),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->color('info')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (BaIncident $record): bool => $record->isCreated() && auth()->user()->can('review', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Tinjau Laporan BA')
                    ->modalDescription('Dengan meninjau BA ini, status berubah menjadi Reviewed dan dokumen Lesson Learned akan otomatis dibuat.')
                    ->schema([
                        Textarea::make('note')
                            ->label('Catatan Review')
                            ->placeholder('Masukkan catatan hasil peninjauan/FTK (opsional)'),
                    ])
                    ->action(function (BaIncident $record, array $data): void {
                        app(BaIncidentService::class)->review($record, auth()->user(), $data['note'] ?? null);
                        Notification::make()->title('BA berhasil ditinjau')->success()->send();
                    }),
                Action::make('close')
                    ->label('Tutup')
                    ->color('success')
                    ->icon('heroicon-o-lock-closed')
                    ->visible(fn (BaIncident $record): bool => $record->isReviewed() && auth()->user()->can('close', $record))
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan & Tutup BA')
                    ->modalDescription('Apakah Anda yakin ingin menyelesaikan dan menutup kasus BA ini?')
                    ->schema([
                        Textarea::make('note')
                            ->label('Catatan Penutupan')
                            ->placeholder('Masukkan catatan penyelesaian (opsional)'),
                    ])
                    ->action(function (BaIncident $record, array $data): void {
                        app(BaIncidentService::class)->close($record, auth()->user(), $data['note'] ?? null);
                        Notification::make()->title('BA berhasil diselesaikan & ditutup')->success()->send();
                    }),
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (BaIncident $record): bool => $record->isCreated() && auth()->user()->can('update', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->hasRole('admin') ?? false),
                ]),
            ]);
    }
}
