<?php

namespace App\Filament\Resources\LearningCategories;

use App\Filament\Resources\LearningCategories\Pages\CreateLearningCategory;
use App\Filament\Resources\LearningCategories\Pages\EditLearningCategory;
use App\Filament\Resources\LearningCategories\Pages\ListLearningCategories;
use App\Filament\Resources\LearningCategories\Schemas\LearningCategoryForm;
use App\Filament\Resources\LearningCategories\Tables\LearningCategoriesTable;
use App\Models\LearningCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LearningCategoryResource extends Resource
{
    protected static ?string $model = LearningCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static UnitEnum|string|null $navigationGroup = 'Repository & Pembelajaran';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Kategori Learning';

    protected static ?string $modelLabel = 'Kategori Learning';

    protected static ?string $pluralModelLabel = 'Kategori Learning';

    /**
     * Sesuai PRD 2.2: Hanya role Quality dan Admin yang boleh mengelola kategori Learning.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'quality']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return LearningCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningCategoriesTable::configure($table);
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
            'index' => ListLearningCategories::route('/'),
            'create' => CreateLearningCategory::route('/create'),
            'edit' => EditLearningCategory::route('/{record}/edit'),
        ];
    }
}
