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
        $log = AttendanceLog::firstOrCreate(
            ['date' => '2026-09-30'],
            [
                'machine_ip'   => '192.168.1.201',
                'payload_data' => ['device' => 'Face Recognition Senayan']
            ]
        );

        $employees = Employee::whereIn('nik', ['1001', '1002', '1003'])->get();

        foreach ($employees as $employee) {
            AttendanceLogDetail::updateOrCreate(
                [
                    'nik'  => $employee->nik,
                    'date' => '2026-09-30',
                ],
                [
                    'attendance_log_id' => $log->id,
                    'department_id'     => $employee->department_id,
                    'department_name'   => $employee->department?->name ?? 'IT',
                    'employee_name'     => $employee->full_name,
                    'attendance_time'   => '2026-09-30 08:00:00',
                    'attendance_type'   => 'in',
                ]
            );
        }
    }
}