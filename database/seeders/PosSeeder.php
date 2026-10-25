<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            [
                'code' => 'POS01',
                'title' => 'Programmer Officer',
                'department_code' => 'DP001', // Kode Departemen IT
                'sub_department_code' => 'SUB001', // Sesuaikan dengan kode/ID Sub Departemen di database
                'is_active' => true,
            ],
            [
                'code' => 'POS02',
                'title' => 'Network Engineer',
                'department_code' => 'DP001',
                'sub_department_code' => 'SUB002',
                'is_active' => true,
            ],
            [
                'code' => 'POS03',
                'title' => 'Talent Acquisition Staff',
                'department_code' => 'DP002',
                'sub_department_code' => 'SUB003',
                'is_active' => false,
            ],
            [
                'code' => 'POS04',
                'title' => 'Payroll & Benefits Specialist',
                'department_code' => 'DP002',
                'sub_department_code' => 'SUB004',
                'is_active' => true,
            ],
        ];

        foreach ($positions as $pos) {
            Position::updateOrCreate(
                ['code' => $pos['code']],
                $pos
            );
        }
    }
}