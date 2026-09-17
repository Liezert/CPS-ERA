<?php

namespace App\Filament\Resources\KnowledgeDocuments;

use App\Filament\Resources\KnowledgeDocuments\Pages\CreateKnowledgeDocument;
use App\Filament\Resources\KnowledgeDocuments\Pages\EditKnowledgeDocument;
use App\Filament\Resources\KnowledgeDocuments\Pages\ListKnowledgeDocuments;
use App\Filament\Resources\KnowledgeDocuments\Pages\ViewKnowledgeDocument;
use App\Filament\Resources\KnowledgeDocuments\Schemas\KnowledgeDocumentForm;
use App\Filament\Resources\KnowledgeDocuments\Schemas\KnowledgeDocumentInfolist;
use App\Filament\Resources\KnowledgeDocuments\Tables\KnowledgeDocumentsTable;
use App\Models\KnowledgeDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class KnowledgeDocumentResource extends Resource
{
    protected static ?string $model = KnowledgeDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static UnitEnum|string|null $navigationGroup = 'Repository & Pembelajaran';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Knowledge Repository';

    protected static ?string $modelLabel = 'Materi Pengetahuan';

    protected static ?string $pluralModelLabel = 'Knowledge Repository';

    /**
     * Scope query by role:
     * - Admin, Quality, dan HRGA: Mengakses semua dokumen dari seluruh divisi.
     * - Supervisor divisi lain: Mengakses dokumen di divisinya sendiri dan dokumen umum perusahaan (null division).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && ! $user->hasAnyRole(['admin', 'quality']) && $user->division?->name !== 'HRGA') {
            $query->where(function (Builder $q) use ($user) {
                $q->where('division_id', $user->division_id)
                    ->orWhereNull('division_id');
            });
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage-knowledge-documents') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return KnowledgeDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KnowledgeDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeDocumentsTable::configure($table);
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
            'index' => ListKnowledgeDocuments::route('/'),
            'create' => CreateKnowledgeDocument::route('/create'),
            'view' => ViewKnowledgeDocument::route('/{record}'),
            'edit' => EditKnowledgeDocument::route('/{record}/edit'),
        ];
    }
}
