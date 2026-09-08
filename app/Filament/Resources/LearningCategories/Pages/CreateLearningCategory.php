<?php

namespace App\Filament\Resources\LearningCategories\Pages;

use App\Filament\Resources\LearningCategories\LearningCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLearningCategory extends CreateRecord
{
    protected static string $resource = LearningCategoryResource::class;

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
