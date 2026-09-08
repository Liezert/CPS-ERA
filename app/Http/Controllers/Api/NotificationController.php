<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Tampilkan daftar notifikasi milik user yang sedang login.
     * GET /api/me/notifications?unread_only=
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $query = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        $notifications = $query->get();
        $unreadCount = Notification::where('user_id', $user->id)->unread()->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'data' => $notifications,
        ]);
    }

    /**
     * Tandai satu notifikasi sebagai sudah dibaca.
     * PATCH /api/me/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $notification = Notification::findOrFail($id);

        // Keamanan: pastikan notifikasi adalah milik user yang sedang login
        if ((string) $notification->user_id !== (string) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke notifikasi ini.',
            ], 403);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil ditandai sebagai telah dibaca.',
            'data' => $notification,
        ]);
    }

    /**
     * Tampilkan daftar seluruh achievement beserta status unlock milik user yang sedang login.
     * GET /api/me/achievements
     */
    public function myAchievements(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $userAchievements = $user->userAchievements()
            ->get()
            ->keyBy('achievement_id');

        $achievements = Achievement::orderBy('id')->get()->map(function (Achievement $achievement) use ($userAchievements) {
            $unlocked = $userAchievements->get($achievement->id);

            return [
                'id' => $achievement->id,
                'name' => $achievement->name,
                'description' => $achievement->description,
                'icon' => $achievement->icon,
                'is_unlocked' => $unlocked !== null,
                'unlocked_at' => $unlocked?->unlocked_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $achievements,
        ]);
    }
}
