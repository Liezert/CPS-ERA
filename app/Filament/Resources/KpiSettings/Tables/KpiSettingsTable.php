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
                TextColumn::make('period_start')
                    ->label('Mulai Periode')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('period_end')
                    ->label('Akhir Periode')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('target_materials')
                    ->label('Target Materi')
                    ->suffix(' materi')
                    ->weight('bold')
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
