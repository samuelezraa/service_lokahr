<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CustomWorkHour;

class CustomWorkHourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customWorkHours = [
            [
                'name' => 'Jam Kerja Shift Pagi',
                'clock_in' => '08:00:00',
                'clock_out' => '17:00:00',
                'tolerance' => '00:15:00',
                'description' => 'Jam kerja normal reguler',
                'is_active' => true,
            ],
            [
                'name' => 'Jam Kerja Shift Malam',
                'clock_in' => '20:00:00',
                'clock_out' => '04:00:00',
                'tolerance' => '00:15:00',
                'description' => 'Jam kerja khusus divisi operasional malam',
                'is_active' => true,
            ],
            [
                'name' => 'Jam Kerja Bulan Ramadhan',
                'clock_in' => '08:30:00',
                'clock_out' => '15:30:00',
                'tolerance' => '00:15:00',
                'description' => 'Jam kerja penyesuaian khusus bulan puasa',
                'is_active' => false,
            ],
        ];

        foreach ($customWorkHours as $workHour) {
            CustomWorkHour::create($workHour);
        }
    }
}