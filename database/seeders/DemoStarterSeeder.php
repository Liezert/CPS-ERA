<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\LearningCategory;
use App\Models\LearningMaterial;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use App\Services\EmployeeAccountService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data starter untuk pengujian manual: 3 akun uji, 3 materi Learning, dan 3 misi.
 *
 * Dipakai oleh `php artisan cps:reset-demo-data` setelah pembersihan, tapi aman dijalankan
 * sendiri (`php artisan db:seed --class=DemoStarterSeeder`) karena semuanya firstOrCreate.
 * Tidak didaftarkan di DatabaseSeeder: ini data uji, bukan data production.
 */
class DemoStarterSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'password';

    /**
     * @var list<array{name: string, email: string, employee_id: string, divisi: string, jabatan: string, role: string}>
     */
    public const ACCOUNTS = [
        [
            'name' => 'Karyawan Demo',
            'email' => 'employee@cps.test',
            'employee_id' => 'EMP-001',
            'divisi' => 'Produksi',
            'jabatan' => 'Operator Produksi',
            'role' => 'employee',
        ],
        [
            // Divisi sama dengan employee supaya alur approval CAPA tahap Supervisor terhubung.
            'name' => 'Supervisor Demo',
            'email' => 'supervisor@cps.test',
            'employee_id' => 'SPV-001',
            'divisi' => 'Produksi',
            'jabatan' => 'Supervisor Produksi',
            'role' => 'supervisor',
        ],
        [
            'name' => 'Admin Demo',
            'email' => 'admin@cps.test',
            'employee_id' => 'ADM-001',
            'divisi' => 'HRGA',
            'jabatan' => 'Admin HRGA',
            'role' => 'admin',
        ],
    ];

    public function run(): void
    {
        // Misi dibuat lebih dulu: QuizObserver mengirim notifikasi "misi baru" ke seluruh user,
        // jadi urutan ini membuat kotak notifikasi akun uji benar-benar kosong di awal.
        $this->seedMissions();

        $admin = $this->seedAccounts();
        $this->seedLearningMaterials($admin);
    }

    /**
     * @return User Akun admin demo (dipakai sebagai pemilik data master & materi).
     */
    public function seedAccounts(): User
    {
        $service = app(EmployeeAccountService::class);
        $accounts = [];

        foreach (self::ACCOUNTS as $account) {
            $existing = User::where('email', $account['email'])->first();

            if ($existing) {
                $accounts[$account['role']] = $existing;

                continue;
            }

            $accounts[$account['role']] = $service->create([
                'name' => $account['name'],
                'email' => $account['email'],
                'employee_id' => $account['employee_id'],
                'division_id' => Division::where('name', $account['divisi'])->value('id'),
                'jabatan' => $account['jabatan'],
                'role' => $account['role'],
            ], self::DEMO_PASSWORD)['user'];
        }

        return $accounts['admin'];
    }

    public function seedLearningMaterials(User $admin): void
    {
        $materials = [
            [
                'category' => 'K3 & Lingkungan Kerja',
                'title' => 'Penerapan Budaya 5S (Ringkas, Rapi, Resik, Rawat, Rajin) di Lantai Produksi',
                'type' => 'artikel',
                'description' => 'Panduan penerapan 5S di area produksi: memilah barang yang tidak terpakai, menata perkakas pada posisi tetap, membersihkan mesin setiap akhir shift, merawat standar yang sudah dicapai, dan membiasakan seluruh anggota lini menjalankannya.',
            ],
            [
                'category' => 'SOP & K3',
                'title' => 'Prosedur Keselamatan Pengoperasian Mesin & APD Wajib',
                'type' => 'dokumen',
                'description' => 'Langkah aman menghidupkan dan mematikan mesin produksi, pemeriksaan pengaman sebelum operasi, prosedur lockout-tagout saat perawatan, serta alat pelindung diri yang wajib dipakai di tiap area kerja.',
            ],
            [
                'category' => 'Quality Control & Improvement',
                'title' => 'Standar Identifikasi Cacat Produk & Alur Pelaporan CAPA',
                'type' => 'artikel',
                'description' => 'Cara mengenali jenis cacat produk berdasarkan standar mutu, memisahkan produk tidak sesuai, dan melaporkannya lewat form CAPA sampai tindakan korektif diverifikasi.',
            ],
        ];

        foreach ($materials as $material) {
            $category = LearningCategory::firstOrCreate(
                ['name' => $material['category']],
                ['created_by' => $admin->id],
            );

            LearningMaterial::firstOrCreate(
                ['title' => $material['title']],
                [
                    'id' => (string) Str::uuid(),
                    'learning_category_id' => $category->id,
                    'type' => $material['type'],
                    'description' => $material['description'],
                    'content_url' => null,
                    'xp_reward' => 10,
                    'status' => 'published',
                    'created_by' => $admin->id,
                ],
            );
        }
    }

    /**
     * Misi di aplikasi ini berbentuk kuis (quizzes.type = mission_quiz), jadi tiap misi diberi satu
     * pertanyaan konfirmasi supaya bisa diselesaikan dan poinnya benar-benar masuk saat diuji.
     */
    public function seedMissions(): void
    {
        $missions = [
            [
                'title' => 'Langkah Pertama Perbaikan',
                'points' => 50,
                'description' => 'Laporkan 1 kejadian insiden atau ketidaksesuaian melalui form CAPA.',
                'question' => 'Setelah menemukan ketidaksesuaian di area kerja, apa langkah yang benar?',
                'options' => [
                    ['Catat kejadiannya lalu laporkan lewat form CAPA agar bisa ditindaklanjuti Supervisor', true],
                    ['Diamkan saja selama produksi masih bisa berjalan', false],
                    ['Sampaikan lisan ke rekan kerja tanpa dicatat di sistem', false],
                ],
            ],
            [
                'title' => 'Karyawan Sadar K3',
                'points' => 30,
                'description' => 'Selesaikan dan baca 2 materi edukasi pada modul Learning.',
                'question' => 'Alat pelindung diri wajib dipakai pada kondisi apa?',
                'options' => [
                    ['Setiap berada di area kerja yang mensyaratkannya, sesuai SOP tiap area', true],
                    ['Hanya saat ada inspeksi atau kunjungan dari manajemen', false],
                    ['Hanya jika mesin sedang dalam kondisi rusak', false],
                ],
            ],
            [
                'title' => 'Uji Ketangkasan Standar Mutu',
                'points' => 40,
                'description' => 'Selesaikan kuis pemahaman mutu harian dengan nilai tuntas.',
                'question' => 'Produk yang teridentifikasi cacat saat inspeksi harus diperlakukan bagaimana?',
                'options' => [
                    ['Dipisahkan dan diberi penanda agar tidak tercampur produk baik, lalu dilaporkan', true],
                    ['Digabung kembali ke lot produksi supaya target output tetap tercapai', false],
                    ['Langsung dibuang tanpa dicatat di laporan mutu', false],
                ],
            ],
        ];

        foreach ($missions as $mission) {
            $quiz = Quiz::firstOrCreate(
                ['title' => $mission['title'], 'type' => 'mission_quiz'],
                [
                    'id' => (string) Str::uuid(),
                    'related_type' => 'none',
                    'related_id' => null,
                    'points_reward' => $mission['points'],
                    'description' => $mission['description'],
                ],
            );

            $question = QuizQuestion::firstOrCreate(
                ['quiz_id' => $quiz->id, 'order_index' => 1],
                ['id' => (string) Str::uuid(), 'question_text' => $mission['question']],
            );

            foreach ($mission['options'] as [$text, $isCorrect]) {
                QuizOption::firstOrCreate(
                    ['quiz_question_id' => $question->id, 'option_text' => $text],
                    ['id' => (string) Str::uuid(), 'is_correct' => $isCorrect],
                );
            }
        }
    }
}
