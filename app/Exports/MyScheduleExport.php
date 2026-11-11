<?php

namespace App\Exports;

use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MyScheduleExport implements FromArray, WithHeadings
{
    protected ?string $period;
    protected array $selectedIds;

    public function __construct(?string $period = null, array $selectedIds = [])
    {
        $this->period = $period;
        $this->selectedIds = array_filter($selectedIds);
    }

    public function array(): array
    {
        $query = Schedule::with(['employee.department', 'employee.subDepartment', 'employee.grade']);

        if (!empty($this->period)) {
            $query->where('period_code', $this->period);
        }

        // Jika ada baris yang dicentang (selected), export yang dipilih saja
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        $schedules = $query->get();
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