<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Actions\ResetPasswordAction;
use App\Models\PointTransaction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('employee_id')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('must_change_password')
                    ->label('Kata Sandi')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Sementara' : 'Pribadi')
                    ->color(fn (bool $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('xp')
                    ->label('XP Saat Ini')
                    ->numeric()
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->relationship('division', 'name'),
                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        1 => 'Level 1',
                        2 => 'Level 2',
                        3 => 'Level 3',
                        4 => 'Level 4',
                        5 => 'Level 5',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                ResetPasswordAction::make(),
                Action::make('adjustXp')
                    ->label('Koreksi XP')
                    ->icon(Heroicon::OutlinedAdjustmentsVertical)
                    ->color('warning')
                    ->visible(fn (): bool => auth()->user()?->can('adjust-xp-manual') ?? false)
                    ->modalHeading('Koreksi XP Manual (Buku Besar)')
                    ->modalDescription('Koreksi XP akan dicatat ke buku besar point_transactions (source_type=admin_adjustment). Kolom users.xp akan diperbarui otomatis melalui ledger observer tanpa edit langsung.')
                    ->form([
                        TextInput::make('points')
                            ->label('Nilai Koreksi XP (+/-)')
                            ->numeric()
                            ->required()
                            ->helperText('Gunakan angka positif untuk penambahan (contoh: 100), atau angka negatif untuk pengurangan (contoh: -50).'),
                        Textarea::make('description')
                            ->label('Alasan / Catatan Koreksi')
                            ->required()
                            ->rows(3)
                            ->placeholder('Contoh: Koreksi kelebihan klaim poin misi...'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $points = (int) $data['points'];

                        // PRD v2.0 §2.2 & prompt: WAJIB selalu insert ke point_transactions,
                        // JANGAN pernah mengedit kolom users.xp secara langsung!
                        PointTransaction::create([
                            'user_id' => $record->id,
                            'ledger_type' => PointTransaction::LEDGER_XP,
                            'points' => $points,
                            'source_type' => 'admin_adjustment',
                            'description' => trim((string) $data['description']),
                            'created_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Koreksi XP Berhasil Dicatat')
                            ->body("Transaksi koreksi {$points} XP untuk {$record->name} berhasil dicatat ke buku besar.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
