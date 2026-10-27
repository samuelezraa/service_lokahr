<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\ScheduleScheme;
use App\Models\Employee;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Induk / Master Template Jadwal (Schedule ID = 1)
        $masterSchedule = Schedule::create([
            'name'      => 'Jadwal Reguler (Skema 5-2)',
            'is_active' => true,
        ]);

        // 2. Buat Rincian Jam Kerja 7 Hari (ScheduleDetail)
        $days = [
            [
                'day_of_week'       => 'monday',
                'check_in_time'     => '08:00:00',
                'check_out_time'    => '17:00:00',
                'annual_event_name' => null,
            ],
            [
                'day_of_week'       => 'tuesday',
                'check_in_time'     => '08:00:00',
                'check_out_time'    => '17:00:00',
                'annual_event_name' => null,
            ],
            [
                'day_of_week'       => 'wednesday',
                'check_in_time'     => '08:00:00',
                'check_out_time'    => '17:00:00',
                'annual_event_name' => null,
            ],
            [
                'day_of_week'       => 'thursday',
                'check_in_time'     => '08:00:00',
                'check_out_time'    => '17:00:00',
                'annual_event_name' => null,
            ],
            [
                'day_of_week'       => 'friday',
                'check_in_time'     => '08:00:00',
                'check_out_time'    => '17:00:00',
                'annual_event_name' => null,
            ],
            [
                'day_of_week'       => 'saturday',
                'check_in_time'     => '00:00:00',
                'check_out_time'    => '00:00:00',
                'annual_event_name' => null,
            ],
            [
                'day_of_week'       => 'sunday',
                'check_in_time'     => '00:00:00',
                'check_out_time'    => '00:00:00',
                'annual_event_name' => null,
            ],
        ];

        foreach ($days as $day) {
            ScheduleDetail::create([
                'schedule_id'       => $masterSchedule->id,
                'day_of_week'       => $day['day_of_week'],
                'check_in_time'     => $day['check_in_time'],
                'check_out_time'    => $day['check_out_time'],
                'annual_event_name' => $day['annual_event_name'],
                'is_active'         => true,
            ]);
        }

        // 3. Hubungkan Master Schedule dengan WorkScheduleScheme (ID = 1 / Skema 5-2)
        ScheduleScheme::create([
            'schedule_id'             => $masterSchedule->id,
            'work_schedule_scheme_id' => 1,
            'is_active'               => true,
        ]);

        // 4. Generate Rekapitulasi Jadwal Per Karyawan untuk Periode 2026-10
        $employees = Employee::with(['department', 'subDepartment'])->get();

        foreach ($employees as $employee) {
            Schedule::create([
                'name'                => 'Jadwal Reguler Oktober 2026',
                'period_code'         => '2026-10',
                'employee_id'         => $employee->id,
                'nip'                 => $employee->nik ?? '-',
                'employee_name'       => $employee->full_name,
                'department_id'       => $employee->department_id,
                'department_name'     => $employee->department?->name ?? '-',
                'sub_department_id'   => $employee->sub_department_id,
                'sub_department_name' => $employee->subDepartment?->name ?? '-',
                'work_scheme'         => 'Skema 5-2',
                'total_days'          => 31,
                'work_days'           => 22,
                'off_days'            => 9,
                'ph_days'             => 1,
                'permit_days'         => 0,
                'alpha_days'          => 0,
                'sick_days'           => 0,
                'leave_days'          => 0,
                'late_days'           => 0,
                'ref'                 => null,
                'is_active'           => $employee->is_active ?? true,
            ]);
        }
    }
}