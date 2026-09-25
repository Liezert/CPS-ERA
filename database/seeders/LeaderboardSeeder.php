<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LeaderboardSeeder extends Seeder
{
    /**
     * Seed database dengan data peringkat pegawai bervariasi poin dan divisi.
     */
    public function run(): void
    {
        $divisions = Division::all()->keyBy('name');

        $employeesData = [
            [
                'name' => 'Bambang Trianto',
                'email' => 'bambang.trianto@cps.test',
                'employee_id' => 'CPS-00101',
                'division' => 'Produksi',
                'jabatan' => 'Senior Technician Injeksi',
                'points' => 2450,
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@cps.test',
                'employee_id' => 'CPS-00102',
                'division' => 'Quality Control',
                'jabatan' => 'Senior QA Specialist',
                'points' => 2180,
            ],
            [
                'name' => 'Rian Hidayat',
                'email' => 'rian.hidayat@cps.test',
                'employee_id' => 'CPS-00103',
                'division' => 'Engineering',
                'jabatan' => 'Automation Engineer',
                'points' => 1950,
            ],
            [
                'name' => 'Agus Prasetyo',
                'email' => 'agus.prasetyo@cps.test',
                'employee_id' => 'CPS-00104',
                'division' => 'Produksi',
                'jabatan' => 'Machine Operator 1',
                'points' => 1620,
            ],
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi.lestari@cps.test',
                'employee_id' => 'CPS-00105',
                'division' => 'PPIC',
                'jabatan' => 'Production Planner',
                'points' => 1340,
            ],
            [
                'name' => 'Hendra Gunawan',
                'email' => 'hendra.gunawan@cps.test',
                'employee_id' => 'CPS-00106',
                'division' => 'RM Warehouse',
                'jabatan' => 'Dies Repair Specialist',
                'points' => 1120,
            ],
            [
                'name' => 'Fajar Sidik',
                'email' => 'fajar.sidik@cps.test',
                'employee_id' => 'CPS-00107',
                'division' => 'RM Warehouse',
                'jabatan' => 'Material Handling Lead',
                'points' => 870,
            ],
            [
                'name' => 'Maya Anggraeni',
                'email' => 'maya.anggraeni@cps.test',
                'employee_id' => 'CPS-00108',
                'division' => 'HRGA',
                'jabatan' => 'Safety & Training Officer',
                'points' => 640,
            ],
            [
                'name' => 'Eko Wahyudi',
                'email' => 'eko.wahyudi@cps.test',
                'employee_id' => 'CPS-00109',
                'division' => 'Warehouse & Delivery',
                'jabatan' => 'Logistics Dispatcher',
                'points' => 450,
            ],
            [
                'name' => 'Rina Kartika',
                'email' => 'rina.kartika@cps.test',
                'employee_id' => 'CPS-00110',
                'division' => 'Purchasing',
                'jabatan' => 'Procurement Staff',
                'points' => 280,
            ],
            [
                'name' => 'Doni Kusuma',
                'email' => 'doni.kusuma@cps.test',
                'employee_id' => 'CPS-00111',
                'division' => 'Marketing & Sales',
                'jabatan' => 'System Support Specialist',
                'points' => 190,
            ],
            [
                'name' => 'Yudi Pratama',
                'email' => 'yudi.pratama@cps.test',
                'employee_id' => 'CPS-00112',
                'division' => 'Engineering',
                'jabatan' => 'Security Patrol Officer',
                'points' => 110,
            ],
        ];

        foreach ($employeesData as $emp) {
            $division = $divisions->get($emp['division']);

            $user = User::updateOrCreate(
                ['email' => $emp['email']],
                [
                    'name' => $emp['name'],
                    'password' => Hash::make('password'),
                    'employee_id' => $emp['employee_id'],
                    'division_id' => $division?->id,
                    'jabatan' => $emp['jabatan'],
                    'xp' => $emp['points'],
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles(['employee']);
        }
    }
}
