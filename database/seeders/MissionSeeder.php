<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MissionSeeder extends Seeder
{
    /**
     * Seed database dengan misi studi kasus (Case Study) dan kuis cepat (Quiz).
     * Sesuai Data Contract §1 & ERD (quizzes.type: mission_case_study, mission_quiz).
     */
    public function run(): void
    {
        // ---------------------------------------------------------------------
        // MISI 1: Studi Kasus - Kontaminasi Raw Material Resin
        // ---------------------------------------------------------------------
        $m1 = Quiz::firstOrCreate(
            [
                'title' => 'Investigasi Kontaminasi Raw Material Resin Lini Injeksi',
                'type' => 'mission_case_study',
            ],
            [
                'id' => (string) Str::uuid(),
                'related_type' => 'none',
                'related_id' => null,
                'points_reward' => 40,
                'description' => "SKENARIO KASUS:\nPada shift 2 pukul 14:30, operator Lini Injeksi 03 melaporkan cacat visual berupa bintik hitam (black specks) pada 15% hasil cetakan cover panel presisi. Suhu barrel normal (210°C), namun material hopper baru saja diisi ulang dengan sak resin ABS batch LOT-2026-089A.\n\nSebagai team investigasi, tentukan urutan tindakan penanganan insiden, isolasi produk non-conforming, dan pencegahan kontaminasi berulang.",
            ]
        );

        $q1_1 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m1->id, 'order_index' => 1],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Apa tindakan pertama yang wajib dilakukan oleh penanggung jawab lini saat mendeteksi cacat berulang pada komponen?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_1->id, 'option_text' => 'Hentikan lini produksi sementara, pasang tag HOLD pada lot terdampak, dan laporkan ke Quality Control'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_1->id, 'option_text' => 'Tingkatkan suhu barrel sebesar 30°C agar bintik hitam meleleh sempurna'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_1->id, 'option_text' => 'Lanjutkan produksi sampai batch habis, lalu lakukan sortir manual di gudang'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        $q1_2 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m1->id, 'order_index' => 2],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Saat memeriksa hopper loader, ditemukan sisa material regrind warna hitam dari proses sebelumnya. Apa akar masalah utama insiden ini?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_2->id, 'option_text' => 'Prosedur pembersihan (purging & vacuuming) hopper saat penggantian warna/material tidak dijalankan sesuai checklist SOP'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_2->id, 'option_text' => 'Daya listrik mesin mengalami lonjakan tegangan sesaat'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_2->id, 'option_text' => 'Kadar kelembaban udara ruang produksi terlalu tinggi'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        $q1_3 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m1->id, 'order_index' => 3],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Tindakan korektif jangka panjang apa yang paling efektif untuk mencegah terulangnya kontaminasi hopper?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_3->id, 'option_text' => 'Menerapkan verifikasi ganda (two-man check) pada form sign-off pembersihan hopper sebelum pengisian material virgin'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_3->id, 'option_text' => 'Menghapus penggunaan material daur ulang (regrind) secara keseluruhan dari pabrik'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q1_3->id, 'option_text' => 'Mengurangi kecepatan putaran screw injeksi menjadi 50%'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        // ---------------------------------------------------------------------
        // MISI 2: Studi Kasus - Troubleshooting Suhu Chiller
        // ---------------------------------------------------------------------
        $m2 = Quiz::firstOrCreate(
            [
                'title' => 'Troubleshooting Kegagalan Suhu Chiller Mesin Cetak',
                'type' => 'mission_case_study',
            ],
            [
                'id' => (string) Str::uuid(),
                'related_type' => 'none',
                'related_id' => null,
                'points_reward' => 35,
                'description' => "SKENARIO KASUS:\nSuhu sirkulasi pendingin chiller cetakan melonjak dari setting 18°C ke 32°C dalam waktu 20 menit, memicu alarm overheat temperatur pada mesin utama. Tekanan refrigerant sirkuit kompresor terbaca drop drastis di bawah nilai nominal.",
            ]
        );

        $q2_1 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m2->id, 'order_index' => 1],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Tanda penurunan tekanan hisap (low suction pressure) yang drastis pada sirkuit pendingin biasanya mengindikasikan apa?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q2_1->id, 'option_text' => 'Terjadi kebocoran refrigerant (freon) atau penyumbatan pada pipa kapiler/expansion valve'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q2_1->id, 'option_text' => 'Pompa sirkulasi air cetakan berputar melebihi RPM batas'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q2_1->id, 'option_text' => 'Sensor suhu thermostat mengalami over-voltage'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        $q2_2 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m2->id, 'order_index' => 2],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Sebelum teknisi menyambungkan manifold gauge dan leak detector ke unit chiller, prosedur keselamatan apa yang wajib dipatuhi?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q2_2->id, 'option_text' => 'Pasang Lockout-Tagout (LOTO) pada panel breaker chiller dan gunakan sarung tangan tahan dingin cryogenic'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q2_2->id, 'option_text' => 'Nyalakan blower pendingin tambahan langsung mengarah ke evaporator'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q2_2->id, 'option_text' => 'Buka katup pembuangan air tangki reservoir chiller sampai kosong'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        // ---------------------------------------------------------------------
        // MISI 3: Kuis Cepat - Keselamatan Listrik & Panel Mesin
        // ---------------------------------------------------------------------
        $m3 = Quiz::firstOrCreate(
            [
                'title' => 'Standar Operasional Keselamatan Listrik & Panel Mesin',
                'type' => 'mission_quiz',
            ],
            [
                'id' => (string) Str::uuid(),
                'related_type' => 'none',
                'related_id' => null,
                'points_reward' => 20,
                'description' => 'Kuis cepat evaluasi protokol keselamatan saat beraktivitas di dekat panel distribusi daya 380V, grounding mesin, dan verifikasi ketiadaan tegangan.',
            ]
        );

        $q3_1 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m3->id, 'order_index' => 1],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Alat ukur apa yang wajib digunakan untuk memastikan ketiadaan tegangan (zero voltage verification) sebelum menyentuh konduktor listrik?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q3_1->id, 'option_text' => 'Multimeter / Voltmeter yang telah terkalibrasi dan diuji live-dead-live method'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q3_1->id, 'option_text' => 'Testpen obeng biasa tanpa isolasi rating CAT III'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q3_1->id, 'option_text' => 'Thermometer inframerah jarak jauh'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        $q3_2 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m3->id, 'order_index' => 2],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Berapa jarak batas aman (flash protection boundary) minimum yang diwajibkan saat membuka panel daya tegangan rendah tanpa APD Arc Flash?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q3_2->id, 'option_text' => 'Panel tidak boleh dibuka oleh personel tanpa kualifikasi listrik dan APD Arc Flash yang sesuai'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q3_2->id, 'option_text' => 'Cukup menjaga jarak 10 cm dari busbar terbuka'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q3_2->id, 'option_text' => 'Bebas dibuka asalkan lantai karet telah dipasang'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        // ---------------------------------------------------------------------
        // MISI 4: Kuis Cepat - Penerapan Budaya 5S Lini Fabrikasi
        // ---------------------------------------------------------------------
        $m4 = Quiz::firstOrCreate(
            [
                'title' => 'Penerapan Budaya 5S & Ringkas-Rapi di Lini Fabrikasi',
                'type' => 'mission_quiz',
            ],
            [
                'id' => (string) Str::uuid(),
                'related_type' => 'none',
                'related_id' => null,
                'points_reward' => 15,
                'description' => 'Evaluasi penerapan pilar 5S (Ringkas, Rapi, Resik, Rawat, Rajin) dalam menjaga keteraturan area kerja, penempatan tools, dan penandaan visual pabrik.',
            ]
        );

        $q4_1 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m4->id, 'order_index' => 1],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Pilar Seiri (Ringkas) pada prinsip 5S paling tepat didefinisikan sebagai aktivitas apa?',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q4_1->id, 'option_text' => 'Memilah barang yang diperlukan dan menyingkirkan barang yang tidak terpakai dari area kerja (Red Tag)'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q4_1->id, 'option_text' => 'Mengecat lantai dengan garis marka berwarna-warni'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q4_1->id, 'option_text' => 'Menyapu lantai setiap pergantian shift saja'], ['id' => (string) Str::uuid(), 'is_correct' => false]);

        $q4_2 = QuizQuestion::firstOrCreate(
            ['quiz_id' => $m4->id, 'order_index' => 2],
            [
                'id' => (string) Str::uuid(),
                'question_text' => 'Manfaat utama penerapan shadow board pada penyimpanan kunci dan tools mekanik adalah:',
            ]
        );
        QuizOption::firstOrCreate(['quiz_question_id' => $q4_2->id, 'option_text' => 'Memudahkan inspeksi visual cepat untuk mengetahui alat yang hilang atau sedang dipinjam dalam waktu < 5 detik'], ['id' => (string) Str::uuid(), 'is_correct' => true]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q4_2->id, 'option_text' => 'Menambah nilai estetika dinding workshop pabrik'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
        QuizOption::firstOrCreate(['quiz_question_id' => $q4_2->id, 'option_text' => 'Mencegah debu menempel pada peralatan kerja'], ['id' => (string) Str::uuid(), 'is_correct' => false]);
    }
}
