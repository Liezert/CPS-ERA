<?php

namespace App\Observers;

use App\Models\Achievement;
use App\Models\Notification;
use App\Models\UserAchievement;

class UserAchievementObserver
{
    /**
     * Handle the UserAchievement "created" event.
     * Otomatis membuat notifikasi bertipe 'achievement_baru' untuk user penerima.
     */
    public function created(UserAchievement $userAchievement): void
    {
        $achievement = $userAchievement->achievement ?? Achievement::find($userAchievement->achievement_id);
        $achievementName = $achievement?->name ?? 'Achievement';

        Notification::create([
            'user_id' => $userAchievement->user_id,
            'type' => 'achievement_baru',
            'title' => 'Achievement Baru Terbuka!',
            'message' => "Selamat, Anda berhasil membuka badge achievement: {$achievementName}!",
            'related_type' => 'achievement',
            'related_id' => (string) $userAchievement->id,
            'read_at' => null,
        ]);
    }
}
