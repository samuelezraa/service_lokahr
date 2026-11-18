<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\Employee;
use App\Models\Period;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exports\InputScheduleExport;
use App\Exports\InputScheduleSampleExport;
use App\Exports\MyScheduleExport;
use App\Imports\InputScheduleImport;
use Maatwebsite\Excel\Facades\Excel;

class InputScheduleController extends Controller
{
    /**
     * 1. GET /api/schedule/input-schedule
     */
    public function index(Request $request)
    {
        $period = $request->query('period');

        $query = Schedule::with(['employee', 'department', 'subDepartment'])
            ->whereNotNull('employee_id');

        if (!empty($period)) {
            $query->where('period_code', $period);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('department_id') && !empty($request->department_id)) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('sub_department_id') && !empty($request->sub_department_id)) {
            $query->where('sub_department_id', $request->sub_department_id);
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        $summaryQuery = clone $query;
        $summary = [
            'totalPeriodDays' => (int) $summaryQuery->sum('total_days'),
            'totalWorkDays'   => (int) $summaryQuery->sum('work_days'),
            'totalOffDays'    => (int) $summaryQuery->sum('off_days'),
            'totalPhDays'     => (int) $summaryQuery->sum('ph_days'),
            'totalPermitDays' => (int) $summaryQuery->sum('permit_days'),
            'totalAlphaDays'  => (int) $summaryQuery->sum('alpha_days'),
            'totalLeaveDays'  => (int) $summaryQuery->sum('leave_days'),
            'totalLateDays'   => (int) $summaryQuery->sum('late_days'),
        ];

        $perPage = (int) $request->input('per_page', 10);
        $paginated = $query->latest()->paginate($perPage);

        $mappedItems = collect($paginated->items())->map(function ($sch) {
            return [
                'id'                => (string) $sch->id,
                'periodId'          => $sch->period_code ?? '-',
                'nip'               => $sch->nip ?? $sch->employee?->nip ?? '-',
                'name'              => $sch->employee_name ?? $sch->employee?->full_name ?? '-',
                'department_id'     => $sch->department_id,
                'department'        => $sch->department_name ?? $sch->department?->name ?? '-',
                'sub_department_id' => $sch->sub_department_id,
                'subDept'           => $sch->sub_department_name ?? $sch->subDepartment?->name ?? '-',
                'workScheme'        => $sch->work_scheme ?? 'Skema 5-2',
                'status'            => $sch->is_active ? 'Aktif' : 'Nonaktif',
                'totalDays'         => (int) $sch->total_days,
                'workDays'          => (int) $sch->work_days,
                'offDays'           => (int) $sch->off_days,
                'phDays'            => (int) $sch->ph_days,
                'permitDays'        => (int) $sch->permit_days,
                'alphaDays'         => (int) $sch->alpha_days,
                'leaveDays'         => (int) $sch->leave_days,
                'lateDays'          => (int) $sch->late_days,
                'ref'               => $sch->ref,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $mappedItems,
            'summary' => $summary,
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

    /**
     * GET /api/schedule/input-schedule/weekly-hours
     */
    public function getWeeklyHours()
    {
        $schedule = Schedule::with('scheduleDetails')->first();

        $defaultHours = [
            'monday'    => '09:00:00',
            'tuesday'   => '09:00:00',
            'wednesday' => '09:00:00',
            'thursday'  => '09:00:00',
            'friday'    => '09:00:00',
            'saturday'  => '00:00:00',
            'sunday'    => '00:00:00',
        ];

        if ($schedule && $schedule->scheduleDetails->count() > 0) {
            foreach ($schedule->scheduleDetails as $detail) {
                if (isset($defaultHours[$detail->day_of_week])) {
                    $defaultHours[$detail->day_of_week] = $detail->check_in_time ?? '00:00:00';
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $defaultHours
        ]);
    }

    /**
     * 2. GET /api/schedule/input-schedule/edit/{id}
     * Mendukung rentang tanggal lintas bulan berdasarkan Master Period (start_date s.d. end_date)
     */
    public function showDetail(Request $request, $id)
    {
        $schedule = Schedule::with(['employee', 'department', 'subDepartment', 'scheduleDetails'])
            ->findOrFail($id);

        $periodCode = $schedule->period_code ?? $request->query('period');
        $periodModel = $periodCode ? Period::where('code', $periodCode)->first() : null;

        if ($periodModel) {
            $startDate   = Carbon::parse($periodModel->start_date);
            $endDate     = Carbon::parse($periodModel->end_date);
            $periodLabel = $periodModel->name;
        } elseif (!empty($periodCode) && str_contains($periodCode, '-')) {
            [$year, $month] = explode('-', $periodCode);
            $startDate   = Carbon::createFromDate((int)$year, (int)$month, 1);
            $endDate     = $startDate->copy()->endOfMonth();
            $periodLabel = $startDate->format('F Y');
        } else {
            $startDate   = Carbon::now()->startOfMonth();
            $endDate     = $startDate->copy()->endOfMonth();
            $periodLabel = $startDate->format('F Y');
        }

        $details = $schedule->scheduleDetails->keyBy('day_of_week');

        $actualAttendances = \App\Models\EmployeeAttendanceDetail::whereHas('employeeAttendance', function ($q) use ($schedule, $periodCode) {
            $q->where('nip', trim($schedule->nip))
              ->where('period_code', $periodCode);
        })->get()->keyBy(function ($item) {
            return date('Y-m-d', strtotime($item->date));
        });

        $scheduleList = [];
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $cursor = $startDate->copy();
        $no = 1;

        while ($cursor->lte($endDate)) {
            $dateStr = $cursor->format('Y-m-d');
            $dayOfWeekIndex = (int) $cursor->format('w');
            $dayName = $dayNames[$dayOfWeekIndex];
            $dayOfWeekEnglish = strtolower($cursor->format('l'));

            $detail = $details->get($dayOfWeekEnglish);
            $actualLog = $actualAttendances->get($dateStr);

            $isWeekend = ($dayOfWeekIndex === 0 || $dayOfWeekIndex === 6);
            $isLibur = $detail ? ($detail->is_day_off || !$detail->is_active) : $isWeekend;

            $status = $isLibur ? 'Libur' : 'Berangkat';
            $checkIn = $detail ? $detail->check_in_time : ($isWeekend ? '00:00:00' : '08:00:00');
            $checkOut = $detail ? $detail->check_out_time : ($isWeekend ? '00:00:00' : '17:00:00');

            $actualCheckIn = $actualLog ? ($actualLog->actual_check_in ?? '00:00:00') : '00:00:00';
            $actualCheckOut = $actualLog ? ($actualLog->actual_check_out ?? '00:00:00') : '00:00:00';

            $scheduleList[] = [
                'id'                => $detail ? (string) $detail->id : 'temp_' . $no,
                'no'                => $no,
                'dayName'           => $dayName,
                'date'              => $dateStr,
                'day_of_week'       => $dayOfWeekEnglish,
                'attendanceStatus'  => $status,
                'scheduledCheckIn'  => $checkIn,
                'scheduledCheckOut' => $checkOut,
                'actualCheckIn'     => $actualCheckIn,
                'actualCheckOut'    => $actualCheckOut,
                'notes'             => $actualLog?->notes ?? ($detail?->annual_event_name ?? '-'),
                'ref'               => $schedule->ref ?? $periodCode,
            ];

            $cursor->addDay();
            $no++;
        }

        $totalDaysInPeriod = count($scheduleList);

        return response()->json([
            'success' => true,
            'employeeInfo' => [
                'id'            => $schedule->id,
                'name'          => $schedule->employee_name ?? $schedule->employee?->full_name ?? '-',
                'nip'           => $schedule->nip ?? $schedule->employee?->nip ?? '-',
                'period'        => $periodLabel,
                'dateRange'     => $startDate->format('Y-m-d') . ' — ' . $endDate->format('Y-m-d'),
                'department'    => $schedule->department_name ?? $schedule->department?->name ?? '-',
                'subDepartment' => $schedule->sub_department_name ?? $schedule->subDepartment?->name ?? '-',
                'scheme'        => $schedule->work_scheme ?? 'Skema 5-2',
                'hoursPerDay'   => 9,
                'summary'       => [
                    'totalPeriodDays' => $totalDaysInPeriod,
                    'totalWorkDays'   => collect($scheduleList)->where('attendanceStatus', 'Berangkat')->count(),
                    'totalOffDays'    => collect($scheduleList)->where('attendanceStatus', 'Libur')->count(),
                    'totalPhDays'     => $schedule->ph_days,
                    'totalPermitDays' => $schedule->permit_days,
                    'totalAlphaDays'  => $schedule->alpha_days,
                    'totalLeaveDays'  => $schedule->leave_days,
                    'totalLateDays'   => $schedule->late_days,
                ]
            ],
            'scheduleList' => $scheduleList
        ]);
    }

    /**
     * 3. PUT /api/schedule/input-schedule/edit/{id}
     */
    public function updateDetail(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'scheduleList' => 'required|array',
        ]);

        $scheduleList = $validated['scheduleList'];
        $workDays = 0;
        $offDays = 0;

        foreach ($scheduleList as $item) {
            $dayOfWeek = $item['day_of_week'] ?? strtolower(Carbon::parse($item['date'])->format('l'));
            $isLibur = ($item['attendanceStatus'] === 'Libur');

            if ($isLibur) {
                $offDays++;
            } else {
                $workDays++;
            }

            ScheduleDetail::updateOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'check_in_time'  => $item['scheduledCheckIn'] ?? '00:00:00',
                    'check_out_time' => $item['scheduledCheckOut'] ?? '00:00:00',
                    'is_active'      => !$isLibur,
                ]
            );
        }

        $schedule->update([
            'work_days' => $workDays,
            'off_days'  => $offDays,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Jadwal harian karyawan berhasil diperbarui.',
            'data'    => $schedule
        ]);
    }

    /**
     * 4. POST /api/schedule/input-schedule/weekly-hours
     */
    public function setWeeklyHours(Request $request)
    {
        $validated = $request->validate([
            'monday'    => 'required',
            'tuesday'   => 'required',
            'wednesday' => 'required',
            'thursday'  => 'required',
            'friday'    => 'required',
            'saturday'  => 'required',
            'sunday'    => 'required',
        ]);

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $schedules = Schedule::all();

        foreach ($schedules as $schedule) {
            foreach ($days as $day) {
                $dayInput = $request->input($day);

                if (is_string($dayInput)) {
                    $checkIn = $dayInput;
                    $checkOut = ($checkIn === '00:00:00') ? '00:00:00' : '17:00:00';
                } else {
                    $checkIn = $dayInput['check_in_time'] ?? '00:00:00';
                    $checkOut = $dayInput['check_out_time'] ?? '00:00:00';
                }

                $isLibur = ($checkIn === '00:00:00' && $checkOut === '00:00:00');

                ScheduleDetail::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'check_in_time'  => $checkIn,
                        'check_out_time' => $checkOut,
                        'is_active'      => !$isLibur,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Jam kerja mingguan global berhasil diterapkan ke seluruh karyawan.'
        ]);
    }

    /**
     * 5. POST /api/schedule/input-schedule/import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file'   => 'required|mimes:xlsx,xls,csv',
            'period' => 'nullable|string',
        ]);

        try {
            $period = $request->input('period');
            Excel::import(new InputScheduleImport($period), $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Data jadwal kehadiran berhasil diimport.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 6. GET /api/schedule/input-schedule/export
     */
    public function export(Request $request)
    {
        $period        = $request->query('period');
        $search        = $request->query('search');
        $departmentId  = $request->query('department_id');
        $subDeptId     = $request->query('sub_department_id');
        $status        = $request->query('status');

        $fileName = 'schedule-period-' . ($period ?? 'all') . '.xlsx';

        return Excel::download(
            new InputScheduleExport($period, $search, $departmentId, $subDeptId, $status),
            $fileName
        );
    }

    /**
     * 7. GET /api/schedule/input-schedule/sample
     */
    public function downloadSample(Request $request)
    {
        $period = $request->query('period')
            ?? Period::where('is_active', true)->orderBy('start_date')->value('code')
            ?? date('Y-m');
        $fileName = "sample-template-import-schedule-{$period}.xlsx";

        return Excel::download(new InputScheduleSampleExport($period), $fileName);
    }

    /**
     * 8. GET /api/schedule/jadwalku
     */
    public function getMySchedule(Request $request)
    {
        $defaultPeriod = Period::where('is_active', true)->orderBy('start_date')->value('code') ?? '220001';
        $period = $request->query('period', $defaultPeriod);

        $query = Schedule::with(['employee.department', 'employee.subDepartment', 'employee.grade'])
            ->where('period_code', $period);

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($empQ) use ($search) {
                      $empQ->where('full_name', 'like', "%{$search}%")
                           ->orWhere('nip', 'like', "%{$search}%");
                  });
            });
        }

        $deptVal = $request->query('department_id', $request->query('dept'));
        if (!empty($deptVal)) {
            $query->where('department_id', $deptVal);
        }

        if ($request->has('sub_department') && !empty($request->sub_department)) {
            $subDept = $request->sub_department;
            $query->where(function ($q) use ($subDept) {
                $q->where('sub_department_name', $subDept)
                  ->orWhereHas('subDepartment', function ($subQ) use ($subDept) {
                      $subQ->where('name', $subDept);
                  });
            });
        }

        if ($request->has('grade') && !empty($request->grade)) {
            $grade = $request->grade;
            $query->whereHas('employee.grade', function ($q) use ($grade) {
                $q->where('name', $grade);
            });
        }

        $perPage = (int) $request->input('per_page', 25);
        $paginated = $query->latest()->paginate($perPage);

        $mappedItems = collect($paginated->items())->map(function ($sch) use ($period) {
            return [
                'id'             => (string) $sch->id,
                'period'         => $period,
                'nip'            => $sch->nip ?? $sch->employee?->nip ?? '-',
                'name'           => $sch->employee_name ?? $sch->employee?->full_name ?? '-',
                'department_id'  => $sch->department_id,
                'department'     => $sch->department_name ?? $sch->department?->name ?? '-',
                'sub_department' => $sch->sub_department_name ?? $sch->subDepartment?->name ?? '-',
                'grade'          => $sch->employee?->grade?->name ?? 'Officer',
                'off_days'       => (int) $sch->off_days,
                'ph_days'        => (int) $sch->ph_days,
                'permit_days'    => (int) $sch->permit_days,
                'alpha_days'     => (int) $sch->alpha_days,
                'sick_days'      => 0,
                'leave_days'     => (int) $sch->leave_days,
                'late_days'      => (int) $sch->late_days,
                'work_days'      => (int) $sch->work_days,
                'total_days'     => (int) $sch->total_days,
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

   /**
     * 9. POST /api/schedule/jadwalku/export
     */
    public function exportMySchedule(Request $request)
    {
        $defaultPeriod = Period::where('is_active', true)->orderBy('start_date')->value('code') ?? '220001';
        $period        = $request->input('period', $defaultPeriod);
        $selectedIds   = $request->input('ids', []);
        $search        = $request->input('search');
        $departmentId  = $request->input('department_id', $request->input('dept'));
        $subDepartment = $request->input('sub_department');
        $grade         = $request->input('grade');

        $fileName = "rekap-jadwalku-period-{$period}.xlsx";

        return Excel::download(
            new MyScheduleExport(
                $period,
                $selectedIds,
                $search,
                $departmentId,
                $subDepartment,
                $grade
            ),
            $fileName
        );
    }
}