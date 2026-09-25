<?php

namespace App\Observers;

use App\Enums\QuizRelatedType;
use App\Models\LearningMaterial;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\User;
use BackedEnum;
use Illuminate\Support\Str;

class QuizObserver
{
    /**
     * Handle the Quiz "created" event.
     * Mengirim notifikasi 'misi_baru' saat quiz baru bertipe 'mission_*' dibuat.
     */
    public function created(Quiz $quiz): void
    {
        if (str_starts_with($quiz->type, 'mission_')) {
            $this->notifyUsers($quiz);
        }

        $this->publishBaLearningMaterial($quiz);
    }

    /**
     * Materi Learning hasil laporan CAPA menunggu post-test (status `candidate`); begitu HRGA
     * membuat post-test-nya, materi langsung terbit di Learning. Satu titik untuk semua jalur
     * pembuatan post-test (tombol "Buat Post-Test" di review CAPA maupun form materi Learning).
     */
    protected function publishBaLearningMaterial(Quiz $quiz): void
    {
        // Form Filament bisa mengisi related_type sebagai instance enum (kolom tidak di-cast).
        $relatedType = $quiz->related_type instanceof BackedEnum ? $quiz->related_type->value : $quiz->related_type;

        if ($quiz->type !== 'post_test' || $relatedType !== QuizRelatedType::LearningMaterial->value) {
            return;
        }

        LearningMaterial::whereKey($quiz->related_id)
            ->whereNotNull('source_ba_id')
            ->where('status', 'candidate')
            ->update(['status' => 'published']);
    }

    /**
     * Notify all users about the new mission.
     */
    protected function notifyUsers(Quiz $quiz): void
    {
        $users = User::all();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'misi_baru',
                'title' => 'Misi Baru: '.Str::limit($quiz->title, 100),
                'message' => "Misi baru '{$quiz->title}' telah tersedia. Selesaikan dan dapatkan {$quiz->points_reward} poin!",
                'related_type' => 'quiz',
                'related_id' => (string) $quiz->id,
                'read_at' => null,
            ]);
        }
    }
}
