<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceLog;
use App\Models\AttendanceLogDetail;
use App\Models\Employee;

class AttendanceLogDetailSeeder extends Seeder
{
    public function run(): void
    {
        // 🟢 Daftar periode bulan yang ingin di-generate log kehadirannya
        $periods = [
            ['year' => 2026, 'month' => 9],  // September 2026
            ['year' => 2026, 'month' => 10], // Oktober 2026
        ];

        $employees = Employee::with('department')->where('is_active', true)->get();

        foreach ($periods as $p) {
            $year = $p['year'];
            $month = $p['month'];
            $totalDays = cal_days_in_month(CAL_GREGORIAN, $month, $year);

            for ($day = 1; $day <= $totalDays; $day++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

                // Lewati akhir pekan (Sabtu & Minggu) jika ingin opsional, atau biarkan terisi
                $dayOfWeek = date('w', strtotime($dateStr));
                if ($dayOfWeek == 0 || $dayOfWeek == 6) {
                    continue; 
                }

                $log = AttendanceLog::firstOrCreate(
                    ['date' => $dateStr],
                    [
                        'machine_ip'   => '192.168.1.201',
                        'payload_data' => ['device' => 'Face Recognition Senayan']
                    ]
                );

                foreach ($employees as $employee) {
                    AttendanceLogDetail::updateOrCreate(
                        [
                            'nip'  => $employee->nip,
                            'date' => $dateStr,
                        ],
                        [
                            'attendance_log_id' => $log->id,
                            'department_id'     => $employee->department_id,
                            'department_name'   => $employee->department?->name ?? 'IT',
                            'employee_name'     => $employee->full_name,
                            'attendance_time'   => $dateStr . ' 08:00:00',
                            'attendance_type'   => 'in',
                        ]
                    );
                }
            }
        }
    }
}