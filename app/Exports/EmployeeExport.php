<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class EmployeeExport implements FromArray, WithHeadings, WithCustomCsvSettings
{
    protected array $selectedIds;
    protected array $filters;

    public function __construct(array $selectedIds = [], array $filters = [])
    {
        $this->selectedIds = array_filter($selectedIds);
        $this->filters = $filters;
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter'              => ';',
            'use_bom'                => true,
            'output_encoding'        => 'UTF-8',
        ];
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

        // 1. Jika ada baris yang dicentang (checkbox), export berdasarkan ID terpilih
        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        } else {
            // 2. Jika tidak ada yang dicentang, export berdasarkan filter aktif di tabel
            if (!empty($this->filters['search'])) {
                $search = $this->filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('employee_id', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            if (!empty($this->filters['department_id'])) {
                $query->where('department_id', $this->filters['department_id']);
            }
            if (!empty($this->filters['sub_department_id'])) {
                $query->where('sub_department_id', $this->filters['sub_department_id']);
            }
            if (!empty($this->filters['position_id'])) {
                $query->where('position_id', $this->filters['position_id']);
            }
            if (!empty($this->filters['grade_id'])) {
                $query->where('grade_id', $this->filters['grade_id']);
            }
            if (isset($this->filters['status']) && $this->filters['status'] !== '') {
                $status = filter_var($this->filters['status'], FILTER_VALIDATE_BOOLEAN);
                $query->where('is_active', $status);
            }
        }

        $employees = $query->latest()->get();
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
                $emp->age_years !== null ? (int) floor($emp->age_years) : null,
                $emp->work_duration_months !== null ? (int) floor($emp->work_duration_months) : null,
                $emp->is_active ? 'Aktif' : 'Non Aktif',
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'ID Karyawan', 
            'NIP', 
            'Nama', 
            'Email', 
            'No. Telepon', 
            'Departemen', 
            'Sub Departemen', 
            'POS', 
            'Grade', 
            'Skema Hari Kerja', 
            'Tanggal Bergabung', 
            'Tanggal Lahir', 
            'Umur (Thn)', 
            'Lama Kerja (Bln)', 
            'Status'
        ];
    }
}