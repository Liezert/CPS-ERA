<?php

namespace App\Filament\Resources\KnowledgeTopics\Pages;

use App\Filament\Resources\KnowledgeTopics\KnowledgeTopicResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeTopic extends CreateRecord
{
    protected static string $resource = KnowledgeTopicResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
