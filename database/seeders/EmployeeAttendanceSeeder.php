<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EmployeeAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $periods = Period::where('is_active', true)->get();
        if ($periods->isEmpty()) {
            $periods = collect([
                (object) [
                    'code'       => '2026-09',
                    'name'       => 'September 2026',
                    'start_date' => '2026-09-01',
                    'end_date'   => '2026-09-30',
                ],
                (object) [
                    'code'       => '2026-10',
                    'name'       => 'Oktober 2026',
                    'start_date' => '2026-10-01',
                    'end_date'   => '2026-10-31',
                ],
            ]);
        }

        $dayNames  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $employees = Employee::with(['department', 'subDepartment', 'position', 'grade'])->get();

        foreach ($periods as $period) {
            $periodCode = $period->code;
            $startDate  = Carbon::parse($period->start_date);
            $endDate    = Carbon::parse($period->end_date);

            foreach ($employees as $employee) {
                $nip = trim((string) ($employee->nip ?? $employee->employee_id ?? ''));
                if ($nip === '') {
                    continue;
                }

                $totalPeriodDays = 0;
                $totalWorkDays   = 0;
                $totalOffDays    = 0;
                $dailyRows       = [];

                $cursor = $startDate->copy();
                $dayNo  = 1;

                while ($cursor->lte($endDate)) {
                    $dateStr        = $cursor->format('Y-m-d');
                    $dayOfWeekIndex = (int) $cursor->format('w');
                    $dayName        = $dayNames[$dayOfWeekIndex];
                    $isWeekend      = ($dayOfWeekIndex === 0 || $dayOfWeekIndex === 6);

                    $totalPeriodDays++;
                    if ($isWeekend) {
                        $totalOffDays++;
                        $attendanceStatus  = 'Libur';
                        $scheduledCheckIn  = '00:00:00';
                        $scheduledCheckOut = '00:00:00';
                        $actualCheckIn     = '00:00:00';
                        $actualCheckOut    = '00:00:00';
                        $status            = '-';
                        $notes             = 'Hari Libur Akhir Pekan';
                    } else {
                        $totalWorkDays++;
                        $attendanceStatus  = 'Berangkat';
                        $scheduledCheckIn  = '09:00:00';
                        $scheduledCheckOut = '17:00:00';
                        $actualCheckIn     = '08:55:00';
                        $actualCheckOut    = '17:05:00';
                        $status            = 'Tepat Waktu';
                        $notes             = '-';
                    }

                    $dailyRows[] = [
                        'day_no'              => $dayNo,
                        'day_name'            => $dayName,
                        'date'                => $dateStr,
                        'attendance_status'   => $attendanceStatus,
                        'scheduled_check_in'  => $scheduledCheckIn,
                        'scheduled_check_out' => $scheduledCheckOut,
                        'actual_check_in'     => $actualCheckIn,
                        'actual_check_out'    => $actualCheckOut,
                        'status'              => $status,
                        'notes'               => $notes,
                        'ref'                 => $dateStr,
                    ];

                    $cursor->addDay();
                    $dayNo++;
                }

                $attendance = EmployeeAttendance::updateOrCreate(
                    [
                        'period_code' => $periodCode,
                        'nip'         => $nip,
                    ],
                    [
                        'employee_id'         => $employee->employee_id ?? null,
                        'employee_name'       => $employee->full_name ?? $employee->name ?? 'Karyawan',
                        'department_name'     => $employee->department?->name ?? '-',
                        'sub_department_name' => $employee->subDepartment?->name ?? '-',
                        'pos_name'            => $employee->position?->title ?? $employee->position?->name ?? '-',
                        'grade_name'          => $employee->grade?->name ?? '-',
                        'work_scheme'         => $employee->work_scheme ?? '5-2',
                        'total_off_days'      => $totalOffDays,
                        'total_ph_days'       => 0,
                        'total_permit_days'   => 0,
                        'total_alpha_days'    => 0,
                        'total_sick_days'     => 0,
                        'total_leave_days'    => 0,
                        'total_late_days'     => 0,
                        'total_work_days'     => $totalWorkDays,
                        'total_period_days'   => $totalPeriodDays,
                    ]
                );

                EmployeeAttendanceDetail::where('employee_attendance_id', $attendance->id)->delete();

                foreach ($dailyRows as $row) {
                    EmployeeAttendanceDetail::create(array_merge($row, [
                        'employee_attendance_id' => $attendance->id,
                    ]));
                }
            }
        }
    }
}