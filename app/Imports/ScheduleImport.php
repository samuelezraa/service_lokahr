<?php

namespace App\Imports;

use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class ScheduleImport implements ToArray, WithHeadingRow
{
    /**
     * Helper untuk mengubah waktu Excel (String / Float / DateTime) ke H:i:s
     */
    private function parseExcelTime($value): string
    {
        if (empty($value) || $value === '-' || $value === '0') {
            return '00:00:00';
        }

        try {
            // 1. Jika bertipe angka desimal khas Excel (misal 0.25 = 06:00:00)
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('H:i:s');
            }

            // 2. Jika bertipe string "6:11:00" atau "6:11", ubah ke format "06:11:00"
            return Carbon::parse((string)$value)->format('H:i:s');
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    public function array(array $array): void
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($array as $index => $row) {
            $rowNum = $index + 2;

            $nip = $row['nip'] ?? $row['id_karyawan_nip'] ?? $row['employee_id'] ?? null;
            if (empty($nip)) {
                continue;
            }

            $employee = Employee::where('nip', (string)$nip)->first();
            if (!$employee) {
                throw new \Exception("Baris {$rowNum}: Karyawan dengan NIP '{$nip}' tidak ditemukan di database.");
            }

            // Department Resolver
            $deptVal = $row['departemen'] ?? $row['department'] ?? null;
            $departmentId = $employee->department_id;
            $departmentName = $employee->department?->name;
            if (!empty($deptVal)) {
                $deptObj = is_numeric($deptVal) 
                    ? Department::find($deptVal) 
                    : Department::where('name', $deptVal)->first();
                if ($deptObj) {
                    $departmentId = $deptObj->id;
                    $departmentName = $deptObj->name;
                }
            }

            // Sub Department Resolver
            $subDeptVal = $row['sub_departemen'] ?? $row['sub_department'] ?? null;
            $subDepartmentId = $employee->sub_department_id;
            $subDepartmentName = $employee->subDepartment?->name;
            if (!empty($subDeptVal)) {
                $subDeptObj = is_numeric($subDeptVal) 
                    ? SubDepartment::find($subDeptVal) 
                    : SubDepartment::where('name', $subDeptVal)->first();
                if ($subDeptObj) {
                    $subDepartmentId = $subDeptObj->id;
                    $subDepartmentName = $subDeptObj->name;
                }
            }

            $statusRaw = $row['status'] ?? 'Aktif';
            $isActive = (strtolower((string)$statusRaw) === 'aktif' || $statusRaw == 1 || strtolower((string)$statusRaw) === 'true');

            // 1. Update/Create Schedule Induk
            $schedule = Schedule::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'nip'                 => (string)$nip,
                    'employee_name'       => $employee->full_name,
                    'department_id'       => $departmentId,
                    'department_name'     => $departmentName,
                    'sub_department_id'   => $subDepartmentId,
                    'sub_department_name' => $subDepartmentName,
                    'is_active'           => $isActive,
                ]
            );

            // 2. Update/Create Rincian Jam Kerja 7 Hari
            foreach ($days as $day) {
                $checkInKey = "{$day}_in";
                $checkOutKey = "{$day}_out";

                if (!isset($row[$checkInKey])) {
                    $indoDays = [
                        'monday' => 'senin', 'tuesday' => 'selasa', 'wednesday' => 'rabu',
                        'thursday' => 'kamis', 'friday' => 'jumat', 'saturday' => 'sabtu', 'sunday' => 'minggu'
                    ];
                    $checkInKey = $indoDays[$day] . '_masuk';
                    $checkOutKey = $indoDays[$day] . '_keluar';
                }

                $rawIn = $row[$checkInKey] ?? null;
                $rawOut = $row[$checkOutKey] ?? null;

                $checkIn = $this->parseExcelTime($rawIn);
                $checkOut = $this->parseExcelTime($rawOut);

                // Hari libur jika kedua jam 00:00:00 atau kosong
                $isDayOff = ($checkIn === '00:00:00' && $checkOut === '00:00:00');

                ScheduleDetail::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'check_in_time'  => $checkIn,
                        'check_out_time' => $checkOut,
                        'is_active'      => !$isDayOff,
                    ]
                );
            }
        }
    }
}