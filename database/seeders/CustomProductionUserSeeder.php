<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use App\Services\EmployeeAccountService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Template akun karyawan SUNGGUHAN untuk production. Isi array $employees sendiri, lalu jalankan:
 *
 *   php artisan db:seed --class=CustomProductionUserSeeder
 *
 * Sengaja TIDAK didaftarkan di DatabaseSeeder. Setiap akun dibuat lewat EmployeeAccountService,
 * jadi otomatis memakai kata sandi sementara (EmployeeAccountService::TEMPORARY_PASSWORD) dan wajib
 * diganti saat login pertama. Email yang sudah terdaftar dilewati, jadi aman dijalankan ulang.
 *
 * Divisi ditulis dengan NAMA, bukan ID: ID divisi bisa berbeda antara lokal dan production.
 * Pakai salah satu dari 12 divisi resmi: Produksi, PPIC, Warehouse & Delivery, RM Warehouse,
 * Engineering, Quality Control, Jahit, Finance Accounting Tax, Purchasing, Marketing, Sales,
 * Plant Balben & Krian.
 *
 * Role: employee | supervisor | quality | admin.
 */
class CustomProductionUserSeeder extends Seeder
{
    /**
     * @var list<array{name: string, email: string, employee_id: string, divisi: string, jabatan: ?string, role: string}>
     */
    private array $employees = [
        // Contoh (hapus tanda komentar dan ganti dengan data asli):
        // [
        //     'name' => 'Nama Lengkap Karyawan',
        //     'email' => 'nama.karyawan@caturpilar.com',
        //     'employee_id' => 'CPS-01001',   // NIK
        //     'divisi' => 'Produksi',          // nama divisi resmi (lihat daftar di atas)
        //     'jabatan' => 'Operator Mesin',
        //     'role' => 'employee',
        // ],
    ];

    public function run(): void
    {
        $service = app(EmployeeAccountService::class);

        foreach ($this->employees as $employee) {
            if (User::where('email', $employee['email'])->exists()) {
                $this->command?->warn("Dilewati, email sudah terdaftar: {$employee['email']}");

                continue;
            }

            $divisionId = Division::reportable()->where('name', $employee['divisi'])->value('id')
                ?? throw new RuntimeException("Divisi tidak dikenal untuk {$employee['email']}: {$employee['divisi']}");

            $service->create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'employee_id' => $employee['employee_id'],
                'division_id' => $divisionId,
                'jabatan' => $employee['jabatan'] ?? null,
                'role' => $employee['role'],
            ]);

            $this->command?->info("Dibuat: {$employee['email']}");
        }
    }
}
