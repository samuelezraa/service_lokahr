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
        foreach ($array as $row) {
            $nip  = $row['nip'] ?? null;
            $nama = $row['nama'] ?? $row['nama_lengkap'] ?? $row['full_name'] ?? null;

            if (empty($nip) || empty($nama)) {
                continue;
            }

            $stringNip = (string) $nip;

            // Master data resolvers (Mendukung header Bahasa Indonesia tanpa underscore)
            $deptVal = $row['departemen'] ?? $row['department'] ?? null;
            $departmentId = is_numeric($deptVal) ? (int)$deptVal : (!empty($deptVal) ? Department::where('name', trim($deptVal))->value('id') : null);

            $subDeptVal = $row['sub_departemen'] ?? $row['sub_department'] ?? null;
            $subDepartmentId = is_numeric($subDeptVal) ? (int)$subDeptVal : (!empty($subDeptVal) ? SubDepartment::where('name', trim($subDeptVal))->value('id') : null);

            $posVal = $row['pos'] ?? $row['position'] ?? null;
            $positionId = is_numeric($posVal) ? (int)$posVal : (!empty($posVal) ? Position::where('title', trim($posVal))->value('id') : null);

            $gradeVal = $row['grade'] ?? null;
            $gradeId = is_numeric($gradeVal) ? (int)$gradeVal : (!empty($gradeVal) ? Grade::where('name', trim($gradeVal))->value('id') : null);

            $schemeVal = $row['skema_hari_kerja'] ?? $row['work_schedule_scheme'] ?? null;
            $schemeId = is_numeric($schemeVal) ? (int)$schemeVal : (!empty($schemeVal) ? WorkScheduleScheme::where('name', trim($schemeVal))->value('id') : null);

            // Parsing Tanggal
            $rawJoinDate  = $row['tanggal_bergabung'] ?? $row['join_date'] ?? null;
            $rawBirthDate = $row['tanggal_lahir'] ?? $row['birth_date'] ?? null;

            $joinDate  = $this->parseDate($rawJoinDate);
            $birthDate = $this->parseDate($rawBirthDate);

            // Hitung langsung dengan pembulatan ke bawah tanpa desimal
            $ageYears           = $birthDate ? (int) floor(Carbon::parse($birthDate)->diffInYears(Carbon::now())) : null;
            $workDurationMonths = $joinDate ? (int) floor(Carbon::parse($joinDate)->diffInMonths(Carbon::now())) : null;

            $rawPhone = $row['no_telepon'] ?? $row['telepon'] ?? $row['phone'] ?? null;
            $phone = !empty($rawPhone) ? Employee::phoneconvert((string) $rawPhone) : null;

            $empId = $row['id_karyawan'] ?? $row['employee_id'] ?? null;

            Employee::updateOrCreate(
                ['nip' => $stringNip],
                [
                    'employee_id'             => !empty($empId) ? (string) $empId : null,
                    'nip'                     => $stringNip,
                    'employee_password'       => Hash::make($stringNip),
                    'full_name'               => trim((string) $nama),
                    'email'                   => $row['email'] ?? null,
                    'phone'                   => $phone,
                    'department_id'           => $departmentId,
                    'sub_department_id'       => $subDepartmentId,
                    'position_id'             => $positionId,
                    'grade_id'                => $gradeId,
                    'work_schedule_scheme_id' => $schemeId,
                    'join_date'               => $joinDate,
                    'birth_date'              => $birthDate,
                    'age_years'               => $ageYears,
                    'work_duration_months'    => $workDurationMonths,
                    'is_active'               => isset($row['status']) ? (strtolower(trim((string) $row['status'])) === 'aktif' || $row['status'] == 1) : true,
                ]
            );
        }

        CalculateEmployeeMetricsJob::dispatch();
    }
}