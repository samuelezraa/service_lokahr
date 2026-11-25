<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class EmployeeSampleExport implements FromArray, WithHeadings, WithCustomCsvSettings
{
    public function getCsvSettings(): array
    {
        return [
            'delimiter'       => ';',
            'use_bom'         => true,
            'output_encoding' => 'UTF-8',
        ];
    }

    public function array(): array
    {
        return [
            [
                '1234456729',
                '1010',
                'Zidane',
                'zidane@example.com',
                '081236567890',
                'IT',
                'Frontend Engineering',
                'Programmer Officer',
                'Staff / Junior',
                'Shift Regular 5 Hari',
                '15-01-2024',
                '20-05-1995',
                'Aktif',
            ]
        ];
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
            'Status'
        ];
    }
}