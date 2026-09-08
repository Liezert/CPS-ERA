<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'Knowledge Contributor',
                'description' => 'Aktif membagikan materi dan dokumen pengetahuan berharga di Knowledge Repository.',
                'icon' => 'heroicon-o-book-open',
            ],
            [
                'name' => 'Learning Champion',
                'description' => 'Menyelesaikan berbagai materi pembelajaran dan lulus post-test dengan nilai memuaskan.',
                'icon' => 'heroicon-o-academic-cap',
            ],
            [
                'name' => 'Kaizen Contributor',
                'description' => 'Mencatat insiden dan menghasilkan Lesson Learned yang disetujui untuk perbaikan berkelanjutan.',
                'icon' => 'heroicon-o-sparkles',
            ],
            [
                'name' => 'Safety Pioneer',
                'description' => 'Menunjukkan kepatuhan keselamatan kerja luar biasa dan konsisten melaporkan bahaya operasional.',
                'icon' => 'heroicon-o-shield-check',
            ],
            [
                'name' => 'Quiz Master',
                'description' => 'Berhasil menyelesaikan semua tantangan kuis pengetahuan dengan skor sempurna tanpa kesalahan.',
                'icon' => 'heroicon-o-puzzle-piece',
            ],
            [
                'name' => 'Zero Defect Hero',
                'description' => 'Menjaga standar kualitas divisi dan memitigasi potensi cacat produksi secara proaktif.',
                'icon' => 'heroicon-o-check-badge',
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(
                ['name' => $achievement['name']],
                $achievement
            );
        }
    }
}
