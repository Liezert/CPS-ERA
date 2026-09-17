<?php

namespace App\Filament\Resources\LearningCategories\Pages;

use App\Filament\Resources\LearningCategories\LearningCategoryResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningCategory extends EditRecord
{
    protected static string $resource = LearningCategoryResource::class;

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
