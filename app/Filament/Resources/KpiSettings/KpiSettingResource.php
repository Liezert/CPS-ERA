<?php

namespace App\Filament\Resources\KpiSettings;

use App\Filament\Resources\KpiSettings\Pages\CreateKpiSetting;
use App\Filament\Resources\KpiSettings\Pages\EditKpiSetting;
use App\Filament\Resources\KpiSettings\Pages\ListKpiSettings;
use App\Filament\Resources\KpiSettings\Schemas\KpiSettingForm;
use App\Filament\Resources\KpiSettings\Tables\KpiSettingsTable;
use App\Models\KpiSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KpiSettingResource extends Resource
{
    protected static ?string $model = KpiSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan & Master Data';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Target KPI Materi';

    protected static ?string $modelLabel = 'Target KPI Materi';

    protected static ?string $pluralModelLabel = 'Target KPI Materi';

    /**
     * Hanya role Admin yang boleh mengelola konfigurasi Target KPI.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return KpiSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KpiSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiSettings::route('/'),
            'create' => CreateKpiSetting::route('/create'),
            'edit' => EditKpiSetting::route('/{record}/edit'),
        ];
    }
}
