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
        $itDivision = Division::where('name', 'IT')->first();
        $produksiDivision = Division::where('name', 'Produksi')->first();
        $qcDivision = Division::where('name', 'Quality Control')->first();
        $engDivision = Division::where('name', 'Engineering')->first();

        $users = [
            [
                'name' => 'Admin CPS',
                'email' => 'admin@cps.test',
                'password' => Hash::make('password'),
                'employee_id' => 'CPS-00001',
                'division_id' => $itDivision?->id,
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
                    'total_points' => 0,
                    'level' => 1,
                ])
            );

            $user->syncRoles([$role]);
        }
    }
}
