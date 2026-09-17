<?php

namespace App\Filament\Resources\UserKpiYearlies;

use App\Filament\Resources\UserKpiYearlies\Pages\ListUserKpiYearlies;
use App\Filament\Resources\UserKpiYearlies\Tables\UserKpiYearliesTable;
use App\Models\UserKpiYearly;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserKpiYearlyResource extends Resource
{
    protected static ?string $model = UserKpiYearly::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected static UnitEnum|string|null $navigationGroup = 'Evaluasi & Kinerja';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Rekap Poin CPS ERA';

    protected static ?string $modelLabel = 'Rekap Poin Karyawan';

    protected static ?string $pluralModelLabel = 'Rekap Poin CPS ERA';

    /**
     * Sesuai PRD v2.0 §2.2 & prompt: HANYA role Quality/HRGA dan Admin yang boleh melihat rekap Poin CPS ERA semua karyawan.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('view-kpi-summary') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return UserKpiYearliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserKpiYearlies::route('/'),
        ];
    }
}
