<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubDepartemenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan nama tabel disesuaikan dengan migrasi backend kamu (sub_departments)
        DB::table('sub_departments')->insert([
            [
                'code'            => 'SUB001',
                'department_code' => 'DP001', // Sesuaikan dengan 'code' departemen yang ada di DepartemenSeeder
                'name'            => 'Frontend Engineering',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'code'            => 'SUB002',
                'department_code' => 'DP001',
                'name'            => 'Backend Engineering',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'code'            => 'SUB003',
                'department_code' => 'DP002',
                'name'            => 'Recruiting & Talent Acquisition',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'code'            => 'SUB004',
                'department_code' => 'DP002',
                'name'            => 'Payroll & Benefits',
                'is_active'       => false,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ]);
    }
}