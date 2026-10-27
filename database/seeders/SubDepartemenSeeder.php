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
        // Menggunakan upsert agar aman dari error duplikasi jika seed dijalankan ulang
        DB::table('sub_departments')->upsert([
            [
                'code'            => 'SUB001',
                'department_code' => 'DP001', 
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
        ], ['code'], ['department_code', 'name', 'is_active', 'updated_at']);
    }
}