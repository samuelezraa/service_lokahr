<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomWorkHour;

class CustomWorkHourSeeder extends Seeder
{
    public function run(): void
    {
        $customWorkHours = [
            [
                'code'        => 'CUS001',
                'start_date'  => '2026-12-15',
                'end_date'    => '2027-01-15',
                'work_scheme' => '5-2',
                'clock_in'    => '12:00:00',
                'tolerance'   => '00:05:00',
                'description' => 'Peak Season',
                'is_active'   => true,
            ],
            [
                'code'        => 'CUS002',
                'start_date'  => '2026-03-01',
                'end_date'    => '2026-03-31',
                'work_scheme' => '6-1',
                'clock_in'    => '08:30:00',
                'tolerance'   => '00:15:00',
                'description' => 'Penyesuaian Bulan Ramadhan',
                'is_active'   => false,
            ],
        ];

        foreach ($customWorkHours as $workHour) {
            CustomWorkHour::updateOrCreate(
                ['code' => $workHour['code']],
                $workHour
            );
        }
    }
}