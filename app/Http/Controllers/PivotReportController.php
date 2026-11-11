<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Exports\PivotReportExport; 

use Maatwebsite\Excel\Facades\Excel;
class PivotReportController extends Controller
{
    /**
     * GET /api/reports/pivot
     * Mendapatkan laporan pivot kehadiran dengan Server-Side Pagination, Search, & Filter
     */
    public function index(Request $request)
    {
        $query = Employee::with([
            'department',
            'subDepartment',
            'position',
            'grade',
            'workScheduleScheme'
        ])->where('is_active', true);

        // 1. Search Global (NIP, Employee ID, Nama Karyawan)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // 2. Filter Master Data (Departemen, Sub Dept, POS, Grade, Skema)
        if ($request->has('departemen') && !empty($request->departemen)) {
            $dept = $request->departemen;
            $query->whereHas('department', function ($q) use ($dept) {
                $q->where('name', $dept)->orWhere('id', $dept);
            });
        }

        if ($request->has('sub_departemen') && !empty($request->sub_departemen)) {
            $subDept = $request->sub_departemen;
            $query->whereHas('subDepartment', function ($q) use ($subDept) {
                $q->where('name', $subDept)->orWhere('id', $subDept);
            });
        }

        if ($request->has('pos') && !empty($request->pos)) {
            $pos = $request->pos;
            $query->whereHas('position', function ($q) use ($pos) {
                $q->where('name', $pos)->orWhere('title', $pos)->orWhere('id', $pos);
            });
        }

        if ($request->has('grade') && !empty($request->grade)) {
            $grade = $request->grade;
            $query->whereHas('grade', function ($q) use ($grade) {
                $q->where('name', $grade)->orWhere('id', $grade);
            });
        }

        if ($request->has('skema') && !empty($request->skema)) {
            $skema = $request->skema;
            $query->whereHas('workScheduleScheme', function ($q) use ($skema) {
                $q->where('name', $skema)->orWhere('code', $skema)->orWhere('id', $skema);
            });
        }

        // 3. Paginate Server-Side
        $perPage = (int) $request->input('per_page', 10);
        $paginated = $query->latest()->paginate($perPage);

        // 4. Map Data Response (Termasuk menyambungkan employee_id)
        $mappedItems = collect($paginated->items())->map(function ($emp) {
            return [
                'id'             => (string) $emp->id,
                'employee_id'    => $emp->employee_id ?? '-', // 👈 ID Karyawan Kustom (EMP-001)
                'nip'            => $emp->nip ?? '-',
                'nama'           => $emp->full_name ?? '-',
                'departemen'     => $emp->department?->name ?? '-',
                'sub_departemen' => $emp->subDepartment?->name ?? '-',
                'pos'            => $emp->position?->title ?? $emp->position?->name ?? '-',
                'grade'          => $emp->grade?->name ?? '-',
                'skema'          => $emp->workScheduleScheme?->name ?? $emp->workScheduleScheme?->code ?? '5-2',
                'masuk'          => 22,
                'libur'          => 8,
                'ph'             => 1,
                'izin'           => 0,
                'alfa'           => 0,
                'sakit'          => 0,
                'cuti'           => 0,
                'terlambat'      => 0,
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



    public function export(Request $request)
    {
        $search  = $request->query('search');
        $dept    = $request->query('departemen');
        $subDept = $request->query('sub_departemen');
        $pos     = $request->query('pos');
        $grade   = $request->query('grade');
        $skema   = $request->query('skema');

        $fileName = 'laporan-pivot-' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new PivotReportExport($search, $dept, $subDept, $pos, $grade, $skema),
            $fileName
        );
    }
}