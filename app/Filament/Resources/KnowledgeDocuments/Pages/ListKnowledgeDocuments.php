<?php

namespace App\Filament\Resources\KnowledgeDocuments\Pages;

use App\Filament\Resources\KnowledgeDocuments\KnowledgeDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListKnowledgeDocuments extends ListRecords
{
    protected static string $resource = KnowledgeDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(fn () => static::getResource()::getEloquentQuery()->count()),
            'lesson_learned' => Tab::make('Lesson Learned BA')
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('type', 'lesson_learned')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'lesson_learned')),
            'sop_uu' => Tab::make('Dokumen SOP/UU')
                ->badge(fn () => static::getResource()::getEloquentQuery()->where('type', '!=', 'lesson_learned')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', '!=', 'lesson_learned')),
        ];
    }
}
