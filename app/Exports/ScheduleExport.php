<?php

namespace App\Exports;

use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ScheduleExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected array $selectedIds;

    public function __construct(array $selectedIds = [])
    {
        $this->selectedIds = array_filter($selectedIds);
    }

    public function array(): array
    {
        $query = Schedule::with(['employee', 'department', 'subDepartment', 'scheduleDetails'])
            ->whereNotNull('employee_id');

        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        $schedules = $query->latest()->get();
        $data = [];

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($schedules as $schedule) {
            $details = $schedule->scheduleDetails->keyBy('day_of_week');

            $row = [
                $schedule->nip ?? $schedule->employee?->nip ?? '-',
                $schedule->employee_name ?? $schedule->employee?->full_name ?? '-',
                $schedule->department_name ?? $schedule->department?->name ?? '-',
                $schedule->sub_department_name ?? $schedule->subDepartment?->name ?? '-',
            ];

            // Petakan jam masuk & keluar per hari
            foreach ($days as $day) {
                $dayDetail = $details->get($day);
                $row[] = $dayDetail ? $dayDetail->check_in_time : '00:00:00';
                $row[] = $dayDetail ? $dayDetail->check_out_time : '00:00:00';
            }

            $row[] = $schedule->is_active ? 'Aktif' : 'Non Aktif';

            $data[] = $row;
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