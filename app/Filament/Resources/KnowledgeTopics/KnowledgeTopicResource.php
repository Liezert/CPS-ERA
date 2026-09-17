<?php

namespace App\Filament\Resources\KnowledgeTopics;

use App\Filament\Resources\KnowledgeTopics\Pages\CreateKnowledgeTopic;
use App\Filament\Resources\KnowledgeTopics\Pages\EditKnowledgeTopic;
use App\Filament\Resources\KnowledgeTopics\Pages\ListKnowledgeTopics;
use App\Filament\Resources\KnowledgeTopics\Schemas\KnowledgeTopicForm;
use App\Filament\Resources\KnowledgeTopics\Tables\KnowledgeTopicsTable;
use App\Models\KnowledgeTopic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class KnowledgeTopicResource extends Resource
{
    protected static ?string $model = KnowledgeTopic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static UnitEnum|string|null $navigationGroup = 'Repository & Pembelajaran';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Topik Pengetahuan';

    protected static ?string $modelLabel = 'Topik Pengetahuan';

    protected static ?string $pluralModelLabel = 'Topik Pengetahuan';

    /**
     * Sesuai PRD v2.0 §2.2 & prompt: Hanya role Admin, Quality, dan HRD/HRGA yang boleh mengelola Topik Knowledge Repository.
     * Route resource ditolak untuk role Employee dan Supervisor.
     */
    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage-knowledge-topics') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage-knowledge-topics') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage-knowledge-topics') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('manage-knowledge-topics') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('manage-knowledge-topics') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return KnowledgeTopicForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeTopicsTable::configure($table);
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
            'index' => ListKnowledgeTopics::route('/'),
            'create' => CreateKnowledgeTopic::route('/create'),
            'edit' => EditKnowledgeTopic::route('/{record}/edit'),
        ];
    }
}
