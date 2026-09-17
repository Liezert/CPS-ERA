<?php

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateLearningMaterial extends CreateRecord
{
    protected static string $resource = LearningMaterialResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        // Bersihkan field virtual post-test agar tidak dimasukkan ke tabel learning_materials
        unset(
            $data['has_post_test'],
            $data['post_test_title'],
            $data['post_test_points'],
            $data['post_test_description'],
            $data['post_test_questions']
        );

        return $data;
    }

    /**
     * Eksekusi hook setelah pembuatan materi untuk membuat Post-Test jika opsi diaktifkan.
     */
    protected function afterCreate(): void
    {
        $formData = $this->form->getRawState();
        $hasPostTest = (bool) ($formData['has_post_test'] ?? false);

        if (! $hasPostTest) {
            return;
        }

        $quizTitle = ! empty($formData['post_test_title'])
            ? $formData['post_test_title']
            : ('Post-Test: '.$this->record->title);

        $quiz = Quiz::create([
            'id' => (string) Str::uuid(),
            'title' => $quizTitle,
            'type' => 'post_test',
            'related_type' => 'learning_material',
            'related_id' => $this->record->id,
            'points_reward' => (int) ($formData['post_test_points'] ?? 20),
            'description' => $formData['post_test_description'] ?? null,
        ]);

        $questions = $formData['post_test_questions'] ?? [];
        foreach ($questions as $qIndex => $qData) {
            if (empty($qData['question_text'])) {
                continue;
            }

            $question = QuizQuestion::create([
                'id' => (string) Str::uuid(),
                'quiz_id' => $quiz->id,
                'question_text' => $qData['question_text'],
                'order_index' => (int) ($qData['order_index'] ?? ($qIndex + 1)),
                'allow_multiple_answers' => (bool) ($qData['allow_multiple_answers'] ?? false),
            ]);

            $options = $qData['options'] ?? [];
            foreach ($options as $optData) {
                if (empty($optData['option_text'])) {
                    continue;
                }

                QuizOption::create([
                    'id' => (string) Str::uuid(),
                    'quiz_question_id' => $question->id,
                    'option_text' => $optData['option_text'],
                    'is_correct' => (bool) ($optData['is_correct'] ?? false),
                ]);
            }
        }
    }
}
