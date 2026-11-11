<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Position;
use App\Models\Grade;
use App\Models\WorkScheduleScheme;
use App\Jobs\CalculateEmployeeMetricsJob;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements ToArray, WithHeadingRow
{
    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
            }

            $cleanDate = str_replace('/', '-', trim((string)$value));

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $cleanDate)) {
                return $cleanDate;
            }

            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $cleanDate)) {
                return Carbon::createFromFormat('d-m-Y', $cleanDate)->format('Y-m-d');
            }

            return Carbon::parse($cleanDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function array(array $array): void
    {
        foreach ($array as $index => $row) {
            $nip = $row['nip'] ?? null;
            $nama = $row['nama_lengkap'] ?? $row['full_name'] ?? null;

            if (empty($nip) || empty($nama)) {
                continue;
            }

            $stringNip = (string) $nip;

            // Master data resolvers
            $deptVal = $row['department'] ?? $row['departemen'] ?? $row['department_id'] ?? null;
            $departmentId = is_numeric($deptVal) ? (int)$deptVal : (!empty($deptVal) ? Department::where('name', $deptVal)->value('id') : null);

            $subDeptVal = $row['sub_department'] ?? $row['sub_departemen'] ?? $row['sub_department_id'] ?? null;
            $subDepartmentId = is_numeric($subDeptVal) ? (int)$subDeptVal : (!empty($subDeptVal) ? SubDepartment::where('name', $subDeptVal)->value('id') : null);

            $posVal = $row['position'] ?? $row['pos'] ?? $row['position_id'] ?? null;
            $positionId = is_numeric($posVal) ? (int)$posVal : (!empty($posVal) ? Position::where('title', $posVal)->value('id') : null);

            $gradeVal = $row['grade'] ?? $row['grade_id'] ?? null;
            $gradeId = is_numeric($gradeVal) ? (int)$gradeVal : (!empty($gradeVal) ? Grade::where('name', $gradeVal)->value('id') : null);

            $schemeVal = $row['work_schedule_scheme'] ?? $row['skema_hari_kerja'] ?? $row['work_schedule_scheme_id'] ?? null;
            $schemeId = is_numeric($schemeVal) ? (int)$schemeVal : (!empty($schemeVal) ? WorkScheduleScheme::where('name', $schemeVal)->value('id') : null);

            // Parsing Tanggal
            $rawJoinDate = $row['tanggal_bergabung'] ?? $row['join_date'] ?? null;
            $rawBirthDate = $row['tanggal_lahir'] ?? $row['birth_date'] ?? null;

            $joinDate = $this->parseDate($rawJoinDate);
            $birthDate = $this->parseDate($rawBirthDate);

            // 💡 HITUNG LANGSUNG UMUR & LAMA BEKERJA
            $ageYears = $birthDate ? Carbon::parse($birthDate)->age : null;
            $workDurationMonths = $joinDate ? Carbon::parse($joinDate)->diffInMonths(Carbon::now()) : null;

            $phone = !empty($row['telepon'] ?? $row['phone']) 
                ? Employee::phoneconvert($row['telepon'] ?? $row['phone']) 
                : null;

            Employee::updateOrCreate(
                ['nip' => $stringNip],
                [
                    'employee_id'             => $row['employee_id'] ?? null,
                    'nip'                     => $stringNip,
                    'employee_password'       => Hash::make($stringNip),
                    'full_name'               => $nama,
                    'email'                   => $row['email'] ?? null,
                    'phone'                   => $phone,
                    'department_id'           => $departmentId,
                    'sub_department_id'       => $subDepartmentId,
                    'position_id'             => $positionId,
                    'grade_id'                => $gradeId,
                    'work_schedule_scheme_id' => $schemeId,
                    'join_date'               => $joinDate,
                    'birth_date'              => $birthDate,
                    'age_years'               => $ageYears,            // 👈 LANGSUNG TERISI IN-MEMORY
                    'work_duration_months'    => $workDurationMonths,   // 👈 LANGSUNG TERISI IN-MEMORY
                    'is_active'               => isset($row['status']) ? (strtolower($row['status']) === 'aktif' || $row['status'] == 1) : true,
                ]
            );
        }

        CalculateEmployeeMetricsJob::dispatch();
    }
}