<?php

namespace App\Observers;

use App\Models\Notification;
use App\Models\Quiz;
use App\Models\User;
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
