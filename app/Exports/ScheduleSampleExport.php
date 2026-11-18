<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ScheduleSampleExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function array(): array
    {
        // Ambil semua karyawan aktif yang memiliki skema kerja 5-2
        $employees = Employee::with([
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
            ->get();

        // Ambil data jadwal yang sudah pernah disetting untuk karyawan tersebut
        $schedulesByEmployee = Schedule::with('scheduleDetails')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $data = [];

        foreach ($employees as $emp) {
            $schedule = $schedulesByEmployee->get($emp->id);
            $details = $schedule ? $schedule->scheduleDetails->keyBy('day_of_week') : collect();

            $row = [
                $emp->nip ?? '-',
                $emp->full_name ?? '-',
                $emp->department?->name ?? '-',
                $emp->subDepartment?->name ?? '-',
            ];

            // Isi dengan jam yang sudah disetting, jika belum ada gunakan default 00:00:00
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

            $data[] = $row;
        }

        // Fallback jika belum ada data karyawan aktif skema 5-2 di database
        if (empty($data)) {
            $data[] = [
                '1001',
                'Antika Lorien',
                'IT',
                'Frontend Engineering',
                '00:00:00', '00:00:00',
                '00:00:00', '00:00:00',
                '00:00:00', '00:00:00',
                '00:00:00', '00:00:00',
                '00:00:00', '00:00:00',
                '00:00:00', '00:00:00',
                '00:00:00', '00:00:00',
                'Aktif',
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