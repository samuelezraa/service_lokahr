<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PivotReportExport implements FromArray, WithHeadings
{
    protected ?string $search;
    protected ?string $dept;
    protected ?string $subDept;
    protected ?string $pos;
    protected ?string $grade;
    protected ?string $skema;

    public function __construct(
        ?string $search = null,
        ?string $dept = null,
        ?string $subDept = null,
        ?string $pos = null,
        ?string $grade = null,
        ?string $skema = null
    ) {
        $this->search  = $search;
        $this->dept    = $dept;
        $this->subDept = $subDept;
        $this->pos     = $pos;
        $this->grade   = $grade;
        $this->skema   = $skema;
    }

    public function array(): array
    {
        $query = Employee::with([
            'department',
            'subDepartment',
            'position',
            'grade',
            'workScheduleScheme'
        ])->where('is_active', true);

        // 1. Search Global
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // 2. Filter Departemen
        if (!empty($this->dept)) {
            $dept = $this->dept;
            $query->whereHas('department', function ($q) use ($dept) {
                $q->where('name', $dept)->orWhere('id', $dept);
            });
        }

        // 3. Filter Sub Departemen
        if (!empty($this->subDept)) {
            $subDept = $this->subDept;
            $query->whereHas('subDepartment', function ($q) use ($subDept) {
                $q->where('name', $subDept)->orWhere('id', $subDept);
            });
        }

        // 4. Filter POS
        if (!empty($this->pos)) {
            $pos = $this->pos;
            $query->whereHas('position', function ($q) use ($pos) {
                $q->where('name', $pos)->orWhere('title', $pos)->orWhere('id', $pos);
            });
        }

        // 5. Filter Grade
        if (!empty($this->grade)) {
            $grade = $this->grade;
            $query->whereHas('grade', function ($q) use ($grade) {
                $q->where('name', $grade)->orWhere('id', $grade);
            });
        }

        // 6. Filter Skema
        if (!empty($this->skema)) {
            $skema = $this->skema;
            $query->whereHas('workScheduleScheme', function ($q) use ($skema) {
                $q->where('name', $skema)->orWhere('code', $skema)->orWhere('id', $skema);
            });
        }

        $employees = $query->get();
        $data = [];

        foreach ($employees as $emp) {
            $data[] = [
                $emp->employee_id ?? '-', // 👈 ID Karyawan Kustom (EMP-001)
                $emp->nip ?? '-',         // 👈 NIP Karyawan
                $emp->full_name ?? '-',
                $emp->department?->name ?? '-',
                $emp->subDepartment?->name ?? '-',
                $emp->position?->title ?? $emp->position?->name ?? '-',
                $emp->grade?->name ?? '-',
                $emp->workScheduleScheme?->name ?? $emp->workScheduleScheme?->code ?? '5-2',
                22, // masuk
                8,  // libur
                1,  // ph
                0,  // izin
                0,  // alfa
                0,  // sakit
                0,  // cuti
                0,  // terlambat
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'ID Karyawan',
            'NIP',
            'Nama Karyawan',
            'Departemen',
            'Sub Departemen',
            'POS',
            'Grade',
            'Skema',
            'Masuk',
            'Libur',
            'PH',
            'Izin',
            'Alfa',
            'Sakit',
            'Cuti',
            'Terlambat',
        ];
    }
}