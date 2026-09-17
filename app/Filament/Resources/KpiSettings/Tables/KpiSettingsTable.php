<?php

namespace App\Filament\Resources\KpiSettings\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class KpiSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('target_video_count')
                    ->label('Target Video')
                    ->sortable()
                    ->suffix(' video')
                    ->weight('bold'),

                TextColumn::make('period_type')
                    ->label('Tipe Periode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'quarterly' => 'Kuartalan (Quarterly)',
                        'all_time' => 'Sepanjang Waktu (All Time)',
                        default => 'Bulanan (Monthly)',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'quarterly' => 'warning',
                        'all_time' => 'info',
                        default => 'success',
                    })
                    ->sortable(),

                TextColumn::make('points_reward')
                    ->label('Poin Reward')
                    ->suffix(' Pts')
                    ->sortable(),

                TextColumn::make('creator.name')
                    ->label('Ditetapkan Oleh')
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Tanggal Pembaruan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
