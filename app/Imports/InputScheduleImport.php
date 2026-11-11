<?php

namespace App\Imports;

use App\Models\Schedule;
use App\Models\Employee;
use App\Models\Department;
use App\Models\SubDepartment;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InputScheduleImport implements ToArray, WithHeadingRow
{
    protected ?string $overridePeriod;

    public function __construct(?string $overridePeriod = null)
    {
        $this->overridePeriod = $overridePeriod;
    }

    public function array(array $array): void
    {
        foreach ($array as $row) {
            $nip = $row['nip'] ?? $row['id_karyawan'] ?? $row['nip_id_karyawan'] ?? null;
            $nama = $row['employee_name'] ?? $row['nama_karyawan'] ?? null;

            if (empty($nip)) {
                continue;
            }

            $stringNip = (string) $nip;
            $employee = Employee::where('nip', $stringNip)->first();

            $periodCode = $this->overridePeriod ?? ($row['period_code'] ?? $row['id_periode'] ?? date('Y-m'));

            $deptVal = $row['department'] ?? $row['departemen'] ?? null;
            $departmentId = $employee?->department_id;
            if (!empty($deptVal)) {
                $departmentId = is_numeric($deptVal) ? (int)$deptVal : Department::where('name', $deptVal)->value('id') ?? $departmentId;
            }

            $subDeptVal = $row['sub_department'] ?? $row['sub_departemen'] ?? null;
            $subDepartmentId = $employee?->sub_department_id;
            if (!empty($subDeptVal)) {
                $subDepartmentId = is_numeric($subDeptVal) ? (int)$subDeptVal : SubDepartment::where('name', $subDeptVal)->value('id') ?? $subDepartmentId;
            }

            $statusRaw = $row['status'] ?? 'Aktif';
            $isActive = (strtolower((string)$statusRaw) === 'aktif' || $statusRaw == 1);

            Schedule::updateOrCreate(
                [
                    'period_code' => $periodCode,
                    'nip'         => $stringNip,
                ],
                [
                    'employee_id'         => $employee?->id,
                    'employee_name'       => $nama ?? $employee?->full_name,
                    'department_id'       => $departmentId,
                    'department_name'     => $deptVal ?? $employee?->department?->name,
                    'sub_department_id'   => $subDepartmentId,
                    'sub_department_name' => $subDeptVal ?? $employee?->subDepartment?->name,
                    'work_scheme'         => $row['work_scheme'] ?? $row['skema_kerja'] ?? 'Skema 5-2',
                    'is_active'           => $isActive,
                    'total_days'          => (int) ($row['total_days'] ?? $row['total_hari'] ?? 31),
                    'work_days'           => (int) ($row['work_days'] ?? $row['masuk'] ?? 0),
                    'off_days'            => (int) ($row['off_days'] ?? $row['libur'] ?? 0),
                    'ph_days'             => (int) ($row['ph_days'] ?? $row['ph'] ?? 0),
                    'permit_days'         => (int) ($row['permit_days'] ?? $row['izin'] ?? 0),
                    'alpha_days'          => (int) ($row['alpha_days'] ?? $row['alfa'] ?? 0),
                    'leave_days'          => (int) ($row['leave_days'] ?? $row['cuti'] ?? 0),
                    'late_days'           => (int) ($row['late_days'] ?? $row['terlambat'] ?? 0),
                ]
            );
        }
    }
}