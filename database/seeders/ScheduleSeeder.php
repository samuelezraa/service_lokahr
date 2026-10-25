<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\ScheduleScheme;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Induk Jadwal (Schedule)
        $schedule = Schedule::create([
            'name' => 'Jadwal Reguler (Skema 5-2)',
            'is_active' => true,
        ]);

        // 2. Buat Rincian Jam Kerja 7 Hari (ScheduleDetail)
        $days = [
            [
                'day_of_week' => 'monday',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'is_day_off' => false,
            ],
            [
                'day_of_week' => 'tuesday',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'is_day_off' => false,
            ],
            [
                'day_of_week' => 'wednesday',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'is_day_off' => false,
            ],
            [
                'day_of_week' => 'thursday',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'is_day_off' => false,
            ],
            [
                'day_of_week' => 'friday',
                'check_in_time' => '08:00:00',
                'check_out_time' => '17:00:00',
                'is_day_off' => false,
            ],
            [
                'day_of_week' => 'saturday',
                'check_in_time' => '00:00:00',
                'check_out_time' => '00:00:00',
                'is_day_off' => true,
            ],
            [
                'day_of_week' => 'sunday',
                'check_in_time' => '00:00:00',
                'check_out_time' => '00:00:00',
                'is_day_off' => true,
            ],
        ];

        foreach ($days as $day) {
            ScheduleDetail::create([
                'schedule_id' => $schedule->id,
                'day_of_week' => $day['day_of_week'],
                'check_in_time' => $day['check_in_time'],
                'check_out_time' => $day['check_out_time'],
                'is_day_off' => $day['is_day_off'],
                'is_active' => true,
            ]);
        }

        // 3. Hubungkan Schedule dengan WorkScheduleScheme (ID = 1 / Skema 5-2)
        ScheduleScheme::create([
            'schedule_id' => $schedule->id,
            'work_schedule_scheme_id' => 1,
            'is_active' => true,
        ]);
    }
}