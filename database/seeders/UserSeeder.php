<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminDivision = Division::where('name', 'Warehouse & Delivery')->first();
        $produksiDivision = Division::where('name', 'Produksi')->first();
        $qcDivision = Division::where('name', 'Quality Control')->first();
        $engDivision = Division::where('name', 'Engineering')->first();

        $users = [
            [
                'name' => 'Admin CPS',
                'email' => 'admin@cps.test',
                'password' => Hash::make('password'),
                'employee_id' => 'CPS-00001',
                'division_id' => $adminDivision?->id,
                'jabatan' => 'IT Administrator',
                'role' => 'admin',
            ],
            [
                'name' => 'Supervisor CPS',
                'email' => 'supervisor@cps.test',
                'password' => Hash::make('password'),
                'employee_id' => 'CPS-00002',
                'division_id' => $produksiDivision?->id,
                'jabatan' => 'Production Supervisor',
                'role' => 'supervisor',
            ],
            [
                'name' => 'Quality CPS',
                'email' => 'quality@cps.test',
                'password' => Hash::make('password'),
                'employee_id' => 'CPS-00003',
                'division_id' => $qcDivision?->id,
                'jabatan' => 'Quality Inspector',
                'role' => 'quality',
            ],
            [
                'name' => 'Employee CPS',
                'email' => 'employee@cps.test',
                'password' => Hash::make('password'),
                'employee_id' => 'CPS-00004',
                'division_id' => $engDivision?->id,
                'jabatan' => 'Engineering Staff',
                'role' => 'employee',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                array_merge($userData, [
                    'email_verified_at' => now(),
                    'xp' => 0,
                ])
            );

            $user->syncRoles([$role]);
        }

        $this->seedDivisionSupervisors();
    }

    /**
     * Satu supervisor dummy per divisi resmi, supaya tahap approval Supervisor punya pelaksana
     * di setiap divisi. firstOrCreate: aman dijalankan ulang tanpa menimpa user yang sudah ada.
     */
    public function seedDivisionSupervisors(): void
    {
        $slugs = [
            'Produksi' => 'produksi',
            'PPIC' => 'ppic',
            'Warehouse & Delivery' => 'wd',
            'RM Warehouse' => 'rmw',
            'Engineering' => 'engineering',
            'Quality Control' => 'qc',
            'Jahit' => 'jahit',
            'Finance Accounting Tax' => 'fat',
            'Purchasing' => 'purchasing',
            'Marketing & Sales' => 'marketing',
            'Plant Balben & Krian' => 'plant',
        ];

        $number = 200;

        foreach ($slugs as $divisionName => $slug) {
            $number++;
            $division = Division::where('name', $divisionName)->first();

            if (! $division) {
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => "spv.{$slug}@cps.test"],
                [
                    'name' => "Supervisor {$divisionName}",
                    'password' => Hash::make('password'),
                    'employee_id' => "CPS-00{$number}",
                    'division_id' => $division->id,
                    'jabatan' => "Supervisor {$divisionName}",
                    'email_verified_at' => now(),
                    'xp' => 0,
                ]
            );

            $user->syncRoles(['supervisor']);
        }
    }
}
