<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeTopic;
use App\Models\User;
use Illuminate\Database\Seeder;

class KnowledgeTopicSeeder extends Seeder
{
    /**
     * Seed database dengan Topik Pengetahuan Resmi & Dokumen Kurasi Manual (PRD v2.0 §3.2).
     */
    public function run(): void
    {
        $admin = User::role('admin')->first() ?? User::first();
        if (! $admin) {
            return;
        }

        // 1. Seed 6 Topik Pengetahuan Formal Perusahaan (Dikelola Admin/HRD)
        $topicSop = KnowledgeTopic::firstOrCreate(
            ['name' => 'SOP & Prosedur Operasional Standar'],
            [
                'description' => 'Prosedur operasional baku dan standar alur kerja operasional operasional PT Catur Pilar Sejahtera.',
                'created_by' => $admin->id,
            ]
        );

        $topicHr = KnowledgeTopic::firstOrCreate(
            ['name' => 'Peraturan Perusahaan & Kebijakan HR'],
            [
                'description' => 'Tata tertib kerja, kode etik korporat, kebijakan jam kerja, serta hak & kewajiban seluruh karyawan PT CPS.',
                'created_by' => $admin->id,
            ]
        );

        $topicK3 = KnowledgeTopic::firstOrCreate(
            ['name' => 'Keselamatan, Kesehatan Kerja & Lingkungan (K3L)'],
            [
                'description' => 'Pedoman keselamatan kerja, penanganan insiden berbahaya, pemakaian APD wajib, dan prosedur tanggap darurat pabrik.',
                'created_by' => $admin->id,
            ]
        );

        $topicTeknis = KnowledgeTopic::firstOrCreate(
            ['name' => 'Instruksi Kerja & Standar Teknis'],
            [
                'description' => 'Petunjuk teknis pengoperasian mesin industri, kalibrasi sensor, dan lembar cek perawatan preventif berkala.',
                'created_by' => $admin->id,
            ]
        );

        $topicMutu = KnowledgeTopic::firstOrCreate(
            ['name' => 'Manajemen Mutu & Quality Assurance (QA/QC)'],
            [
                'description' => 'Kebijakan mutu ISO 9001:2015, kriteria inspeksi incoming & outgoing material, serta prosedur pengendalian ketidaksesuaian produk.',
                'created_by' => $admin->id,
            ]
        );

        $topicIt = KnowledgeTopic::firstOrCreate(
            ['name' => 'Teknologi Informasi & Keamanan Siber'],
            [
                'description' => 'Kebijakan penggunaan akun sistem CPS ERA, proteksi kerahasiaan data perusahaan, dan tata kelola perangkat IT kantor.',
                'created_by' => $admin->id,
            ]
        );

        // 2. Divisi pendukung untuk filter sekunder opsional
        $divEngineering = Division::where('name', 'Engineering')->first();
        $divProduksi = Division::where('name', 'Produksi')->first();
        $divQC = Division::where('name', 'Quality Control')->first();
        $divHRGA = Division::where('name', 'HRGA')->first();

        // 3. Seed Dokumen Kurasi Manual (SOP & Kebijakan) — Referensi Pasif Tanpa Poin
        $documents = [
            [
                'title' => 'SOP Kalibrasi Sensor dan Manometer Mesin Injection',
                'topic_id' => $topicSop->id,
                'division_id' => $divEngineering?->id,
                'type' => 'sop',
                'external_link' => null,
                'file_url' => 'knowledge-documents/files/sop-kalibrasi-sensor.pdf',
                'description' => "Prosedur operasional baku mengenai kalibrasi berkala sensor temperatur dan tekanan hidrolik mesin injection molding.\n\n1. Pastikan mesin dalam status lockout/tagout sebelum inspeksi.\n2. Sambungkan calibrator fluke presisi ke terminal probe thermocouple.\n3. Verifikasi simpangan pembacaan tidak melampaui toleransi ±0.5°C.",
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Peraturan Perusahaan PT CPS: Tata Tertib & Kebijakan Ketenagakerjaan',
                'topic_id' => $topicHr->id,
                'division_id' => $divHRGA?->id,
                'type' => 'dokumen',
                'external_link' => null,
                'file_url' => 'knowledge-documents/files/peraturan-perusahaan-cps.pdf',
                'description' => 'Pedoman peraturan perusahaan resmi PT Catur Pilar Sejahtera mengatur hak cuti, kehadiran, kerahasiaan data internal, etika profesional kerja, dan mekanisme komplain ketenagakerjaan.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Standar Keselamatan Kerja & Penggunaan APD di Area Produksi (K3)',
                'topic_id' => $topicK3->id,
                'division_id' => $divProduksi?->id,
                'type' => 'sop',
                'external_link' => null,
                'file_url' => 'knowledge-documents/files/pedoman-k3-area-produksi.pdf',
                'description' => 'Ketentuan wajib pemakaian alat pelindung diri (safety shoes, kacamata pelindung, earplug, dan sarung tangan tahan panas) selama berada di lantai workshop manufaktur.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Instruksi Kerja Pemeliharaan Preventif Harian Mesin Bubut CNC',
                'topic_id' => $topicTeknis->id,
                'division_id' => $divEngineering?->id,
                'type' => 'dokumen',
                'external_link' => null,
                'file_url' => 'knowledge-documents/files/ik-preventive-maintenance-cnc.docx',
                'description' => 'Instruksi kerja langkah demi langkah untuk operator dan teknisi dalam memeriksa level pelumas spindle, membersihkan chip sisa pemotongan, dan memeriksa kekencangan chuck sebelum operasi shift.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Panduan Mutu: Verifikasi Kualitas & Toleransi Dimensi Produk',
                'topic_id' => $topicMutu->id,
                'division_id' => $divQC?->id,
                'type' => 'dokumen',
                'external_link' => null,
                'file_url' => 'knowledge-documents/files/panduan-mutu-iso9001.pdf',
                'description' => 'Dokumen acuan pengendalian kualitas inspeksi dimensi produk menggunakan jangka sorong digital dan mikrometer sekrup dengan akurasi 0.01 mm.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Kebijakan Keamanan Akun & Perlindungan Data Internal Perusahaan',
                'topic_id' => $topicIt->id,
                'division_id' => null, // Umum / Seluruh Perusahaan
                'type' => 'dokumen',
                'external_link' => null,
                'file_url' => 'knowledge-documents/files/kebijakan-keamanan-informasi.pdf',
                'description' => 'Kebijakan resmi penanganan kredensial login, pencegahan kebocoran data rahasia operasional, larangan sharing password, dan panduan pelaporan anomali sistem IT PT CPS.',
                'status' => 'published',
                'created_by' => $admin->id,
            ],
        ];

        foreach ($documents as $docData) {
            KnowledgeDocument::firstOrCreate(
                ['title' => $docData['title']],
                $docData
            );
        }
    }
}
