<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\ScheduleScheme;
use App\Models\Employee;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Template Hari Standar Skema 5-2
        $defaultDays = [
            ['day_of_week' => 'monday',    'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'tuesday',   'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'wednesday', 'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'thursday',  'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'friday',    'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'saturday',  'check_in_time' => '00:00:00', 'check_out_time' => '00:00:00', 'is_active' => false],
            ['day_of_week' => 'sunday',    'check_in_time' => '00:00:00', 'check_out_time' => '00:00:00', 'is_active' => false],
        ];

        // 2. Loop Semua Karyawan Real & Buat Schedule Beserta ScheduleDetail Per Karyawan
        $employees = Employee::with(['department', 'subDepartment'])->get();

        foreach ($employees as $employee) {
            $schedule = Schedule::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'name'                => 'Jadwal Reguler Oktober 2026',
                    'period_code'         => '2026-10',
                    'nip'                 => $employee->nip ?? $employee->employee_id ?? '-',
                    'employee_name'       => $employee->full_name ?? $employee->name,
                    'department_id'       => $employee->department_id,
                    'department_name'     => $employee->department?->name ?? '-',
                    'sub_department_id'   => $employee->sub_department_id,
                    'sub_department_name' => $employee->subDepartment?->name ?? '-',
                    'work_scheme'         => 'Skema 5-2',
                    'total_days'          => 31,
                    'work_days'           => 22,
                    'off_days'            => 9,
                    'is_active'           => $employee->is_active ?? true,
                ]
            );

            // 💡 Buat/Update Rincian 7 Hari untuk Setiap Karyawan
            foreach ($defaultDays as $day) {
                ScheduleDetail::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'day_of_week' => $day['day_of_week'],
                    ],
                    [
                        'check_in_time'  => $day['check_in_time'],
                        'check_out_time' => $day['check_out_time'],
                        'is_active'      => $day['is_active'],
                    ]
                );
            }
        }
    }
}