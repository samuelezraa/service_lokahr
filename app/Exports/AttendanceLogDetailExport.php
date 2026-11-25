<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\EmployeeAttendanceDetail;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceLogDetailExport implements FromArray, WithHeadings, WithCustomCsvSettings
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

    public function getCsvSettings(): array
    {
        return [
            'delimiter'              => ';',
            'enclosure'              => '"',
            'line_ending'            => "\r\n",
            'use_bom'                => true,
            'include_separator_line' => false,
        ];
    }

    public function array(): array
    {
        $query = EmployeeAttendanceDetail::with([
            'employeeAttendance',
        ])->where(function ($q) {
            $q->whereNotNull('actual_check_in')
              ->where('actual_check_in', '!=', '00:00:00')
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('actual_check_out')
                     ->where('actual_check_out', '!=', '00:00:00');
              });
        });

        if (!empty($this->search)) {
            $search = $this->search;
            $query->whereHas('employeeAttendance', function ($q) use ($search) {
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
            $dept = $this->dept;
            $query->whereHas('employeeAttendance', function ($q) use ($dept) {
                $q->where('department_name', $dept);
            });
        }

        if (!empty($this->subDept)) {
            $subDept = $this->subDept;
            $query->whereHas('employeeAttendance', function ($q) use ($subDept) {
                $q->where('sub_department_name', $subDept);
            });
        }

        if (!empty($this->grade)) {
            $grade = $this->grade;
            $query->whereHas('employeeAttendance', function ($q) use ($grade) {
                $q->where('grade_name', $grade);
            });
        }

        $details = $query->orderByDesc('date')->orderBy('id')->get();

        $nips = $details
            ->map(fn ($d) => trim((string) ($d->employeeAttendance?->nip ?? '')))
            ->filter()
            ->unique()
            ->values();

        // Hanya ambil karyawan yang aktif (is_active = true)
        $activeEmployees = Employee::with(['department', 'subDepartment', 'position', 'grade'])
            ->where('is_active', true)
            ->where(function ($q) use ($nips) {
                $q->whereIn('nip', $nips)
                  ->orWhereIn('employee_id', $nips);
            })
            ->get();

        $employeesByNip   = $activeEmployees->keyBy(fn ($e) => trim((string) $e->nip));
        $employeesByEmpId = $activeEmployees->keyBy(fn ($e) => trim((string) $e->employee_id));

        $activeCodes = $activeEmployees
        ->flatMap(fn ($e) => [trim((string) $e->nip), trim((string) $e->employee_id)])
        ->filter()
        ->unique()
        ->values();

        $rows = collect();

        foreach ($details as $item) {
            $att  = $item->employeeAttendance;
            $code = trim((string) ($att?->nip ?? ''));

            if ($code === '' || !$activeCodes->contains($code)) {
                continue;
            }

            $emp = $employeesByNip->get($code) ?? $employeesByEmpId->get($code);

            $baseRow = [
                'employee_id'            => $emp?->employee_id ?? $code,
                'nip'                    => $emp?->nip ?? $code,
                'name'                   => $emp?->full_name ?? ($att?->employee_name ?: '-'),
                'dept'                   => $att?->department_name ?? $emp?->department?->name ?? '-',
                'subDept'                => $att?->sub_department_name ?? $emp?->subDepartment?->name ?? '-',
                'pos'                    => $att?->pos_name ?? $emp?->position?->name ?? $emp?->position?->title ?? '-',
                'grade'                  => $att?->grade_name ?? $emp?->grade?->name ?? '-',
                'attendanceEnrollId'     => $emp?->employee_id ?? $code,
                'attendanceVerification' => '99',
                'presenceVerification'   => '255',
                'attendanceDate'         => $item->date ? $item->date->format('Y-m-d') : '-',
                'dataUploadTime'         => $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : '-',
                'machineId'              => '1',
                'machineIp'              => '192.168.1.201',
            ];

            if (!empty($item->actual_check_in) && $item->actual_check_in !== '00:00:00') {
                $rows->push(array_merge($baseRow, [
                    'attendanceTime' => $item->actual_check_in,
                ]));
            }

            if (!empty($item->actual_check_out) && $item->actual_check_out !== '00:00:00') {
                $rows->push(array_merge($baseRow, [
                    'attendanceTime' => $item->actual_check_out,
                ]));
            }
        }

        return $rows
            ->unique(fn ($r) => $r['employee_id'] . '|' . $r['attendanceDate'] . '|' . $r['attendanceTime'])
            ->map(fn ($r) => [
                $r['employee_id'],
                $r['nip'],
                $r['name'],
                $r['dept'],
                $r['subDept'],
                $r['pos'],
                $r['grade'],
                $r['attendanceEnrollId'],
                $r['attendanceVerification'],
                $r['presenceVerification'],
                $r['attendanceTime'],
                $r['attendanceDate'],
                $r['dataUploadTime'],
                $r['machineId'],
                $r['machineIp'],
            ])
            ->values()
            ->toArray();
    }

    public function headings(): array
    {
        return [
            'ID Karyawan',
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