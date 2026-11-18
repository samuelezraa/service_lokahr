<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;

class EmployeeAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // 🟢 Daftar periode yang ingin di-generate datanya
        $periods = [
            ['code' => '2026-09', 'year' => 2026, 'month' => 9],
            ['code' => '2026-10', 'year' => 2026, 'month' => 10],
        ];

        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $employees = Employee::with(['department', 'subDepartment', 'position', 'grade'])->get();

        foreach ($periods as $p) {
            $periodCode = $p['code'];
            $year = $p['year'];
            $month = $p['month'];
            $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            foreach ($employees as $employee) {
                $nip = trim($employee->nip ?? $employee->employee_id);
                if (empty($nip)) continue;

                $attendance = EmployeeAttendance::updateOrCreate(
                    [
                        'period_code' => $periodCode,
                        'nip'         => $nip,
                    ],
                    [
                        'employee_id'         => $employee->employee_id ?? null,
                        'employee_name'       => $employee->full_name ?? $employee->name ?? 'Karyawan',
                        'department_name'     => $employee->department?->name ?? 'IT',
                        'sub_department_name' => $employee->subDepartment?->name ?? 'Aplikasi & System',
                        'pos_name'            => $employee->position?->title ?? $employee->position?->name ?? 'Staff',
                        'grade_name'          => $employee->grade?->name ?? 'Officer',
                        'work_scheme'         => '5-2',
                        'total_off_days'      => 8,
                        'total_ph_days'       => 1,
                        'total_permit_days'   => 0,
                        'total_alpha_days'    => 0,
                        'total_sick_days'     => 0,
                        'total_leave_days'    => 0,
                        'total_late_days'     => 0,
                        'total_work_days'     => $totalDaysInMonth - 9,
                        'total_period_days'   => $totalDaysInMonth,
                    ]
                );

                EmployeeAttendanceDetail::where('employee_attendance_id', $attendance->id)->delete();

                for ($i = 1; $i <= $totalDaysInMonth; $i++) {
                    $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $i);
                    $timestamp = strtotime($dateStr);
                    $dayOfWeekIndex = date('w', $timestamp);
                    $dayName = $dayNames[$dayOfWeekIndex];

                    $isWeekend = ($dayOfWeekIndex == 0 || $dayOfWeekIndex == 6);

                    if ($isWeekend) {
                        $attendanceStatus = 'Libur';
                        $scheduledCheckIn = '00:00:00';
                        $scheduledCheckOut = '00:00:00';
                        $actualCheckIn = '00:00:00';
                        $actualCheckOut = '00:00:00';
                        $status = '-';
                        $notes = 'Hari Libur Akhir Pekan';
                    } else {
                        $attendanceStatus = 'Berangkat';
                        $scheduledCheckIn = '09:00:00';
                        $scheduledCheckOut = '17:00:00';
                        $actualCheckIn = '08:55:00';
                        $actualCheckOut = '17:05:00';
                        $status = 'Tepat Waktu';
                        $notes = '-';
                    }

                    EmployeeAttendanceDetail::create([
                        'employee_attendance_id' => $attendance->id,
                        'day_no'                 => $i,
                        'day_name'               => $dayName,
                        'date'                   => $dateStr,
                        'attendance_status'      => $attendanceStatus,
                        'scheduled_check_in'     => $scheduledCheckIn,
                        'scheduled_check_out'    => $scheduledCheckOut,
                        'actual_check_in'        => $actualCheckIn,
                        'actual_check_out'       => $actualCheckOut,
                        'status'                 => $status,
                        'notes'                  => $notes,
                        'ref'                    => $dateStr,
                    ]);
                }
            }
        }
    }
}