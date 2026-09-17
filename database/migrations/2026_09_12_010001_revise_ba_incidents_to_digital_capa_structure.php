<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Revisi total struktur tabel ba_incidents dari 2 upload file menjadi form digital CAPA/FTK (FR/QC/22).
     */
    public function up(): void
    {
        Schema::table('ba_incidents', function (Blueprint $table) {
            // Drop kolom upload file lama
            if (Schema::hasColumn('ba_incidents', 'file_ba_url')) {
                $table->dropColumn('file_ba_url');
            }
            if (Schema::hasColumn('ba_incidents', 'file_ftk_url')) {
                $table->dropColumn('file_ftk_url');
            }

            // Tambah kolom data terstruktur CAPA/FTK
            if (! Schema::hasColumn('ba_incidents', 'tanggal_pengisian')) {
                $table->date('tanggal_pengisian')->nullable()->after('division_id');
            }
            if (! Schema::hasColumn('ba_incidents', 'sumber_ketidaksesuaian')) {
                $table->string('sumber_ketidaksesuaian')->default('laporan_ketidaksesuaian')->after('tanggal_pengisian');
            }
            if (! Schema::hasColumn('ba_incidents', 'sumber_ketidaksesuaian_lainnya')) {
                $table->string('sumber_ketidaksesuaian_lainnya', 255)->nullable()->after('sumber_ketidaksesuaian');
            }
            if (! Schema::hasColumn('ba_incidents', 'tanggal_masalah')) {
                $table->date('tanggal_masalah')->nullable()->after('sumber_ketidaksesuaian_lainnya');
            }
            if (! Schema::hasColumn('ba_incidents', 'lokasi')) {
                $table->string('lokasi', 255)->nullable()->after('tanggal_masalah');
            }
            if (! Schema::hasColumn('ba_incidents', 'deskripsi_masalah')) {
                $table->text('deskripsi_masalah')->nullable()->after('lokasi');
            }

            // Analisis 5 Whys
            if (! Schema::hasColumn('ba_incidents', 'why_1')) {
                $table->text('why_1')->nullable()->after('deskripsi_masalah');
            }
            if (! Schema::hasColumn('ba_incidents', 'why_2')) {
                $table->text('why_2')->nullable()->after('why_1');
            }
            if (! Schema::hasColumn('ba_incidents', 'why_3')) {
                $table->text('why_3')->nullable()->after('why_2');
            }
            if (! Schema::hasColumn('ba_incidents', 'why_4')) {
                $table->text('why_4')->nullable()->after('why_3');
            }
            if (! Schema::hasColumn('ba_incidents', 'why_5')) {
                $table->text('why_5')->nullable()->after('why_4');
            }
            if (! Schema::hasColumn('ba_incidents', 'kesimpulan_akar_masalah')) {
                $table->text('kesimpulan_akar_masalah')->nullable()->after('why_5');
            }

            // Tindakan Koreksi (Sementara)
            if (! Schema::hasColumn('ba_incidents', 'koreksi_deskripsi')) {
                $table->text('koreksi_deskripsi')->nullable()->after('kesimpulan_akar_masalah');
            }
            if (! Schema::hasColumn('ba_incidents', 'koreksi_pic')) {
                $table->string('koreksi_pic', 150)->nullable()->after('koreksi_deskripsi');
            }
            if (! Schema::hasColumn('ba_incidents', 'koreksi_waktu')) {
                $table->string('koreksi_waktu', 100)->nullable()->after('koreksi_pic');
            }

            // Tindakan Korektif (Akar Masalah)
            if (! Schema::hasColumn('ba_incidents', 'korektif_deskripsi')) {
                $table->text('korektif_deskripsi')->nullable()->after('koreksi_waktu');
            }
            if (! Schema::hasColumn('ba_incidents', 'korektif_pic')) {
                $table->string('korektif_pic', 150)->nullable()->after('korektif_deskripsi');
            }
            if (! Schema::hasColumn('ba_incidents', 'korektif_waktu')) {
                $table->string('korektif_waktu', 100)->nullable()->after('korektif_pic');
            }

            // Potensi Risiko & Peluang
            if (! Schema::hasColumn('ba_incidents', 'is_potensi_risiko')) {
                $table->boolean('is_potensi_risiko')->default(false)->after('korektif_waktu');
            }
            if (! Schema::hasColumn('ba_incidents', 'is_potensi_peluang')) {
                $table->boolean('is_potensi_peluang')->default(false)->after('is_potensi_risiko');
            }

            // Kolom Verifikasi Tindakan (Diisi Reviewer)
            if (! Schema::hasColumn('ba_incidents', 'status_verifikasi')) {
                $table->string('status_verifikasi')->nullable()->after('is_potensi_peluang');
            }
            if (! Schema::hasColumn('ba_incidents', 'bukti_objektif')) {
                $table->text('bukti_objektif')->nullable()->after('status_verifikasi');
            }
            if (! Schema::hasColumn('ba_incidents', 'alasan_tidak_efektif')) {
                $table->text('alasan_tidak_efektif')->nullable()->after('bukti_objektif');
            }

            // Catatan Penolakan jika Reviewer me-reject
            if (! Schema::hasColumn('ba_incidents', 'catatan_penolakan')) {
                $table->text('catatan_penolakan')->nullable()->after('alasan_tidak_efektif');
            }
        });

        /**
         * Mapping status data lama ke status baru:
         * - 'created'  -> 'draft' (menunggu submit video pada alur multi-step)
         * - 'reviewed' -> 'approved'
         * - 'closed'   -> 'approved' (penutupan kini terintegrasi dengan verifikasi efektif)
         */
        DB::table('ba_incidents')->where('status', 'created')->update(['status' => 'draft']);
        DB::table('ba_incidents')->where('status', 'reviewed')->update(['status' => 'approved']);
        DB::table('ba_incidents')->where('status', 'closed')->update(['status' => 'approved']);

        // Set default status menjadi 'draft'
        Schema::table('ba_incidents', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ba_incidents', function (Blueprint $table) {
            $table->string('file_ba_url')->nullable();
            $table->string('file_ftk_url')->nullable();

            $table->dropColumn([
                'tanggal_pengisian',
                'sumber_ketidaksesuaian',
                'sumber_ketidaksesuaian_lainnya',
                'tanggal_masalah',
                'lokasi',
                'deskripsi_masalah',
                'why_1',
                'why_2',
                'why_3',
                'why_4',
                'why_5',
                'kesimpulan_akar_masalah',
                'koreksi_deskripsi',
                'koreksi_pic',
                'koreksi_waktu',
                'korektif_deskripsi',
                'korektif_pic',
                'korektif_waktu',
                'is_potensi_risiko',
                'is_potensi_peluang',
                'status_verifikasi',
                'bukti_objektif',
                'alasan_tidak_efektif',
                'catatan_penolakan',
            ]);

            $table->string('status')->default('created')->change();
        });
    }
};
