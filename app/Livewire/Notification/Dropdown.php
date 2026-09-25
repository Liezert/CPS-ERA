<?php

namespace App\Livewire\Notification;

use App\Models\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dropdown extends Component
{
    public int $unreadCount = 0;

    /**
     * Tandai sebuah notifikasi spesifik sebagai dibaca dan arahkan ke halaman terkait.
     */
    public function markAsRead(string $notificationId)
    {
        $userId = Auth::id();
        if (! $userId) {
            return null;
        }

        $notification = Notification::where('user_id', $userId)
            ->where('id', $notificationId)
            ->first();

        if ($notification) {
            if (! $notification->read_at) {
                $notification->update(['read_at' => now()]);
            }

            $this->unreadCount = Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->count();

            // Redirect ke halaman terkait sesuai jenis notifikasi
            return match ($notification->type) {
                'knowledge_baru' => redirect()->route('knowledge.index'),
                'misi_baru' => redirect()->route('missions.index'),
                'ba_review', 'ba_ditolak_hr', 'ba_revisi' => $notification->related_id
                    ? redirect()->route('ba.show', $notification->related_id)
                    : redirect()->route('ba.index'),
                'achievement_baru' => redirect()->route('achievements.index'),
                default => null,
            };
        }

        return null;
    }

    /**
     * Tandai semua notifikasi milik user saat ini sebagai telah dibaca.
     */
    public function markAllAsRead(): void
    {
        $userId = Auth::id();
        if ($userId) {
            Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $this->unreadCount = 0;
        }
    }

    /**
     * Render dropdown notifikasi bell.
     */
    public function render(): View
    {
        $userId = Auth::id();

        $notifications = $userId
            ? Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
            : collect();

        $this->unreadCount = $userId
            ? Notification::where('user_id', $userId)
                ->whereNull('read_at')
                ->count()
            : 0;

        return view('livewire.notification.dropdown', [
            'notifications' => $notifications,
            'unreadCount' => $this->unreadCount,
        ]);
    }
}
