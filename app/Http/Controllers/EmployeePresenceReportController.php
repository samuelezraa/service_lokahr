<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\AttendanceLogDetail;
use App\Exports\EmployeePresenceReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeePresenceReportController extends Controller
{
    /**
     * GET /api/reports/employee-presence
     */
    public function index(Request $request)
    {
        $query = Department::query();

        // 1. Search Global (Nama Departemen)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // 2. Server-Side Pagination
        $perPage = (int) $request->input('per_page', 25);
        $paginated = $query->paginate($perPage);

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $periode   = $request->input('periode');

        // 3. Mapping data & kalkulasi total pax
        $mappedItems = collect($paginated->items())->map(function ($dept) use ($startDate, $endDate, $periode) {
            $paxQuery = AttendanceLogDetail::whereHas('employee', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
            });

            // 🟢 GUNAKAN KOLOM 'date' (BUKAN 'attendance_date')
            if (!empty($startDate) && !empty($endDate)) {
                $paxQuery->whereBetween('date', [$startDate, $endDate]);
            } elseif (!empty($periode)) {
                $paxQuery->where('date', 'like', "{$periode}%");
            }

            $totalPax = $paxQuery->count();
            if ($totalPax === 0 && empty($startDate) && empty($periode)) {
                $totalPax = $dept->employees()->where('is_active', true)->count();
            }

            return [
                'id'         => (string) $dept->id,
                'department' => $dept->name,
                'totalPax'   => $totalPax,
            ];
        });

        // Hitung Grand Total Pax seluruh departemen
        $grandTotalPax = Department::all()->sum(function ($dept) use ($startDate, $endDate, $periode) {
            $paxQuery = AttendanceLogDetail::whereHas('employee', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
            });

            // 🟢 GUNAKAN KOLOM 'date' (BUKAN 'attendance_date')
            if (!empty($startDate) && !empty($endDate)) {
                $paxQuery->whereBetween('date', [$startDate, $endDate]);
            } elseif (!empty($periode)) {
                $paxQuery->where('date', 'like', "{$periode}%");
            }

            $cnt = $paxQuery->count();
            return ($cnt === 0 && empty($startDate) && empty($periode)) 
                ? $dept->employees()->where('is_active', true)->count() 
                : $cnt;
        });

        return response()->json([
            'success'       => true,
            'data'          => $mappedItems,
            'grandTotalPax' => $grandTotalPax,
            'meta'          => [
                'current_page' => $paginated->currentPage(),
                'from'         => $paginated->firstItem(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'to'           => $paginated->lastItem(),
                'total'        => $paginated->total(),
            ]
        ]);
    }

    /**
     * GET /api/reports/employee-presence/export
     */
    public function export(Request $request)
    {
        $search    = $request->query('search');
        $periode   = $request->query('periode');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');

        $fileName = 'laporan-kehadiran-karyawan-' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new EmployeePresenceReportExport($search, $periode, $startDate, $endDate),
            $fileName
        );
    }
}