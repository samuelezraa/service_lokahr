<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InputScheduleSampleExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                '2026-10',                // period_code
                '1001',                   // nip
                'Antika Lorien',          // employee_name
                'IT',                     // department
                'Frontend Engineering',   // sub_department
                'Skema 5-2',              // work_scheme
                'Aktif',                  // status
                31,                       // total_days
                22,                       // work_days
                9,                        // off_days
                1,                        // ph_days
                0,                        // permit_days
                0,                        // alpha_days
                0,                        // leave_days
                0,                        // late_days
            ],
            [
                '2026-10',
                '1002',
                'Budi Pratama',
                'IT',
                'Backend Engineering',
                'Skema 5-2',
                'Aktif',
                31,
                22,
                9,
                1,
                0,
                0,
                0,
                0,
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'period_code',
            'nip',
            'employee_name',
            'department',
            'sub_department',
            'work_scheme',
            'status',
            'total_days',
            'work_days',
            'off_days',
            'ph_days',
            'permit_days',
            'alpha_days',
            'leave_days',
            'late_days',
        ];
    }
}