<?php

namespace App\Filament\Resources\UserKpiYearlies\Tables;

use App\Models\UserKpiYearly;
use App\Services\KpiContributionCalculator;
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
                // Progres dihitung dari attempt periode KPI aktif (counter materials_completed_count tidak dipakai lagi).
                // ponytail: 1 query per baris; cukup untuk tabel berpaginasi, pindah ke subquery bila lambat.
                TextColumn::make('kpi_progress')
                    ->label('Progres Materi (Periode Aktif)')
                    ->state(function (UserKpiYearly $record): string {
                        $kpi = app(KpiContributionCalculator::class)->calculate($record->user);

                        return "{$kpi['summary']} ({$kpi['percentage']}%)";
                    })
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
