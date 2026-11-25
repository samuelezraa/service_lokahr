<?php

namespace App\Http\Controllers;

use App\Exports\PivotReportExport;
use App\Models\AttendanceLogDetail;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;
use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PivotReportController extends Controller
{
    /**
     * GET /api/reports/pivot
     */
    public function index(Request $request)
    {
        $query = Employee::with([
            'department',
            'subDepartment',
            'position',
            'grade',
            'workScheduleScheme',
        ])->where('is_active', true);

        // 1. Search Global
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // 2. Resolusi Periode Terpilih
        $selectedPeriodObj = null;
        if ($request->filled('periode')) {
            $periode = $request->periode;
            $selectedPeriodObj = Period::where('code', $periode)
                ->orWhere('id', $periode)
                ->first();

            $periodCode = $selectedPeriodObj?->code ?? $periode;

            $query->where(function ($subQuery) use ($periodCode, $periode, $selectedPeriodObj) {
                $subQuery->whereHas('attendances', function ($q) use ($periodCode, $periode) {
                    $q->where('period_code', $periodCode)
                      ->orWhere('period_code', $periode);
                });

                if ($selectedPeriodObj && $selectedPeriodObj->start_date && $selectedPeriodObj->end_date) {
                    $start = Carbon::parse($selectedPeriodObj->start_date)->format('Y-m-d');
                    $end   = Carbon::parse($selectedPeriodObj->end_date)->format('Y-m-d');

                    $subQuery->orWhereHas('attendances.details', function ($q) use ($start, $end) {
                        $q->whereBetween('date', [$start, $end]);
                    })->orWhereHas('attendanceLogDetails', function ($q) use ($start, $end) {
                        $q->whereBetween('date', [$start, $end]);
                    });
                }
            });
        }

        // 3. Filter Master Data
        if ($request->filled('departemen')) {
            $dept = $request->departemen;
            $query->whereHas('department', function ($q) use ($dept) {
                $q->where('name', $dept)->orWhere('code', $dept)->orWhere('id', $dept);
            });
        }

        if ($request->filled('sub_departemen')) {
            $subDept = $request->sub_departemen;
            $query->whereHas('subDepartment', function ($q) use ($subDept) {
                $q->where('name', $subDept)->orWhere('code', $subDept)->orWhere('id', $subDept);
            });
        }

        if ($request->filled('pos')) {
            $pos = $request->pos;
            $query->whereHas('position', function ($q) use ($pos) {
                $q->where('name', $pos)->orWhere('title', $pos)->orWhere('code', $pos)->orWhere('id', $pos);
            });
        }

        if ($request->filled('grade')) {
            $grade = $request->grade;
            $query->whereHas('grade', function ($q) use ($grade) {
                $q->where('name', $grade)->orWhere('code', $grade)->orWhere('id', $grade);
            });
        }

        if ($request->filled('skema')) {
            $skema = $request->skema;
            $query->whereHas('workScheduleScheme', function ($q) use ($skema) {
                $q->where('name', $skema)->orWhere('code', $skema)->orWhere('id', $skema);
            });
        }

        // 4. Filter Rentang Tanggal Manual
        if ($request->filled('start_date') || $request->filled('end_date')) {
            $startDate = $request->start_date;
            $endDate   = $request->end_date;

            $query->where(function ($subQuery) use ($startDate, $endDate) {
                $subQuery->whereHas('attendances.details', function ($q) use ($startDate, $endDate) {
                    if (!empty($startDate)) $q->whereDate('date', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('date', '<=', $endDate);
                })->orWhereHas('attendanceLogDetails', function ($q) use ($startDate, $endDate) {
                    if (!empty($startDate)) $q->whereDate('date', '>=', $startDate);
                    if (!empty($endDate)) $q->whereDate('date', '<=', $endDate);
                });
            });
        }

        // 5. Paginate Server-Side
        $perPage = (int) $request->input('per_page', 25);
        $paginated = $query->latest()->paginate($perPage);

        $activePeriodObj = $selectedPeriodObj ?? Period::where('is_active', true)->orderByDesc('start_date')->first();
        $effectiveStart  = $request->filled('start_date')
            ? $request->start_date
            : (!empty($activePeriodObj?->start_date) ? Carbon::parse($activePeriodObj->start_date)->format('Y-m-d') : null);
        $effectiveEnd    = $request->filled('end_date')
            ? $request->end_date
            : (!empty($activePeriodObj?->end_date) ? Carbon::parse($activePeriodObj->end_date)->format('Y-m-d') : null);

        // 6. Map Data Response & Hitung Rekap Per 1 Periode / Rentang Tanggal
        $mappedItems = collect($paginated->items())->map(function ($emp) use ($request, $selectedPeriodObj, $activePeriodObj, $effectiveStart, $effectiveEnd) {
            $masuk = 0; $libur = 0; $ph = 0; $izin = 0;
            $alfa = 0; $sakit = 0; $cuti = 0; $terlambat = 0;

            $empCodes = collect([$emp->nip, $emp->employee_id])
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values();

            $attRecord = null;
            if (!$request->filled('start_date') && !$request->filled('end_date') && $empCodes->isNotEmpty()) {
                $attQuery = EmployeeAttendance::where(function ($q) use ($empCodes) {
                    $q->whereIn('nip', $empCodes)
                      ->orWhereIn('employee_id', $empCodes);
                });

                if ($request->filled('periode')) {
                    $periodCode = $selectedPeriodObj?->code ?? $request->periode;
                    $attQuery->where(function ($q) use ($periodCode, $request) {
                        $q->where('period_code', $periodCode)
                          ->orWhere('period_code', $request->periode);
                    });
                } elseif ($activePeriodObj?->code) {
                    $attQuery->where('period_code', $activePeriodObj->code);
                }

                $attRecord = $attQuery->latest('id')->first();
            }

            if ($attRecord) {
                $masuk     = (int) $attRecord->total_work_days;
                $libur     = (int) $attRecord->total_off_days;
                $ph        = (int) $attRecord->total_ph_days;
                $izin      = (int) $attRecord->total_permit_days;
                $alfa      = (int) $attRecord->total_alpha_days;
                $sakit     = (int) $attRecord->total_sick_days;
                $cuti      = (int) $attRecord->total_leave_days;
                $terlambat = (int) $attRecord->total_late_days;
            } elseif ($effectiveStart && $effectiveEnd && $empCodes->isNotEmpty()) {
                $details = EmployeeAttendanceDetail::whereHas('employeeAttendance', function ($q) use ($empCodes) {
                    $q->whereIn('nip', $empCodes)
                      ->orWhereIn('employee_id', $empCodes);
                })
                ->whereBetween('date', [$effectiveStart, $effectiveEnd])
                ->get();

                if ($details->isEmpty()) {
                    $details = AttendanceLogDetail::whereIn('nip', $empCodes)
                        ->whereBetween('date', [$effectiveStart, $effectiveEnd])
                        ->get();
                }

                $masuk     = $details->whereIn('attendance_status', ['Berangkat', 'Masuk', 'Hadir'])->count();
                $libur     = $details->where('attendance_status', 'Libur')->count();
                $ph        = $details->whereIn('attendance_status', ['PH', 'Public Holiday'])->count();
                $izin      = $details->where('attendance_status', 'Izin')->count();
                $alfa      = $details->whereIn('attendance_status', ['Alfa', 'Alpha'])->count();
                $sakit     = $details->where('attendance_status', 'Sakit')->count();
                $cuti      = $details->where('attendance_status', 'Cuti')->count();
                $terlambat = $details->where('status', 'Terlambat')->count();
            }

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
                'masuk'          => $masuk,
                'libur'          => $libur,
                'ph'             => $ph,
                'izin'           => $izin,
                'alfa'           => $alfa,
                'sakit'          => $sakit,
                'cuti'           => $cuti,
                'terlambat'      => $terlambat,
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
            ],
        ]);
    }

    public function export(Request $request)
    {
        $search    = $request->query('search');
        $periode   = $request->query('periode');
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $dept      = $request->query('departemen');
        $subDept   = $request->query('sub_departemen');
        $pos       = $request->query('pos');
        $grade     = $request->query('grade');
        $skema     = $request->query('skema');

        $fileName = 'laporan-pivot-' . date('Y-m-d') . '.csv';

        return Excel::download(
            new PivotReportExport($search, $periode, $startDate, $endDate, $dept, $subDept, $pos, $grade, $skema),
            $fileName
        );
    }
}