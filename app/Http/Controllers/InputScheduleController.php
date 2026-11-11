<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ScheduleDetail;
use App\Models\Employee;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Exports\InputScheduleExport;
use App\Exports\InputScheduleSampleExport;
use App\Imports\InputScheduleImport;
use Maatwebsite\Excel\Facades\Excel;

class InputScheduleController extends Controller
{
    /**
     * 1. GET /api/schedule/input-schedule
     * Mendapatkan daftar jadwal & ringkasan berdasarkan periode dengan Pagination Server-Side, Search, & Filter Dept/SubDept
     */
    public function index(Request $request)
    {
        $period = $request->query('period', date('Y-m'));

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
     * Mendapatkan pengaturan jam kerja mingguan global terkini dari database
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
     */
public function showDetail(Request $request, $id)
    {
        $schedule = Schedule::with(['employee', 'department', 'subDepartment', 'scheduleDetails'])
            ->findOrFail($id);

        $period = $schedule->period_code ?? $request->query('period', '2026-10');
        list($year, $month) = explode('-', $period);
        $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);

        $details = $schedule->scheduleDetails->keyBy('day_of_week');

        // 1. Ambil data absensi aktual dari database berdasarkan NIP & Periode
        $actualAttendances = \App\Models\EmployeeAttendanceDetail::whereHas('employeeAttendance', function($q) use ($schedule, $period) {
            $q->where('nip', trim($schedule->nip))
              ->where('period_code', $period);
        })->get()->keyBy(function($item) {
            return date('Y-m-d', strtotime($item->date));
        });

        $scheduleList = [];
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        for ($i = 1; $i <= $totalDaysInMonth; $i++) {
            $dateStr = sprintf('%s-%s-%02d', $year, $month, $i);
            $timestamp = strtotime($dateStr);
            $dayOfWeekIndex = date('w', $timestamp);
            $dayName = $dayNames[$dayOfWeekIndex];

            $dayOfWeekEnglish = strtolower(date('l', $timestamp));
            $detail = $details->get($dayOfWeekEnglish);
            
            // Cari data absensi aktual berdasarkan tanggal (misal: "2026-10-01")
            $actualLog = $actualAttendances->get($dateStr);

            $isWeekend = ($dayOfWeekIndex == 0 || $dayOfWeekIndex == 6);
            $isLibur = $detail ? ($detail->is_day_off || !$detail->is_active) : $isWeekend;

            $status = $isLibur ? 'Libur' : 'Berangkat';
            $checkIn = $detail ? $detail->check_in_time : ($isWeekend ? '00:00:00' : '08:00:00');
            $checkOut = $detail ? $detail->check_out_time : ($isWeekend ? '00:00:00' : '17:00:00');

            // 2. Ambil jam aktual secara dinamis dari database (jika ada)
            $actualCheckIn = $actualLog ? ($actualLog->actual_check_in ?? '00:00:00') : '00:00:00';
            $actualCheckOut = $actualLog ? ($actualLog->actual_check_out ?? '00:00:00') : '00:00:00';

            $scheduleList[] = [
                'id'                => $detail ? (string) $detail->id : 'temp_' . $i,
                'no'                => $i,
                'dayName'           => $dayName,
                'date'              => $dateStr,
                'day_of_week'       => $dayOfWeekEnglish,
                'attendanceStatus'  => $status,
                'scheduledCheckIn'  => $checkIn,
                'scheduledCheckOut' => $checkOut,
                'actualCheckIn'     => $actualCheckIn,   // 👈 Wajib menggunakan variabel dinamis
                'actualCheckOut'    => $actualCheckOut,  // 👈 Wajib menggunakan variabel dinamis
                'notes'             => $actualLog?->notes ?? ($detail?->annual_event_name ?? '-'),
                'ref'               => $schedule->ref ?? $period,
            ];
        }

        return response()->json([
            'success' => true,
            'employeeInfo' => [
                'id'            => $schedule->id,
                'name'          => $schedule->employee_name ?? $schedule->employee?->full_name ?? '-',
                'nip'           => $schedule->nip ?? $schedule->employee?->nip ?? '-',
                'period'        => date('F Y', strtotime("$year-$month-01")),
                'dateRange'     => "$year-$month-01 — $year-$month-$totalDaysInMonth",
                'department'    => $schedule->department_name ?? $schedule->department?->name ?? '-',
                'subDepartment' => $schedule->sub_department_name ?? $schedule->subDepartment?->name ?? '-',
                'scheme'        => $schedule->work_scheme ?? 'Skema 5-2',
                'hoursPerDay'   => 9,
                'summary'       => [
                    'totalPeriodDays' => $totalDaysInMonth,
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
     * Menyimpan pengaturan jam kerja mingguan global ke seluruh karyawan
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
     * Mengelola upload file Excel/CSV data jadwal kehadiran
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
     * Mengunduh data jadwal kehadiran dalam format Excel (.xlsx)
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
     * Mengunduh template sample file Excel untuk Import Kehadiran
     */
    public function downloadSample()
    {
        return Excel::download(new InputScheduleSampleExport, 'sample-template-import-schedule.xlsx');
    }

    /**
     * 8. GET /api/schedule/jadwalku
     */
   /**
     * 8. GET /api/schedule/jadwalku
     */
 /**
     * 8. GET /api/schedule/jadwalku
     * Mendapatkan rekapitulasi jadwalku dengan Server-Side Pagination, Search, & Filter (Dept, SubDept, Grade)
     */
    public function getMySchedule(Request $request)
    {
        $period = $request->query('period', '2026-10');

        $query = Schedule::with(['employee.department', 'employee.subDepartment', 'employee.grade'])
            ->where('period_code', $period);

        // Server-Side Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhereHas('employee', function($empQ) use ($search) {
                      $empQ->where('full_name', 'like', "%{$search}%")
                           ->orWhere('nip', 'like', "%{$search}%");
                  });
            });
        }

        // Server-Side Filter Departemen (dept / department_id)
        $deptVal = $request->query('department_id', $request->query('dept'));
        if (!empty($deptVal)) {
            $query->where('department_id', $deptVal);
        }

        // Server-Side Filter Sub Departemen
        if ($request->has('sub_department') && !empty($request->sub_department)) {
            $subDept = $request->sub_department;
            $query->where(function($q) use ($subDept) {
                $q->where('sub_department_name', $subDept)
                  ->orWhereHas('subDepartment', function($subQ) use ($subDept) {
                      $subQ->where('name', $subDept);
                  });
            });
        }

        // Server-Side Filter Grade
        if ($request->has('grade') && !empty($request->grade)) {
            $grade = $request->grade;
            $query->whereHas('employee.grade', function($q) use ($grade) {
                $q->where('name', $grade);
            });
        }

        $perPage = (int) $request->input('per_page', 10);
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
   /**
     * 9. POST /api/schedule/jadwalku/export
     * Mengunduh rekapitulasi jadwalku dalam format CSV (.csv)
     */
   /**
     * 9. POST /api/schedule/jadwalku/export
     * Mengunduh rekapitulasi jadwalku dalam format Excel (.xlsx) yang rapi dan terpisah kolomnya
     */
    public function exportMySchedule(Request $request)
    {
        $period = $request->input('period', '2026-10');
        $selectedIds = $request->input('ids', []);

        $fileName = "rekap-jadwalku-period-{$period}.xlsx";

        return Excel::download(
            new class($period, $selectedIds) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
                protected $period;
                protected $selectedIds;

                public function __construct($period, $selectedIds) {
                    $this->period = $period;
                    $this->selectedIds = array_filter($selectedIds);
                }

                public function array(): array {
                    $query = Schedule::with(['employee.department', 'employee.subDepartment', 'employee.grade'])
                        ->where('period_code', $this->period);

                    if (!empty($this->selectedIds)) {
                        $query->whereIn('id', $this->selectedIds);
                    }

                    $schedules = $query->get();
                    $data = [];

                    foreach ($schedules as $sch) {
                        $data[] = [
                            $sch->nip ?? $sch->employee?->nip ?? '-',
                            $sch->employee_name ?? $sch->employee?->full_name ?? '-',
                            $sch->department_name ?? $sch->department?->name ?? '-',
                            $sch->sub_department_name ?? $sch->subDepartment?->name ?? '-',
                            $sch->employee?->grade?->name ?? 'Officer',
                            (int) $sch->off_days,
                            (int) $sch->ph_days,
                            (int) $sch->permit_days,
                            (int) $sch->alpha_days,
                            0, // sakit_days
                            (int) $sch->leave_days,
                            (int) $sch->late_days,
                            (int) $sch->work_days,
                            (int) $sch->total_days,
                        ];
                    }

                    return $data;
                }

                public function headings(): array {
                    return [
                        'NIP', 
                        'Nama Karyawan', 
                        'Departemen', 
                        'Sub Departemen', 
                        'Grade', 
                        'Libur', 
                        'PH', 
                        'Izin', 
                        'Alfa', 
                        'Sakit', 
                        'Cuti', 
                        'Terlambat', 
                        'Masuk', 
                        'Total Hari'
                    ];
                }
            },
            $fileName
        );
    }

    
}