<?php

namespace Database\Seeders;

use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LearningSeeder extends Seeder
{
    /**
     * Seed database dengan data kategori, materi 7 jenis, dan post-test.
     */
    public function run(): void
    {
        $admin = User::role('admin')->first() ?? User::first();
        if (! $admin) {
            return;
        }

        // 1. Seed 4 Kategori Resmi (Dikelola Admin)
        $catMutu = LearningCategory::firstOrCreate(
            ['name' => 'Standar Mutu & Regulasi ISO'],
            ['created_by' => $admin->id]
        );

        $catMesin = LearningCategory::firstOrCreate(
            ['name' => 'Teknik Manufaktur & Operasional Mesin'],
            ['created_by' => $admin->id]
        );

        $catK3 = LearningCategory::firstOrCreate(
            ['name' => 'Keselamatan & Kesehatan Kerja (K3)'],
            ['created_by' => $admin->id]
        );

        $catAutomasi = LearningCategory::firstOrCreate(
            ['name' => 'Automasi & Pemeliharaan Preventif'],
            ['created_by' => $admin->id]
        );

        // 2. Seed 7 Jenis Materi Pembelajaran Lengkap
        $materialsData = [
            [
                'id' => (string) Str::uuid(),
                'learning_category_id' => $catMutu->id,
                'title' => 'SOP Kalibrasi Instrumen Pengukuran Suhu dan Tekanan',
                'type' => 'dokumen',
                'content_url' => 'https://portal.cps.co.id/docs/sop-kalibrasi-instrumen-v2.pdf',
                'description' => "Prosedur operasional baku mengenai verifikasi periodik sensor suhu dan manometer tekanan mesin produksi sesuai ISO 9001:2015.\n\nMemastikan akurasi pembacaan toleransi toleransi ±0.5°C sebelum memulai batch produksi.",
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'id' => (string) Str::uuid(),
                'learning_category_id' => $catMesin->id,
                'title' => 'Teknik Penggantian Seal Hidrolik Mesin Injection Moulding',
                'type' => 'video',
                'content_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'description' => "Video demonstrasi teknis pelepasan piston hidrolik, pemeriksaan keausan O-ring seal, dan pemasangan seal baru bertipe polyurethane pada mesin injection 250 ton.\n\nDilengkapi panduan penggunaan kunci torsi dan pelumasan berbasis standar pabrikan.",
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'id' => (string) Str::uuid(),
                'learning_category_id' => $catMutu->id,
                'title' => 'Pelatihan Pengendalian Cacat Produk & Penurunan Scrap Rate',
                'type' => 'presentasi',
                'content_url' => 'https://portal.cps.co.id/slides/pengendalian-scrap-2026.pdf',
                'description' => 'Slide deck presentasi identifikasi 5 jenis cacat dominan (sink mark, flash, air bubble, warping, short shot) serta metode troubleshooting berbasis diagram Ishikawa.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'id' => (string) Str::uuid(),
                'learning_category_id' => $catK3->id,
                'title' => 'Prinsip Dasar 5S dan Keselamatan Kerja Area Produksi',
                'type' => 'artikel',
                'content_url' => null,
                'description' => "Artikel pedoman implementasi Seiri (Ringkas), Seiton (Rapi), Seiso (Resik), Seiketsu (Rawat), dan Shitsuke (Rajin) di lini perakitan.\n\nMencegah kecelakaan kerja terpeleset, tersandung, serta meningkatkan efisiensi waktu pengambilan perkakas kerja.",
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'id' => (string) Str::uuid(),
                'learning_category_id' => $catMesin->id,
                'title' => 'Langkah Penyetelan Parameter Nozzle Temperature Mesin Cetak',
                'type' => 'tutorial',
                'content_url' => null,
                'description' => "Tutorial praktis penyetelan zona pemanas silinder barrel dari zona 1 hingga zona 4 untuk bahan baku bijih plastik Polypropylene (PP).\n\nLangkah-langkah mencakup pemanasan awal, monitoring thermo-controller, hingga uji purging.",
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'id' => (string) Str::uuid(),
                'learning_category_id' => $catAutomasi->id,
                'title' => 'Portal Dokumentasi Standar Sensor PLC Siemens S7-1200',
                'type' => 'link',
                'content_url' => 'https://support.industry.siemens.com',
                'description' => 'Tautan langsung ke dokumentasi resmi wiring diagram, addressing input/output, dan penanganan alarm error pada modul PLC unit konveyor terintegrasi.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'id' => (string) Str::uuid(),
                'learning_category_id' => $catAutomasi->id,
                'title' => 'Lembar Checklist Inspeksi Rutin Mingguan Motor & Pompa Chiller',
                'type' => 'file_pendukung',
                'content_url' => 'https://portal.cps.co.id/files/checklist-chiller-mingguan.xlsx',
                'description' => 'Berkas formulir digital evaluasi getaran (vibrasi), arus listrik motor (ampere meter), tekanan freon hisap-tekan, serta kebersihan saringan kondensor.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
        ];

        $createdMaterials = [];
        foreach ($materialsData as $mData) {
            $mat = LearningMaterial::firstOrCreate(
                ['title' => $mData['title']],
                $mData
            );
            $createdMaterials[] = $mat;
        }

        // 3. Seed Post-Test Quiz untuk Materi #1 dan Materi #2 (DoD #2)
        if (! empty($createdMaterials[0])) {
            $material1 = $createdMaterials[0];
            $quiz1 = Quiz::firstOrCreate(
                [
                    'type' => 'post_test',
                    'related_type' => 'learning_material',
                    'related_id' => $material1->id,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'Post-Test: Pemahaman SOP Kalibrasi Instrumen',
                    'points_reward' => 25,
                    'description' => 'Uji pemahaman prosedur kalibrasi, batas toleransi sensor, dan protokol pencatatan log verifikasi.',
                ]
            );

            // Seed pertanyaan Post-Test
            $q1 = QuizQuestion::firstOrCreate(
                ['quiz_id' => $quiz1->id, 'question_text' => 'Berapa batas toleransi deviasi sensor suhu yang diizinkan sebelum produksi batch baru?'],
                ['id' => (string) Str::uuid(), 'order_index' => 1]
            );

            QuizOption::firstOrCreate(
                ['quiz_question_id' => $q1->id, 'option_text' => '±0.5°C'],
                ['id' => (string) Str::uuid(), 'is_correct' => true]
            );
            QuizOption::firstOrCreate(
                ['quiz_question_id' => $q1->id, 'option_text' => '±2.5°C'],
                ['id' => (string) Str::uuid(), 'is_correct' => false]
            );
            QuizOption::firstOrCreate(
                ['quiz_question_id' => $q1->id, 'option_text' => '±5.0°C'],
                ['id' => (string) Str::uuid(), 'is_correct' => false]
            );
        }

        if (! empty($createdMaterials[1])) {
            $material2 = $createdMaterials[1];
            $quiz2 = Quiz::firstOrCreate(
                [
                    'type' => 'post_test',
                    'related_type' => 'learning_material',
                    'related_id' => $material2->id,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'title' => 'Post-Test: Penggantian Seal Hidrolik Mesin',
                    'points_reward' => 30,
                    'description' => 'Evaluasi keselamatan kerja saat isolasi tekanan hidrolik dan pemilihan material seal.',
                ]
            );

            $q2 = QuizQuestion::firstOrCreate(
                ['quiz_id' => $quiz2->id, 'question_text' => 'Tindakan awal apa yang wajib dilakukan sebelum membongkar silinder hidrolik?'],
                ['id' => (string) Str::uuid(), 'order_index' => 1]
            );

            QuizOption::firstOrCreate(
                ['quiz_question_id' => $q2->id, 'option_text' => 'Melakukan Lockout-Tagout (LOTO) dan melepaskan tekanan akumulator'],
                ['id' => (string) Str::uuid(), 'is_correct' => true]
            );
            QuizOption::firstOrCreate(
                ['quiz_question_id' => $q2->id, 'option_text' => 'Langsung melepas baut pengikat silinder tanpa dekompresi'],
                ['id' => (string) Str::uuid(), 'is_correct' => false]
            );
        }
    }
}
