<?php

namespace App\Filament\Resources\UserKpiYearlies\Tables;

use App\Models\UserKpiYearly;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserKpiYearliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.employee_id')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('user.division.name')
                    ->label('Divisi')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('period_year')
                    ->label('Tahun')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                ViewColumn::make('poin_cps_era_earned')
                    ->label('Poin CPS ERA (Cap 3)')
                    ->view('filament.tables.columns.progress-bar')
                    ->viewData([
                        'max' => 3,
                        'unit' => 'Poin',
                    ])
                    ->sortable(),
                TextColumn::make('poin_from_ba')
                    ->label('Dari BA')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('poin_from_materi')
                    ->label('Dari Materi')
                    ->badge()
                    ->color('purple')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ViewColumn::make('materials_completed_count')
                    ->label('Progress Bundle (Jalur B)')
                    ->view('filament.tables.columns.progress-bar')
                    ->viewData([
                        'max' => 5,
                        'unit' => 'materi',
                    ])
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('period_year')
                    ->label('Tahun Periode')
                    ->options(function (): array {
                        $years = UserKpiYearly::distinct()->pluck('period_year', 'period_year')->all();
                        if (empty($years)) {
                            $curYear = (int) now()->year;

                            return [$curYear => (string) $curYear];
                        }

                        return $years;
                    }),
                SelectFilter::make('division_id')
                    ->label('Divisi')
                    ->relationship('user.division', 'name'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
