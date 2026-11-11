<?php

namespace App\Exports;

use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InputScheduleExport implements FromArray, WithHeadings
{
    protected ?string $period;
    protected ?string $search;
    protected ?string $departmentId;
    protected ?string $subDepartmentId;
    protected ?string $status;

    public function __construct(
        ?string $period = null,
        ?string $search = null,
        ?string $departmentId = null,
        ?string $subDepartmentId = null,
        ?string $status = null
    ) {
        $this->period = $period;
        $this->search = $search;
        $this->departmentId = $departmentId;
        $this->subDepartmentId = $subDepartmentId;
        $this->status = $status;
    }

    public function array(): array
    {
        $query = Schedule::with(['employee', 'department', 'subDepartment'])
            ->whereNotNull('employee_id');

        if (!empty($this->period)) {
            $query->where('period_code', $this->period);
        }

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (!empty($this->departmentId)) {
            $query->where('department_id', $this->departmentId);
        }

        if (!empty($this->subDepartmentId)) {
            $query->where('sub_department_id', $this->subDepartmentId);
        }

        if ($this->status !== null && $this->status !== '') {
            $boolStatus = filter_var($this->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $boolStatus);
        }

        $schedules = $query->get();
        $data = [];

        foreach ($schedules as $sch) {
            $data[] = [
                $sch->period_code ?? '-',
                $sch->nip ?? $sch->employee?->nip ?? '-',
                $sch->employee_name ?? $sch->employee?->full_name ?? '-',
                $sch->department_name ?? $sch->department?->name ?? '-',
                $sch->sub_department_name ?? $sch->subDepartment?->name ?? '-',
                $sch->work_scheme ?? 'Skema 5-2',
                $sch->is_active ? 'Aktif' : 'Nonaktif',
                (int) $sch->total_days,
                (int) $sch->work_days,
                (int) $sch->off_days,
                (int) $sch->ph_days,
                (int) $sch->permit_days,
                (int) $sch->alpha_days,
                (int) $sch->leave_days,
                (int) $sch->late_days,
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'ID Periode',
            'NIP / ID Karyawan',
            'Nama Karyawan',
            'Departemen',
            'Sub Departemen',
            'Skema Kerja',
            'Status',
            'Total Hari',
            'Masuk',
            'Libur',
            'PH',
            'Izin',
            'Alfa',
            'Cuti',
            'Terlambat',
        ];
    }
}