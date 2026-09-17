<?php

namespace App\Filament\Resources\KnowledgeTopics\Pages;

use App\Filament\Resources\KnowledgeTopics\KnowledgeTopicResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeTopics extends ListRecords
{
    protected static string $resource = KnowledgeTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
