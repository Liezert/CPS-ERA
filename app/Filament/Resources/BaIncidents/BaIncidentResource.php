<?php

namespace App\Filament\Resources\BaIncidents;

use App\Filament\Resources\BaIncidents\Pages\CreateBaIncident;
use App\Filament\Resources\BaIncidents\Pages\EditBaIncident;
use App\Filament\Resources\BaIncidents\Pages\ListBaIncidents;
use App\Filament\Resources\BaIncidents\Pages\ViewBaIncident;
use App\Filament\Resources\BaIncidents\Schemas\BaIncidentForm;
use App\Filament\Resources\BaIncidents\Schemas\BaIncidentInfolist;
use App\Filament\Resources\BaIncidents\Tables\BaIncidentsTable;
use App\Models\BaIncident;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BaIncidentResource extends Resource
{
    protected static ?string $model = BaIncident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Berita Acara (BA)';

    protected static ?string $modelLabel = 'Berita Acara';

    protected static ?string $pluralModelLabel = 'Berita Acara';

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
