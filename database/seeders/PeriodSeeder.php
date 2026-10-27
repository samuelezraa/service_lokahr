<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periods = [
            [
                'code'           => '220001',
                'name'           => 'September - Oktober 2026',
                'start_date'     => '2026-09-21',
                'end_date'       => '2026-10-20',
                'late_tolerance' => '00:15:00',
                'description'    => 'Generate Successfully',
                'is_closed'      => false,
                'is_active'      => true,
            ],
            [
                'code'           => '220002',
                'name'           => 'Oktober - November 2026',
                'start_date'     => '2026-10-21',
                'end_date'       => '2026-11-20',
                'late_tolerance' => '00:15:00',
                'description'    => 'Generate Successfully',
                'is_closed'      => false,
                'is_active'      => true,
            ],
        ];

        foreach ($periods as $period) {
            Period::updateOrCreate(
                ['code' => $period['code']],
                $period
            );
        }
    }
}