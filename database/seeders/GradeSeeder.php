<?php

namespace Database\Seeders;

use App\Models\Grade;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            [
                'code'      => 'GR001',
                'name'      => 'Staff / Junior',
                'is_active' => true,
            ],
            [
                'code'      => 'GR002',
                'name'      => 'Senior Staff',
                'is_active' => true,
            ],
            [
                'code'      => 'GR003',
                'name'      => 'Supervisor',
                'is_active' => true,
            ],
            [
                'code'      => 'GR004',
                'name'      => 'Manager',
                'is_active' => true,
            ],
            [
                'code'      => 'GR005',
                'name'      => 'General Manager',
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