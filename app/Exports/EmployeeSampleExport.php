<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'EMP001',                         // employee_id
                '12344567290',                    // nip
                'Zidane',                         // full_name
                'zidane@example.com',             // email
                '081236567890',                   // phone
                'IT',                             // department (Nama Departemen Asli)
                'Frontend Engineering',           // sub_department (Nama Sub Dept Asli)
                'Programmer Officer',             // position (Nama POS Asli)
                'Staff / Junior',                 // grade (Nama Grade Asli)
                'Shift Regular 5 Hari',           // work_schedule_scheme
                '2024-01-15',                     // join_date (YYYY-MM-DD)
                '1995-05-20',                     // birth_date (YYYY-MM-DD)
                'Aktif',                          // status
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'employee_id',
            'nip',
            'full_name',
            'email',
            'phone',
            'department',
            'sub_department',
            'position',
            'grade',
            'work_schedule_scheme',
            'join_date',
            'birth_date',
            'status'
        ];
    }
}