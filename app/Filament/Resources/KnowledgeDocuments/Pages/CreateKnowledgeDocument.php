<?php

namespace App\Filament\Resources\KnowledgeDocuments\Pages;

use App\Filament\Resources\KnowledgeDocuments\KnowledgeDocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateKnowledgeDocument extends CreateRecord
{
    protected static string $resource = KnowledgeDocumentResource::class;

    /**
     * Set the creator of the knowledge document and enforce lesson_learned restriction.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['type'] ?? '') === 'lesson_learned') {
            throw ValidationException::withMessages([
                'type' => 'Tipe lesson_learned hanya dapat dibuat otomatis oleh sistem melalui review BA.',
            ]);
        }

        $data['created_by'] = auth()->id();

        return $data;
    }
}
