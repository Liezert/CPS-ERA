<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan refresh token OAuth Google Drive milik satu akun perusahaan
     * yang dipakai bersama seluruh aplikasi (bukan per pengguna), sehingga
     * tabel ini hanya berisi satu baris aktif.
     */
    public function up(): void
    {
        Schema::create('google_drive_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Terenkripsi dengan Crypt::encryptString(); ciphertext jauh lebih panjang dari token aslinya.
            $table->text('refresh_token');
            // Akun Google yang memberi consent, untuk ditampilkan di halaman admin.
            $table->string('connected_email')->nullable();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_tokens');
    }
};
