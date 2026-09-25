<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 11 divisi final dari client (Marketing & Sales digabung, keputusan owner 2026-09-24) + HRGA. HRGA bukan divisi pelapor CAPA
        // (lihat Division::HRGA), tetapi tetap ada untuk divisi di profil user HRGA.
        $divisions = [
            'Engineering',
            'Finance Accounting Tax',
            'HRGA',
            'Jahit',
            'Marketing & Sales',
            'PPIC',
            'Plant Balben & Krian',
            'Produksi',
            'Purchasing',
            'Quality Control',
            'RM Warehouse',
            'Warehouse & Delivery',
        ];

        foreach ($divisions as $name) {
            Division::firstOrCreate(['name' => $name]);
        }
    }
}
