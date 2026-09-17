<?php

namespace App\Filament\Resources\KnowledgeDocuments\Pages;

use App\Filament\Resources\KnowledgeDocuments\KnowledgeDocumentResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeDocument extends EditRecord
{
    protected static string $resource = KnowledgeDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
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
            ->visible(fn (): bool => auth()->user()?->can('delete', $this->record) ?? false)
            ->extraAttributes(['class' => 'sm:ms-auto', 'style' => 'margin-inline-start: auto;']);
    }
}
