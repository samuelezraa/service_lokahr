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

        // 2. Filter Periode (Jika diperlukan relasi ke tabel periode atau schedule)
        if ($request->has('periode') && !empty($request->periode)) {
            $periode = $request->periode;
            // Tambahkan query filter periode di sini jika tabel schedule/attendance terikat dengan periode_code
        }

        // 3. Filter Master Data (Departemen, Sub Dept, POS, Grade, Skema)
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
// Filter Rentang Tanggal berdasarkan tabel attendance_log_details
        if ($request->has('start_date') && !empty($request->start_date)) {
            $startDate = $request->start_date; 
            $query->whereHas('attendanceLogDetails', function($q) use ($startDate) {
                 $q->whereDate('date', '>=', $startDate);
             });
        }
        
        if ($request->has('end_date') && !empty($request->end_date)) {
            $endDate = $request->end_date;
            $query->whereHas('attendanceLogDetails', function($q) use ($endDate) {
                 $q->whereDate('date', '<=', $endDate);
            });
        }



        // 4. Paginate Server-Side
        $perPage = (int) $request->input('per_page', 25);
        $paginated = $query->latest()->paginate($perPage);

        // 5. Map Data Response
        $mappedItems = collect($paginated->items())->map(function ($emp) {
            return [
                'id'             => (string) $emp->id,
                'employee_id'    => $emp->employee_id ?? '-',
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
        $search     = $request->query('search');
        $periode    = $request->query('periode');
        $startDate  = $request->query('start_date'); // 👈 Tangkap start_date
        $endDate    = $request->query('end_date');   // 👈 Tangkap end_date
        $dept       = $request->query('departemen');
        $subDept    = $request->query('sub_departemen');
        $pos        = $request->query('pos');
        $grade      = $request->query('grade');
        $skema      = $request->query('skema');

        $fileName = 'laporan-pivot-' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new PivotReportExport($search, $periode, $startDate, $endDate, $dept, $subDept, $pos, $grade, $skema),
            $fileName
        );
    }
}