<?php

namespace App\Livewire\Learning;

use App\Models\LearningMaterial;
use App\Models\UserLearningProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Detail Pembelajaran - CPS ERA')]
class Show extends Component
{
    public LearningMaterial $material;

    public ?UserLearningProgress $progress = null;

    public int $progressPercent = 0;

    public function mount(LearningMaterial|string $material): void
    {
        if (is_string($material)) {
            $this->material = LearningMaterial::with(['category', 'creator', 'postTest'])
                ->where('id', $material)
                ->firstOrFail();
        } else {
            $this->material = $material->loadMissing(['category', 'creator', 'postTest']);
        }

        // Materi belum terbit (draft/candidate) hanya untuk pengelolanya.
        abort_unless($this->material->status === 'published' || (Auth::user()?->can('update', $this->material) ?? false), 404);

        $userId = Auth::id();
        if ($userId) {
            // Ambil atau inisialisasi record progress belajar pengguna (DoD #1)
            $this->progress = UserLearningProgress::firstOrCreate(
                [
                    'user_id' => $userId,
                    'learning_material_id' => $this->material->id,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'progress_percent' => 0,
                    'completed_at' => null,
                ]
            );

            $this->progressPercent = (int) $this->progress->progress_percent;
        }
    }

    /**
     * Update progress belajar pengguna per materi ke database (DoD #1).
     * Kolom dan tipe data dicocokkan ketat ke ERD (user_id, learning_material_id, progress_percent, completed_at).
     */
    public function updateProgress(int $percent): void
    {
        $userId = Auth::id();
        if (! $userId) {
            return;
        }

        // Batasi rentang nilai 0 s/d 100
        $percent = max(0, min(100, $percent));

        $completedAt = $percent >= 100
            ? ($this->progress?->completed_at ?? now())
            : null;

        $this->progress = UserLearningProgress::updateOrCreate(
            [
                'user_id' => $userId,
                'learning_material_id' => $this->material->id,
            ],
            [
                'progress_percent' => $percent,
                'completed_at' => $completedAt,
            ]
        );

        $this->progressPercent = $percent;

        if ($percent >= 100) {
            session()->flash('progress_status', 'Selamat! Anda telah menyelesaikan seluruh materi pembelajaran ini.');
        } else {
            session()->flash('progress_status', "Progress belajar berhasil diperbarui menjadi {$percent}%.");
        }
    }

    /**
     * Tandai materi selesai secara instan (100%).
     */
    public function markCompleted(): void
    {
        $this->updateProgress(100);
    }

    public function render()
    {
        $hasPostTest = (bool) $this->material->postTest;
        $isCompleted = $this->progressPercent >= 100;

        return view('livewire.learning.show', [
            'hasPostTest' => $hasPostTest,
            'isCompleted' => $isCompleted,
            'postTest' => $this->material->postTest,
        ]);
    }
}
