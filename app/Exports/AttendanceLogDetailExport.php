<?php

namespace App\Exports;

use App\Models\AttendanceLogDetail;
use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceLogDetailExport implements FromArray, WithHeadings
{
    protected ?string $startDate;
    protected ?string $endDate;
    protected ?string $dept;
    protected ?string $subDept;
    protected ?string $grade;
    protected ?string $search;

    public function __construct(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $dept = null,
        ?string $subDept = null,
        ?string $grade = null,
        ?string $search = null
    ) {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->dept      = $dept;
        $this->subDept   = $subDept;
        $this->grade     = $grade;
        $this->search    = $search;
    }

    public function array(): array
    {
        $query = AttendanceLogDetail::with([
            'attendanceLog',
            'department',
        ])->latest('attendance_time');

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%");
            });
        }

        if (!empty($this->startDate)) {
            $query->whereDate('date', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $query->whereDate('date', '<=', $this->endDate);
        }

        if (!empty($this->dept)) {
            $query->where('department_name', $this->dept);
        }

        if (!empty($this->subDept)) {
            $subDept = $this->subDept;
            $query->whereHas('employee.subDepartment', function ($q) use ($subDept) {
                $q->where('name', $subDept);
            });
        }

        if (!empty($this->grade)) {
            $grade = $this->grade;
            $query->whereHas('employee.grade', function ($q) use ($grade) {
                $q->where('name', $grade);
            });
        }

        $details = $query->get();

        // Ambil data karyawan pendukung via NIP
        $nips = $details->pluck('nip')->filter()->unique();
        $employees = Employee::with(['subDepartment', 'position', 'grade'])
            ->whereIn('nip', $nips)
            ->get()
            ->keyBy('nip');

        $data = [];

        foreach ($details as $item) {
            $emp = $employees->get($item->nip);

            $data[] = [
                $item->nip ?? $emp?->nip ?? '-',
                $item->employee_name ?? $emp?->full_name ?? '-',
                $item->department_name ?? $item->department?->name ?? $emp?->department?->name ?? '-',
                $emp?->subDepartment?->name ?? '-',
                $emp?->position?->name ?? $emp?->position?->title ?? '-',
                $emp?->grade?->name ?? '-',
                $item->nip ?? '-',
                '99',
                '255',
                $item->attendance_time ? date('H:i:s', strtotime($item->attendance_time)) : '-',
                $item->date ? date('Y-m-d', strtotime($item->date)) : '-',
                $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-',
                (string) ($item->attendance_log_id ?? '-'),
                $item->attendanceLog?->machine_ip ?? '-',
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
            'POS',
            'Grade',
            'ID Absen',
            'Verifikasi Absen',
            'Verifikasi Kehadiran',
            'Jam Absen',
            'Tanggal Absen',
            'Waktu Upload Data',
            'ID Mesin',
            'IP Mesin',
        ];
    }
}