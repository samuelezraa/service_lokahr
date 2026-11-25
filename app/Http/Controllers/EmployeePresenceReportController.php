<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Period;
use App\Models\AttendanceLogDetail;
use App\Exports\EmployeePresenceReportExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeePresenceReportController extends Controller
{
    /**
     * GET /api/reports/employee-presence
     */
    public function index(Request $request)
    {
        $search    = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $periode   = $request->input('periode');
        $perPage   = (int) $request->input('per_page', 25);

        // 1. Resolusi Rentang Tanggal (dari Range Tanggal atau Master Period)
        $effectiveStart = !empty($startDate) ? Carbon::parse($startDate)->format('Y-m-d') : null;
        $effectiveEnd   = !empty($endDate) ? Carbon::parse($endDate)->format('Y-m-d') : null;

        if (!empty($periode) && (!$effectiveStart || !$effectiveEnd)) {
            $periodObj = Period::where('code', $periode)
                ->orWhere('id', $periode)
                ->first();

            if ($periodObj) {
                $effectiveStart = $effectiveStart ?? Carbon::parse($periodObj->start_date)->format('Y-m-d');
                $effectiveEnd   = $effectiveEnd   ?? Carbon::parse($periodObj->end_date)->format('Y-m-d');
            }
        }

        $isDateFiltered = (bool) ($effectiveStart || $effectiveEnd || !empty($periode));

        // Helper closure untuk menerapkan filter tanggal ke query AttendanceLogDetail
        $applyDateFilter = function ($q) use ($effectiveStart, $effectiveEnd, $periode) {
            if ($effectiveStart && $effectiveEnd) {
                $q->whereBetween('date', [$effectiveStart, $effectiveEnd]);
            } elseif ($effectiveStart) {
                $q->whereDate('date', '>=', $effectiveStart);
            } elseif ($effectiveEnd) {
                $q->whereDate('date', '<=', $effectiveEnd);
            } elseif (!empty($periode)) {
                $q->where('date', 'like', "{$periode}%");
            }
        };

        $query = Department::query();

        // 2. Search Global (Nama Departemen)
        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        // 3. Filter Departemen yang memiliki log kehadiran pada periode/tanggal terpilih
        if ($isDateFiltered) {
            $query->whereHas('employees.attendanceLogDetails', function ($q) use ($applyDateFilter) {
                $applyDateFilter($q);
            });
        }

        $paginated = $query->paginate($perPage);
// 4. Mapping data & kalkulasi total pax (jumlah karyawan unik berdasarkan NIP)
        $mappedItems = collect($paginated->items())->map(function ($dept) use ($isDateFiltered, $applyDateFilter) {
            $paxQuery = AttendanceLogDetail::where(function ($q) use ($dept) {
                $q->where('department_id', $dept->id)
                  ->orWhere('department_name', $dept->name)
                  ->orWhereHas('employee', function ($empQ) use ($dept) {
                      $empQ->where('department_id', $dept->id);
                  });
            });

            if ($isDateFiltered) {
                $applyDateFilter($paxQuery);
                $totalPax = $paxQuery->distinct('nip')->count('nip');
            } else {
                $totalPax = $paxQuery->distinct('nip')->count('nip');
                if ($totalPax === 0) {
                    $totalPax = $dept->employees()->where('is_active', true)->count();
                }
            }

            return [
                'id'         => (string) $dept->id,
                'department' => $dept->name,
                'totalPax'   => $totalPax,
            ];
        });

        // 5. Hitung Grand Total Pax seluruh departemen sesuai filter aktif
        $grandTotalQuery = Department::query();
        if (!empty($search)) {
            $grandTotalQuery->where('name', 'like', "%{$search}%");
        }
        if ($isDateFiltered) {
            $grandTotalQuery->whereHas('employees.attendanceLogDetails', function ($q) use ($applyDateFilter) {
                $applyDateFilter($q);
            });
        }

        $grandTotalPax = $grandTotalQuery->get()->sum(function ($dept) use ($isDateFiltered, $applyDateFilter) {
            $paxQuery = AttendanceLogDetail::where(function ($q) use ($dept) {
                $q->where('department_id', $dept->id)
                  ->orWhere('department_name', $dept->name)
                  ->orWhereHas('employee', function ($empQ) use ($dept) {
                      $empQ->where('department_id', $dept->id);
                  });
            });

            if ($isDateFiltered) {
                $applyDateFilter($paxQuery);
                return $paxQuery->distinct('nip')->count('nip');
            }

            $cnt = $paxQuery->distinct('nip')->count('nip');
            return $cnt === 0 ? $dept->employees()->where('is_active', true)->count() : $cnt;
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

        $fileName = 'laporan-kehadiran-karyawan-' . date('Y-m-d') . '.csv';

        return Excel::download(
            new EmployeePresenceReportExport($search, $periode, $startDate, $endDate),
            $fileName
        );
    }
}