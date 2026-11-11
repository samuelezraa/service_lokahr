<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLogDetail;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Exports\AttendanceLogDetailExport;
use Maatwebsite\Excel\Facades\Excel;
class AttendanceLogDetailController extends Controller
{
    /**
     * GET /api/attendance-log-details
     * Mendapatkan daftar laporan absensi dengan Server-Side Pagination, Search, & Filter
     */
    public function index(Request $request)
    {
        $query = AttendanceLogDetail::with([
            'attendanceLog',
            'department',
        ])->latest('attendance_time');

        // Search Global (NIP, Nama Karyawan)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%");
            });
        }

        // Filter Rentang Tanggal
        if ($request->has('start_date') && !empty($request->start_date)) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && !empty($request->end_date)) {
            $query->whereDate('date', '<=', $request->end_date);
        }

        // Filter Departemen (bisa berdasarkan ID atau nama)
        if ($request->has('department_id') && !empty($request->department_id)) {
            $query->where('department_id', $request->department_id);
        } elseif ($request->has('dept') && !empty($request->dept)) {
            $query->where('department_name', $request->dept);
        }

        // Filter Sub Departemen via Relasi Employee
        if ($request->has('sub_dept') && !empty($request->sub_dept)) {
            $subDept = $request->sub_dept;
            $query->whereHas('employee.subDepartment', function ($q) use ($subDept) {
                $q->where('name', $subDept);
            });
        }

        // Filter Grade via Relasi Employee
        if ($request->has('grade') && !empty($request->grade)) {
            $grade = $request->grade;
            $query->whereHas('employee.grade', function ($q) use ($grade) {
                $q->where('name', $grade);
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $paginated = $query->paginate($perPage);

        // Ambil semua NIP dari data absensi pada halaman aktif untuk pencocokan data Karyawan
        $nips = collect($paginated->items())->pluck('nip')->filter()->unique();
        $employees = Employee::with(['subDepartment', 'position', 'grade'])
            ->whereIn('nip', $nips)
            ->get()
            ->keyBy('nip');

        // Map data agar menyertakan relasi karyawan secara lengkap & presisi
        $mappedItems = collect($paginated->items())->map(function ($item) use ($employees) {
            $emp = $employees->get($item->nip);

            return [
                'id'                     => (string) $item->id,
                'nip'                    => $item->nip ?? $emp?->nip ?? '-',
                'name'                   => $item->employee_name ?? $emp?->full_name ?? '-',
                'dept'                   => $item->department_name ?? $item->department?->name ?? $emp?->department?->name ?? '-',
                'subDept'                => $emp?->subDepartment?->name ?? '-',
                'pos'                    => $emp?->position?->name ?? $emp?->position?->title ?? '-',
                'grade'                  => $emp?->grade?->name ?? '-',
                'attendanceEnrollId'     => $item->nip ?? '-',
                'attendanceVerification' => '99',
                'presenceVerification'   => '255',
                'attendanceTime'         => $item->attendance_time ? date('H:i:s', strtotime($item->attendance_time)) : '-',
                'attendanceDate'         => $item->date ? date('Y-m-d', strtotime($item->date)) : '-',
                'dataUploadTime'         => $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '-',
                'machineId'              => (string) ($item->attendance_log_id ?? '-'),
                'machineIp'              => $item->attendanceLog?->machine_ip ?? '-',
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $mappedItems,
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'from'         => $paginated->firstItem(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'to'           => $paginated->lastItem(),
                'total'        => $paginated->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_log_id' => 'nullable|exists:attendance_logs,id',
            'date'              => 'required|date',
            'nip'               => 'required|string',
            'department_id'     => 'nullable|exists:departments,id',
            'department_name'   => 'required|string',
            'employee_name'     => 'required|string',
            'attendance_time'   => 'required|date',
            'attendance_type'   => 'required|string',
        ]);

        $detail = AttendanceLogDetail::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance log detail created successfully',
            'data'    => $detail
        ], 201);
    }

    public function show($id)
    {
        $detail = AttendanceLogDetail::with([
            'attendanceLog',
            'department',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $detail
        ]);
    }

    public function destroy($id)
    {
        $detail = AttendanceLogDetail::findOrFail($id);
        $detail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance log detail deleted successfully'
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

    $fileName = 'laporan-absensi-' . date('Y-m-d') . '.xlsx';

    return Excel::download(
        new AttendanceLogDetailExport($startDate, $endDate, $dept, $subDept, $grade, $search),
        $fileName
    );
}

}