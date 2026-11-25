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

            // 1. Validasi ID Karyawan
            $nip = trim((string)($row['nip'] ?? $row['id_karyawan'] ?? $row['id_karyawan_nip'] ?? $row['employee_id'] ?? ''));
            if ($nip === '') {
                throw new \Exception("Baris {$rowNum}: ID Karyawan wajib diisi.");
            }

            $employee = Employee::where('nip', $nip)
                ->orWhere('employee_id', $nip)
                ->first();

            if (!$employee) {
                throw new \Exception("Baris {$rowNum}: Karyawan dengan ID Karyawan '{$nip}' tidak ditemukan di database.");
            }

            // 2. Validasi Nama Karyawan
            $inputName = trim((string)($row['nama_karyawan'] ?? $row['employee_name'] ?? $row['name'] ?? ''));
            $dbEmployeeName = trim((string)($employee->full_name ?? $employee->name ?? ''));

            if ($inputName === '') {
                throw new \Exception("Baris {$rowNum}: Nama Karyawan wajib diisi.");
            }

            if ($dbEmployeeName === '') {
                throw new \Exception("Baris {$rowNum}: Data nama karyawan untuk ID '{$nip}' tidak ditemukan di database.");
            }

            if (mb_strtolower($inputName) !== mb_strtolower($dbEmployeeName)) {
                throw new \Exception("Baris {$rowNum}: Nama Karyawan '{$inputName}' tidak sesuai dengan data ID Karyawan '{$nip}' ({$dbEmployeeName}).");
            }

            // 3. Validasi Departemen
            $deptVal = trim((string)($row['departemen'] ?? $row['department'] ?? ''));
            if ($deptVal === '') {
                throw new \Exception("Baris {$rowNum}: Departemen wajib diisi.");
            }

            $deptObj = is_numeric($deptVal)
                ? Department::find($deptVal)
                : Department::whereRaw('LOWER(name) = ?', [mb_strtolower($deptVal)])->first();

            if (!$deptObj) {
                throw new \Exception("Baris {$rowNum}: Departemen '{$deptVal}' tidak ditemukan di database.");
            }

            $departmentId = $deptObj->id;
            $departmentName = $deptObj->name;

            // 4. Validasi Sub Departemen
            $subDeptVal = trim((string)($row['sub_departemen'] ?? $row['sub_department'] ?? ''));
            if ($subDeptVal === '') {
                throw new \Exception("Baris {$rowNum}: Sub Departemen wajib diisi.");
            }

            $subDeptObj = is_numeric($subDeptVal)
                ? SubDepartment::find($subDeptVal)
                : SubDepartment::whereRaw('LOWER(name) = ?', [mb_strtolower($subDeptVal)])->first();

            if (!$subDeptObj) {
                throw new \Exception("Baris {$rowNum}: Sub Departemen '{$subDeptVal}' tidak ditemukan di database.");
            }

            $subDepartmentId = $subDeptObj->id;
            $subDepartmentName = $subDeptObj->name;

            $statusRaw = $row['status'] ?? 'Aktif';
            $isActive = (strtolower((string)$statusRaw) === 'aktif' || $statusRaw == 1 || strtolower((string)$statusRaw) === 'true');

            // 1. Update/Create Schedule Induk
            $schedule = Schedule::updateOrCreate(
                ['employee_id' => $employee->id],
                [
                    'nip'                 => $nip,
                    'employee_name'       => $dbEmployeeName,
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