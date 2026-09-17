<?php

namespace App\Filament\Resources\KnowledgeTopics\Pages;

use App\Filament\Resources\KnowledgeTopics\KnowledgeTopicResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeTopic extends EditRecord
{
    protected static string $resource = KnowledgeTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<Action|ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            $this->getDeleteFormAction(),
        ];
    }

    protected function getDeleteFormAction(): Action
    {
        return DeleteAction::make()
            ->extraAttributes(['class' => 'sm:ms-auto', 'style' => 'margin-inline-start: auto;']);
    }
}
