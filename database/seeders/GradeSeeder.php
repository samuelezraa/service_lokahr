<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            [
                'code'      => 'GR001',
                'name'      => 'Staff / Junior',
                'level'     => 1,
                'is_active' => true,
            ],
            [
                'code'      => 'GR002',
                'name'      => 'Senior Staff',
                'level'     => 2,
                'is_active' => true,
            ],
            [
                'code'      => 'GR003',
                'name'      => 'Supervisor',
                'level'     => 3,
                'is_active' => true,
            ],
            [
                'code'      => 'GR004',
                'name'      => 'Manager',
                'level'     => 4,
                'is_active' => true,
            ],
            [
                'code'      => 'GR005',
                'name'      => 'General Manager',
                'level'     => 5,
                'is_active' => false,
            ],
        ];

        foreach ($grades as $grade) {
            Grade::updateOrCreate(
                ['code' => $grade['code']],
                $grade
            );
        }
    }
}