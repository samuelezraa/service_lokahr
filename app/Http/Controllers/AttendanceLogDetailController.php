<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceLogDetailExport;
use App\Models\Employee;
use App\Models\EmployeeAttendanceDetail;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceLogDetailController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);

        // Gunakan EmployeeAttendanceDetail sebagai sumber utama agar sinkron dengan Absensi Karyawan
        $detailQuery = EmployeeAttendanceDetail::with([
            'employeeAttendance',
        ])->where(function ($q) {
            $q->whereNotNull('actual_check_in')
              ->where('actual_check_in', '!=', '00:00:00')
              ->orWhere(function ($q2) {
                  $q2->whereNotNull('actual_check_out')
                     ->where('actual_check_out', '!=', '00:00:00');
              });
        });

        if ($request->filled('search')) {
            $search = $request->search;
            $detailQuery->whereHas('employeeAttendance', function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date')) {
            $detailQuery->whereDate('date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $detailQuery->whereDate('date', '<=', $request->end_date);
        }

        if ($request->filled('dept')) {
            $dept = $request->dept;
            $detailQuery->whereHas('employeeAttendance', function ($q) use ($dept) {
                $q->where('department_name', $dept);
            });
        }

        if ($request->filled('sub_dept')) {
            $subDept = $request->sub_dept;
            $detailQuery->whereHas('employeeAttendance', function ($q) use ($subDept) {
                $q->where('sub_department_name', $subDept);
            });
        }

        if ($request->filled('grade')) {
            $grade = $request->grade;
            $detailQuery->whereHas('employeeAttendance', function ($q) use ($grade) {
                $q->where('grade_name', $grade);
            });
        }

        $allDetails = $detailQuery->orderByDesc('date')->orderBy('id')->get();

        $nips = $allDetails
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

        // Kumpulkan semua kode milik karyawan aktif agar karyawan nonaktif otomatis tersaring
       $activeCodes = $activeEmployees
        ->flatMap(fn ($e) => [trim((string) $e->nip), trim((string) $e->employee_id)])
        ->filter()
        ->unique()
        ->values();

        $rows = collect();
        foreach ($allDetails as $item) {
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
                    'id'             => $item->id . '-in',
                    'attendanceTime' => $item->actual_check_in,
                ]));
            }

            if (!empty($item->actual_check_out) && $item->actual_check_out !== '00:00:00') {
                $rows->push(array_merge($baseRow, [
                    'id'             => $item->id . '-out',
                    'attendanceTime' => $item->actual_check_out,
                ]));
            }
        }

        $uniqueRows = $rows
            ->unique(fn ($r) => $r['employee_id'] . '|' . $r['attendanceDate'] . '|' . $r['attendanceTime'])
            ->values();

        $total       = $uniqueRows->count();
        $lastPage    = max(1, (int) ceil($total / max(1, $perPage)));
        $currentPage = min(max(1, (int) $request->input('page', 1)), $lastPage);
        $offset      = ($currentPage - 1) * $perPage;
        $pagedItems  = $uniqueRows->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data'    => $pagedItems,
            'meta'    => [
                'current_page' => $currentPage,
                'from'         => $total > 0 ? $offset + 1 : null,
                'last_page'    => $lastPage,
                'per_page'     => $perPage,
                'to'           => $total > 0 ? min($offset + $perPage, $total) : null,
                'total'        => $total,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $dept      = $request->query('dept');
        $subDept   = $request->query('sub_dept');
        $grade     = $request->query('grade');
        $search    = $request->query('search');

        $fileName = 'laporan-absensi-' . date('Y-m-d') . '.csv';

        return Excel::download(
            new AttendanceLogDetailExport($startDate, $endDate, $dept, $subDept, $grade, $search),
            $fileName,
            ExcelWriter::CSV
        );
    }
}