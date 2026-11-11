<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ScheduleSampleExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        return [
            [
                '1001',                   // NIP
                'Antika Lorien',          // Nama Karyawan
                'IT',                     // Departemen
                'Frontend Engineering',   // Sub Departemen
                '00:00:00', '00:00:00',   // Senin (Masuk - Keluar)
                '09:11:00', '18:00:00',   // Selasa
                '09:00:00', '18:00:00',   // Rabu
                '09:00:00', '18:00:00',   // Kamis
                '09:00:00', '17:00:00',   // Jumat
                '00:00:00', '00:00:00',   // Sabtu
                '00:00:00', '00:00:00',   // Minggu
                'Aktif',                  // Status
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'nip',
            'employee_name',
            'department',
            'sub_department',
            'monday_in', 'monday_out',
            'tuesday_in', 'tuesday_out',
            'wednesday_in', 'wednesday_out',
            'thursday_in', 'thursday_out',
            'friday_in', 'friday_out',
            'saturday_in', 'saturday_out',
            'sunday_in', 'sunday_out',
            'status',
        ];
    }
}