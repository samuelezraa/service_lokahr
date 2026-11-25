<?php

namespace App\Exports;

use App\Models\Schedule;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ScheduleExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected array $selectedIds;
    protected array $filters;

    public function __construct(array $selectedIds = [], array $filters = [])
    {
        $this->selectedIds = array_values(array_filter($selectedIds));
        $this->filters = $filters;
    }

    public function array(): array
    {
        $query = Schedule::with(['employee', 'department', 'subDepartment', 'scheduleDetails'])
            ->whereNotNull('employee_id');

        if (!empty($this->selectedIds)) {
            $query->whereIn('id', $this->selectedIds);
        }

        // Filter Search
        if (!empty($this->filters['search'])) {
            $search = trim((string) $this->filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQ) use ($search) {
                      $empQ->where('employee_id', 'like', "%{$search}%")
                           ->orWhere('nip', 'like', "%{$search}%")
                           ->orWhere('full_name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Departemen
        if (!empty($this->filters['department_id'])) {
            $query->where('department_id', $this->filters['department_id']);
        }

        // Filter Sub Departemen
        if (!empty($this->filters['sub_department_id'])) {
            $query->where('sub_department_id', $this->filters['sub_department_id']);
        }

        // Filter Status
        if (
            isset($this->filters['status']) &&
            $this->filters['status'] !== null &&
            $this->filters['status'] !== ''
        ) {
            $status = filter_var($this->filters['status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($status !== null) {
                $query->where('is_active', $status);
            }
        }

        // Urutkan dari terbaru lalu deduplikasi berdasarkan NIP + Nama + Dept + SubDept
        $schedules = $query->latest('id')
            ->get()
            ->unique(function ($schedule) {
                $nip = strtolower(trim((string) ($schedule->nip ?? $schedule->employee?->nip ?? $schedule->employee?->employee_id ?? '')));
                $name = strtolower(trim((string) ($schedule->employee_name ?? $schedule->employee?->full_name ?? $schedule->name ?? '')));
                $dept = strtolower(trim((string) ($schedule->department_name ?? $schedule->department?->name ?? '')));
                $subDept = strtolower(trim((string) ($schedule->sub_department_name ?? $schedule->subDepartment?->name ?? '')));

                return implode('|', [$nip, $name, $dept, $subDept]);
            })
            ->values();

        $data = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($schedules as $schedule) {
            $details = $schedule->scheduleDetails->keyBy('day_of_week');

            $row = [
                $schedule->nip ?? $schedule->employee?->nip ?? $schedule->employee?->employee_id ?? '-',
                $schedule->employee_name ?? $schedule->employee?->full_name ?? '-',
                $schedule->department_name ?? $schedule->department?->name ?? '-',
                $schedule->sub_department_name ?? $schedule->subDepartment?->name ?? '-',
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