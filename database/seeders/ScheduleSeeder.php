<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\Employee;
use App\Models\Period;
use Carbon\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $defaultDays = [
            ['day_of_week' => 'monday',    'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'tuesday',   'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'wednesday', 'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'thursday',  'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'friday',    'check_in_time' => '08:00:00', 'check_out_time' => '17:00:00', 'is_active' => true],
            ['day_of_week' => 'saturday',  'check_in_time' => '00:00:00', 'check_out_time' => '00:00:00', 'is_active' => false],
            ['day_of_week' => 'sunday',    'check_in_time' => '00:00:00', 'check_out_time' => '00:00:00', 'is_active' => false],
        ];

        $periods = Period::where('is_active', true)->get();
        if ($periods->isEmpty()) {
            $periods = collect([
                (object) [
                    'code'       => '220001',
                    'name'       => 'September - Oktober 2026',
                    'start_date' => '2026-09-21',
                    'end_date'   => '2026-10-20',
                ]
            ]);
        }

        $employees = Employee::with(['department', 'subDepartment'])->get();

        foreach ($periods as $period) {
            $startDate = Carbon::parse($period->start_date);
            $endDate   = Carbon::parse($period->end_date);

            // Hitung total hari, hari kerja, & hari libur sesuai rentang Master Period
            $totalDays = 0;
            $workDays  = 0;
            $offDays   = 0;
            $cursor    = $startDate->copy();

            while ($cursor->lte($endDate)) {
                $totalDays++;
                if ($cursor->isWeekend()) {
                    $offDays++;
                } else {
                    $workDays++;
                }
                $cursor->addDay();
            }

            foreach ($employees as $employee) {
                $schedule = Schedule::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period_code' => $period->code,
                    ],
                    [
                        'name'                => 'Jadwal Reguler ' . $period->name,
                        'nip' => $employee->nip ?? '-',
                        'employee_name'       => $employee->full_name ?? $employee->name,
                        'department_id'       => $employee->department_id,
                        'department_name'     => $employee->department?->name ?? '-',
                        'sub_department_id'   => $employee->sub_department_id,
                        'sub_department_name' => $employee->subDepartment?->name ?? '-',
                        'work_scheme'         => $employee->work_scheme ?? 'Skema 5-2',
                        'total_days'          => $totalDays,
                        'work_days'           => $workDays,
                        'off_days'            => $offDays,
                        'is_active'           => $employee->is_active ?? true,
                    ]
                );

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
}