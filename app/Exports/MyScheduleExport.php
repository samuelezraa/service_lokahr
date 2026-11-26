<?php

namespace App\Exports;

use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class MyScheduleExport implements FromArray, WithHeadings, ShouldAutoSize, WithCustomCsvSettings
{
    protected ?string $period;
    protected array $selectedIds;
    protected ?string $search;
    protected ?string $departmentId;
    protected ?string $subDepartment;
    protected ?string $grade;

    public function __construct(
        ?string $period = null,
        array $selectedIds = [],
        ?string $search = null,
        ?string $departmentId = null,
        ?string $subDepartment = null,
        ?string $grade = null
    ) {
        $this->period        = $period;
        $this->selectedIds   = array_filter($selectedIds);
        $this->search        = $search;
        $this->departmentId  = $departmentId;
        $this->subDepartment = $subDepartment;
        $this->grade         = $grade;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'use_bom'   => true,
        ];
    }

    public function array(): array
    {
        $query = Schedule::with(['employee.department', 'employee.subDepartment', 'employee.grade']);

        if (!empty($this->period)) {
            $query->where('period_code', $this->period);
        }

        if (!empty($this->departmentId)) {
            $deptId = $this->departmentId;
            $query->where(function ($q) use ($deptId) {
                $q->where('department_id', $deptId)
                  ->orWhereHas('employee', function ($empQ) use ($deptId) {
                      $empQ->where('department_id', $deptId);
                  });
            });
        }

        // Jika ada baris yang dicentang (selected), export baris yang dicentang saja
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        } else {
            // Jika tidak ada baris dicentang, export berdasarkan filter aktif
            if (!empty($this->search)) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('nip', 'like', "%{$search}%")
                      ->orWhere('employee_name', 'like', "%{$search}%")
                      ->orWhereHas('employee', function ($empQ) use ($search) {
                          $empQ->where('full_name', 'like', "%{$search}%")
                               ->orWhere('nip', 'like', "%{$search}%");
                      });
                });
            }

            if (!empty($this->subDepartment)) {
                $subDept = $this->subDepartment;
                $query->where(function ($q) use ($subDept) {
                    $q->where('sub_department_name', $subDept)
                      ->orWhereHas('subDepartment', function ($subQ) use ($subDept) {
                          $subQ->where('name', $subDept);
                      });
                });
            }

            if (!empty($this->grade)) {
                $grade = $this->grade;
                $query->whereHas('employee.grade', function ($q) use ($grade) {
                    $q->where('name', $grade);
                });
            }
        }

        $schedules = $query->latest()->get();
        $data = [];

        foreach ($schedules as $sch) {
            $data[] = [
                $sch->nip ?? $sch->employee?->nip ?? '-',
                $sch->employee_name ?? $sch->employee?->full_name ?? '-',
                $sch->department_name ?? $sch->department?->name ?? '-',
                $sch->sub_department_name ?? $sch->subDepartment?->name ?? '-',
                $sch->employee?->grade?->name ?? 'Officer',
                (int) $sch->off_days,
                (int) $sch->ph_days,
                (int) $sch->permit_days,
                (int) $sch->alpha_days,
                0, // sakit_days
                (int) $sch->leave_days,
                (int) $sch->late_days,
                (int) $sch->work_days,
                (int) $sch->total_days,
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Nama Karyawan',
            'Departemen',
            'Sub Departemen',
            'Grade',
            'Libur',
            'PH',
            'Izin',
            'Alfa',
            'Sakit',
            'Cuti',
            'Terlambat',
            'Masuk',
            'Total Hari'
        ];
    }
}