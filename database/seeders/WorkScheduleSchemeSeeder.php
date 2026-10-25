<?php

namespace Database\Seeders;

use App\Models\WorkScheduleScheme;
use Illuminate\Database\Seeder;

class WorkScheduleSchemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schemes = [
            [
                'code'        => 'SK001',
                'name'        => 'Skema 5-2',
                'description' => '5 hari kerja, 2 hari libur (Sabtu & Minggu)',
                'is_active'   => true,
            ],
            [
                'code'        => 'SK002',
                'name'        => 'Skema 6-1',
                'description' => '6 hari kerja, 1 hari libur (Minggu)',
                'is_active'   => true,
            ],
            [
                'code'        => 'SK003',
                'name'        => 'Skema Shift 24/7',
                'description' => 'Sistem kerja bergilir 3 shift',
                'is_active'   => true,
            ],
        ];

        foreach ($schemes as $scheme) {
            WorkScheduleScheme::updateOrCreate(
                ['code' => $scheme['code']],
                $scheme
            );
        }
    }
}