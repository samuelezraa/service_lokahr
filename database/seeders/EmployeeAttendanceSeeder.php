<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;

class EmployeeAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat data Rekap Absensi Karyawan utama (EmployeeAttendance) untuk periode Oktober 2026
        $attendance = EmployeeAttendance::create([
            'period_code'         => '2026-10',
            'nip'                 => '99-9999-99',
            'employee_name'       => 'User Trial IT',
            'department_name'     => 'IT',
            'sub_department_name' => 'Aplikasi & System',
            'pos_name'            => 'Trial',
            'grade_name'          => 'Officer',
            'work_scheme'         => '5-2',
            'total_off_days'      => 14,
            'total_ph_days'       => 2,
            'total_permit_days'   => 0,
            'total_alpha_days'    => 8,
            'total_sick_days'     => 0,
            'total_leave_days'    => 2,
            'total_late_days'     => 0,
            'total_work_days'     => 20,
            'total_period_days'   => 21,
        ]);

        // 2. Buat sample data rincian absensi harian (EmployeeAttendanceDetail)
        $details = [
            [
                'day_no'             => 1,
                'day_name'           => 'Kamis',
                'date'               => '2026-10-01',
                'attendance_status'  => 'Berangkat',
                'scheduled_check_in' => '09:00:00',
                'scheduled_check_out'=> '18:00:00',
                'actual_check_in'    => '08:55:00',
                'actual_check_out'   => '18:05:00',
                'status'             => 'Tepat Waktu',
                'notes'              => '-',
                'ref'                => '2026-10-01',
            ],
            [
                'day_no'             => 2,
                'day_name'           => 'Jumat',
                'date'               => '2026-10-02',
                'attendance_status'  => 'Libur',
                'scheduled_check_in' => '00:00:00',
                'scheduled_check_out'=> '00:00:00',
                'actual_check_in'    => '00:00:00',
                'actual_check_out'   => '00:00:00',
                'status'             => '-',
                'notes'              => 'Hari Libur',
                'ref'                => '2026-10-02',
            ],
            [
                'day_no'             => 3,
                'day_name'           => 'Sabtu',
                'date'               => '2026-10-03',
                'attendance_status'  => 'PH',
                'scheduled_check_in' => '00:00:00',
                'scheduled_check_out'=> '00:00:00',
                'actual_check_in'    => '00:00:00',
                'actual_check_out'   => '00:00:00',
                'status'             => '-',
                'notes'              => 'Public Holiday',
                'ref'                => '2026-10-03',
            ],
            [
                'day_no'             => 4,
                'day_name'           => 'Minggu',
                'date'               => '2026-10-04',
                'attendance_status'  => 'Alfa',
                'scheduled_check_in' => '00:00:00',
                'scheduled_check_out'=> '00:00:00',
                'actual_check_in'    => '00:00:00',
                'actual_check_out'   => '00:00:00',
                'status'             => 'Tanpa Keterangan',
                'notes'              => '-',
                'ref'                => '2026-10-04',
            ],
        ];

        foreach ($details as $detail) {
            EmployeeAttendanceDetail::create(array_merge($detail, [
                'employee_attendance_id' => $attendance->id,
            ]));
        }
    }
}