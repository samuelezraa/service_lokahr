<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class ScheduleSampleExport implements FromArray, WithHeadings, ShouldAutoSize, WithCustomCsvSettings
{
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'use_bom'   => true,
        ];
    }

    public function array(): array
    {
        // Ambil 1 karyawan aktif yang memiliki skema kerja 5-2 sebagai contoh
        $emp = Employee::with([
            'department',
            'subDepartment',
            'workScheduleScheme',
        ])
            ->where('is_active', true)
            ->whereHas('workScheduleScheme', function ($q) {
                $q->where('name', 'like', '%5-2%')
                  ->orWhere('code', 'like', '%5-2%')
                  ->orWhere('code', 'like', '%52%');
            })
            ->orderBy('full_name')
            ->first();

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        if ($emp) {
            $schedule = Schedule::with('scheduleDetails')
                ->where('employee_id', $emp->id)
                ->latest('id')
                ->first();

            $details = $schedule ? $schedule->scheduleDetails->keyBy('day_of_week') : collect();

            $row = [
                $emp->nip ?? '-',
                $emp->full_name ?? '-',
                $emp->department?->name ?? '-',
                $emp->subDepartment?->name ?? '-',
            ];

            foreach ($days as $day) {
                $dayDetail = $details->get($day);
                $row[] = ($dayDetail && $dayDetail->is_active && $dayDetail->check_in_time)
                    ? $dayDetail->check_in_time
                    : '00:00:00';
                $row[] = ($dayDetail && $dayDetail->is_active && $dayDetail->check_out_time)
                    ? $dayDetail->check_out_time
                    : '00:00:00';
            }

            $row[] = $schedule ? ($schedule->is_active ? 'Aktif' : 'Non Aktif') : 'Aktif';

            return [$row];
        }

        // Fallback jika belum ada data karyawan aktif skema 5-2 di database
        return [[
            '000001001',
            'John Doe',
            'IT',
            'Frontend Engineering',
            '08:00:00', '17:00:00',
            '08:00:00', '17:00:00',
            '08:00:00', '17:00:00',
            '08:00:00', '17:00:00',
            '08:00:00', '17:00:00',
            '00:00:00', '00:00:00',
            '00:00:00', '00:00:00',
            'Aktif',
        ]];
    }

    public function headings(): array
    {
        return [
            'NIP',
            'Nama Karyawan',
            'Departemen',
            'Sub Departemen',
            'Senin Masuk', 'Senin Keluar',
            'Selasa Masuk', 'Selasa Keluar',
            'Rabu Masuk', 'Rabu Keluar',
            'Kamis Masuk', 'Kamis Keluar',
            'Jumat Masuk', 'Jumat Keluar',
            'Sabtu Masuk', 'Sabtu Keluar',
            'Minggu Masuk', 'Minggu Keluar',
            'Status',
        ];
    }
}