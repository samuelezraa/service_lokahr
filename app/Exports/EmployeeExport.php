<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeExport implements FromArray, WithHeadings
{
    protected array $selectedIds;

    public function __construct(array $selectedIds = [])
    {
        $this->selectedIds = array_filter($selectedIds);
    }

    public function array(): array
    {
        $query = Employee::with([
            'department', 
            'subDepartment', 
            'position', 
            'grade', 
            'workScheduleScheme'
        ]);

        // Filter berdasarkan ID yang dipilih jika ada
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        $employees = $query->get();
        $data = [];

        foreach ($employees as $emp) {
            $data[] = [
                $emp->employee_id,
                $emp->nip,
                $emp->full_name,
                $emp->email,
                $emp->phone,
                $emp->department?->name,
                $emp->subDepartment?->name,
                $emp->position?->title ?? $emp->position?->name,
                $emp->grade?->name,
                $emp->workScheduleScheme?->name,
                $emp->join_date,
                $emp->birth_date,
                $emp->age_years,
                $emp->work_duration_months,
                $emp->is_active ? 'Aktif' : 'Non Aktif',
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Employee ID', 
            'NIP', 
            'Nama Lengkap', 
            'Email', 
            'Telepon', 
            'Departemen', 
            'Sub Departemen', 
            'POS', 
            'Grade', 
            'Skema Hari Kerja', 
            'Tanggal Bergabung', 
            'Tanggal Lahir', 
            'Umur (Thn)', 
            'Lama Bekerja (Bln)', 
            'Status'
        ];
    }
}