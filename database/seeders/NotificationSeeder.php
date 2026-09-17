<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Notification;
use App\Models\PointTransaction;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        if ($users->isEmpty()) {
            return;
        }

        $achievements = Achievement::all();

        // 1. Seed User Achievements (Interim MVP: sebagian unlocked, sebagian locked)
        // TODO: Menunggu keputusan PRD §5.3 (Poin 5: Kriteria unlock achievement).
        foreach ($users as $user) {
            // Unlock 2 achievement pertama untuk setiap user demo
            $unlockedAchievements = $achievements->take(2);
            foreach ($unlockedAchievements as $achievement) {
                UserAchievement::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'achievement_id' => $achievement->id,
                    ],
                    [
                        'unlocked_at' => now()->subDays(rand(1, 10)),
                    ]
                );
            }
        }

        // 2. Seed 4 Jenis Notifikasi untuk semua pengguna demo
        // Jenis notifikasi: 'knowledge_baru', 'misi_baru', 'ba_review', 'achievement_baru'
        foreach ($users as $user) {
            $sampleNotifications = [
                [
                    'type' => 'knowledge_baru',
                    'title' => 'Dokumen Pengetahuan Baru Diterbitkan',
                    'message' => 'SOP Pengoperasian Mesin Press Hidrolik telah dipublikasikan di Knowledge Repository.',
                    'related_type' => 'knowledge_document',
                    'related_id' => null,
                    'read_at' => null,
                    'created_at' => now()->subMinutes(15),
                ],
                [
                    'type' => 'misi_baru',
                    'title' => 'Tantangan Misi Baru Tersedia',
                    'message' => 'Kuis Standar K3 Keselamatan Fabrikasi kini dibuka dengan reward 50 poin XP.',
                    'related_type' => 'quiz',
                    'related_id' => null,
                    'read_at' => null,
                    'created_at' => now()->subHours(2),
                ],
                [
                    'type' => 'ba_review',
                    'title' => 'BA Baru Menunggu Review',
                    'message' => 'Laporan insiden BA-2026-0004 membutuhkan peninjauan dan persetujuan supervisor.',
                    'related_type' => 'ba_incident',
                    'related_id' => null,
                    'read_at' => null,
                    'created_at' => now()->subHours(5),
                ],
                [
                    'type' => 'achievement_baru',
                    'title' => 'Selamat! Lencana Baru Terbuka',
                    'message' => 'Anda telah membuka badge prestasi "Knowledge Contributor" atas kontribusi dokumen Anda.',
                    'related_type' => 'achievement',
                    'related_id' => null,
                    'read_at' => now()->subDay(),
                    'created_at' => now()->subDay(),
                ],
            ];

            foreach ($sampleNotifications as $notifData) {
                // Hindari duplikasi jika sudah ada notifikasi dengan judul & user yang sama
                Notification::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => $notifData['type'],
                        'title' => $notifData['title'],
                    ],
                    [
                        'message' => $notifData['message'],
                        'related_type' => $notifData['related_type'],
                        'related_id' => $notifData['related_id'],
                        'read_at' => $notifData['read_at'],
                        'created_at' => $notifData['created_at'],
                    ]
                );
            }
        }

        // 3. Seed sample point transactions untuk user demo (grafik performa 6 bulan)
        $demoUsers = User::take(4)->get();
        foreach ($demoUsers as $user) {
            $samplePointsByMonth = [
                5 => 120, // 5 bulan lalu
                4 => 250, // 4 bulan lalu
                3 => 180, // 3 bulan lalu
                2 => 340, // 2 bulan lalu
                1 => 480, // bulan lalu
                0 => 310, // bulan ini
            ];

            foreach ($samplePointsByMonth as $subMonths => $pts) {
                $monthDate = now()->subMonths($subMonths)->startOfMonth()->addDays(5);
                PointTransaction::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'description' => "Poin Aktivitas Bulanan {$monthDate->format('M Y')}",
                    ],
                    [
                        'ledger_type' => PointTransaction::LEDGER_XP,
                        'points' => $pts,
                        'source_type' => 'mission_completed',
                        'source_id' => null,
                        'created_at' => $monthDate,
                    ]
                );
            }
        }
    }
}
