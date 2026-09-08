<?php

namespace App\Filament\Resources\LearningCategories\Pages;

use App\Filament\Resources\LearningCategories\LearningCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLearningCategories extends ListRecords
{
    protected static string $resource = LearningCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
