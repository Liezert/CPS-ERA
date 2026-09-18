<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\KnowledgeTopic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

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
                'file_url' => 'knowledge-documents/files/ik-preventive-maintenance-cnc.pdf',
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

            $this->ensurePlaceholderFile($docData['file_url'], $docData['title']);
        }
    }

    /**
     * Pastikan dokumen kurasi punya berkas fisik di disk `public`.
     *
     * Tanpa ini, baris knowledge_documents menunjuk path yang tidak pernah ada
     * sehingga unduhan di UI selalu gagal. Berkas ditulis hanya bila belum ada,
     * jadi seeder tetap idempoten dan tidak menimpa dokumen asli yang diunggah.
     */
    private function ensurePlaceholderFile(?string $path, string $title): void
    {
        if ($path === null || $path === '' || Storage::disk('public')->exists($path)) {
            return;
        }

        Storage::disk('public')->put($path, $this->minimalPdf($title));
    }

    /**
     * Bangun PDF satu halaman yang valid, dengan tabel xref beroffset akurat.
     */
    private function minimalPdf(string $title): string
    {
        // Placeholder: sanitasi judul agar aman di dalam literal string PDF.
        $text = preg_replace('/[^A-Za-z0-9 .,:&_-]/', '', $title);
        $stream = "BT /F1 12 Tf 57 780 Td ({$text}) Tj 0 -24 Td (Dokumen placeholder - unggah berkas asli untuk menggantikannya.) Tj ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= 'xref'."\n".'0 '.(count($objects) + 1)."\n".'0000000000 65535 f '."\n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= 'trailer'."\n".'<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\n"
            .'startxref'."\n".$xrefPos."\n".'%%EOF'."\n";

        return $pdf;
    }
}
