<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PivotReportExport implements FromArray, WithHeadings
{
    protected ?string $search;
    protected ?string $periode;
    protected ?string $dept;
    protected ?string $startDate; // 👈 Tambahkan ini
    protected ?string $endDate;
    protected ?string $subDept;
    protected ?string $pos;
    protected ?string $grade;
    protected ?string $skema;

    public function __construct(
        ?string $search = null,
        ?string $periode = null,
        ?string $startDate = null, // 👈 Tambahkan ini
        ?string $endDate = null,   
        ?string $dept = null,
        ?string $subDept = null,
        ?string $pos = null,
        ?string $grade = null,
        ?string $skema = null
    ) {
        $this->search  = $search;
        $this->periode = $periode;
        $this->startDate = $startDate; // 👈 Assign ini
        $this->endDate   = $endDate;
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

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if (!empty($this->dept)) {
            $dept = $this->dept;
            $query->whereHas('department', function ($q) use ($dept) {
                $q->where('name', $dept)->orWhere('id', $dept);
            });
        }

        if (!empty($this->subDept)) {
            $subDept = $this->subDept;
            $query->whereHas('subDepartment', function ($q) use ($subDept) {
                $q->where('name', $subDept)->orWhere('id', $subDept);
            });
        }

        if (!empty($this->pos)) {
            $pos = $this->pos;
            $query->whereHas('position', function ($q) use ($pos) {
                $q->where('name', $pos)->orWhere('title', $pos)->orWhere('id', $pos);
            });
        }

        if (!empty($this->grade)) {
            $grade = $this->grade;
            $query->whereHas('grade', function ($q) use ($grade) {
                $q->where('name', $grade)->orWhere('id', $grade);
            });
        }

        if (!empty($this->skema)) {
            $skema = $this->skema;
            $query->whereHas('workScheduleScheme', function ($q) use ($skema) {
                $q->where('name', $skema)->orWhere('code', $skema)->orWhere('id', $skema);
            });
        }

        if (!empty($this->skema)) {
            $skema = $this->skema;
            $query->whereHas('workScheduleScheme', function ($q) use ($skema) {
                $q->where('name', $skema)->orWhere('code', $skema)->orWhere('id', $skema);
            });
        }

       if (!empty($this->startDate) && !empty($this->endDate)) {
            $startDate = $this->startDate;
            $endDate = $this->endDate;
            $query->whereHas('attendanceLogDetails', function($q) use ($startDate, $endDate) {
                $q->whereDate('date', '>=', $startDate)
                  ->whereDate('date', '<=', $endDate);
            });
        }

        $employees = $query->get();
        $data = [];

        foreach ($employees as $emp) {
            $data[] = [
                $emp->employee_id ?? '-',
                $emp->nip ?? '-',
                $emp->full_name ?? '-',
                $emp->department?->name ?? '-',
                $emp->subDepartment?->name ?? '-',
                $emp->position?->title ?? $emp->position?->name ?? '-',
                $emp->grade?->name ?? '-',
                $emp->workScheduleScheme?->name ?? $emp->workScheduleScheme?->code ?? '5-2',
                22,
                8,
                1,
                0,
                0,
                0,
                0,
                0,
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