<?php

namespace App\Filament\Resources\LearningMaterials\Pages;

use App\Filament\Resources\LearningMaterials\LearningMaterialResource;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditLearningMaterial extends EditRecord
{
    protected static string $resource = LearningMaterialResource::class;

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

    /**
     * Memuat relasi Post-Test ke dalam form state saat membuka halaman edit.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $postTest = $this->record->postTest()->with('questions.options')->first();

        if ($postTest) {
            $data['has_post_test'] = true;
            $data['post_test_title'] = $postTest->title;
            $data['post_test_points'] = $postTest->points_reward;
            $data['post_test_description'] = $postTest->description;

            $questions = [];
            foreach ($postTest->questions as $question) {
                $options = [];
                foreach ($question->options as $option) {
                    $options[] = [
                        'option_text' => $option->option_text,
                        'is_correct' => (bool) $option->is_correct,
                    ];
                }
                $questions[] = [
                    'question_text' => $question->question_text,
                    'order_index' => $question->order_index,
                    'allow_multiple_answers' => (bool) $question->allow_multiple_answers,
                    'options' => $options,
                ];
            }
            $data['post_test_questions'] = $questions;
        } else {
            $data['has_post_test'] = false;
        }

        return $data;
    }

    /**
     * Bersihkan field virtual sebelum record LearningMaterial disimpan.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
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
     * Sinkronisasi Post-Test setelah penyimpanan record materi.
     */
    protected function afterSave(): void
    {
        $formData = $this->form->getRawState();
        $hasPostTest = (bool) ($formData['has_post_test'] ?? false);
        $postTest = $this->record->postTest()->with('questions.options')->first();

        if (! $hasPostTest) {
            // Jika Post-Test dinonaktifkan, hapus record Post-Test lama jika ada
            if ($postTest) {
                $postTest->delete();
            }

            return;
        }

        $quizTitle = ! empty($formData['post_test_title'])
            ? $formData['post_test_title']
            : ('Post-Test: '.$this->record->title);

        if (! $postTest) {
            $postTest = Quiz::create([
                'id' => (string) Str::uuid(),
                'title' => $quizTitle,
                'type' => 'post_test',
                'related_type' => 'learning_material',
                'related_id' => $this->record->id,
                'points_reward' => (int) ($formData['post_test_points'] ?? 20),
                'description' => $formData['post_test_description'] ?? null,
            ]);
        } else {
            $postTest->update([
                'title' => $quizTitle,
                'points_reward' => (int) ($formData['post_test_points'] ?? 20),
                'description' => $formData['post_test_description'] ?? null,
            ]);

            // Hapus pertanyaan dan opsi lama untuk disinkronkan ulang
            foreach ($postTest->questions as $existingQ) {
                $existingQ->options()->delete();
                $existingQ->delete();
            }
        }

        $questions = $formData['post_test_questions'] ?? [];
        foreach ($questions as $qIndex => $qData) {
            if (empty($qData['question_text'])) {
                continue;
            }

            $question = QuizQuestion::create([
                'id' => (string) Str::uuid(),
                'quiz_id' => $postTest->id,
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
