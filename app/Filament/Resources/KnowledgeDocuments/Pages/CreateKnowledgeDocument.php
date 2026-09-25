<?php

namespace App\Filament\Resources\KnowledgeDocuments\Pages;

use App\Filament\Resources\KnowledgeDocuments\KnowledgeDocumentResource;
use App\Filament\Resources\KnowledgeDocuments\Pages\Concerns\UploadsDocumentToDrive;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeDocument extends CreateRecord
{
    use UploadsDocumentToDrive;

    protected static string $resource = KnowledgeDocumentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $this->moveUploadedFileToDrive($data);
    }
}
