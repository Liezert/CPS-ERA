<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    protected static string $resource = QuizResource::class;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        $fillData = [];

        if (request()->filled('related_type')) {
            $fillData['related_type'] = request()->query('related_type');
        }

        if (request()->filled('related_id')) {
            $fillData['related_id'] = request()->query('related_id');
        }

        if (request()->filled('title')) {
            $fillData['title'] = request()->query('title');
        }

        // Kuis yang dibuka untuk materi Learning (termasuk dari tombol Buat Post-Test di review CAPA) adalah post-test.
        if (request()->filled('related_type')) {
            $fillData['type'] = 'post_test';
        }

        $this->form->fill($fillData);

        $this->callHook('afterFill');
    }
}
