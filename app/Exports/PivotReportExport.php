<?php

namespace App\Exports;

use App\Models\AttendanceLogDetail;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;
use App\Models\Period;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PivotReportExport implements FromArray, WithHeadings, WithCustomCsvSettings
{
    protected ?string $search;
    protected ?string $periode;
    protected ?string $startDate;
    protected ?string $endDate;
    protected ?string $dept;
    protected ?string $subDept;
    protected ?string $pos;
    protected ?string $grade;
    protected ?string $skema;

    public function __construct(
        ?string $search = null,
        ?string $periode = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $dept = null,
        ?string $subDept = null,
        ?string $pos = null,
        ?string $grade = null,
        ?string $skema = null
    ) {
        $this->search    = $search;
        $this->periode   = $periode;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->dept      = $dept;
        $this->subDept   = $subDept;
        $this->pos       = $pos;
        $this->grade     = $grade;
        $this->skema     = $skema;
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
        $query = Employee::with([
            'department',
            'subDepartment',
            'position',
            'grade',
            'workScheduleScheme',
        ])->where('is_active', true);

        // 1. Search Global
        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // 2. Filter Periode
        $selectedPeriodObj = null;
        if (!empty($this->periode)) {
            $periode = $this->periode;
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
        if (!empty($this->dept)) {
            $dept = $this->dept;
            $query->whereHas('department', function ($q) use ($dept) {
                $q->where('name', $dept)->orWhere('code', $dept)->orWhere('id', $dept);
            });
        }

        if (!empty($this->subDept)) {
            $subDept = $this->subDept;
            $query->whereHas('subDepartment', function ($q) use ($subDept) {
                $q->where('name', $subDept)->orWhere('code', $subDept)->orWhere('id', $subDept);
            });
        }

        if (!empty($this->pos)) {
            $pos = $this->pos;
            $query->whereHas('position', function ($q) use ($pos) {
                $q->where('name', $pos)->orWhere('title', $pos)->orWhere('code', $pos)->orWhere('id', $pos);
            });
        }

        if (!empty($this->grade)) {
            $grade = $this->grade;
            $query->whereHas('grade', function ($q) use ($grade) {
                $q->where('name', $grade)->orWhere('code', $grade)->orWhere('id', $grade);
            });
        }

        if (!empty($this->skema)) {
            $skema = $this->skema;
            $query->whereHas('workScheduleScheme', function ($q) use ($skema) {
                $q->where('name', $skema)->orWhere('code', $skema)->orWhere('id', $skema);
            });
        }

        // 4. Filter Rentang Tanggal Manual
        if (!empty($this->startDate) || !empty($this->endDate)) {
            $startDate = $this->startDate;
            $endDate   = $this->endDate;

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

        $activePeriodObj = $selectedPeriodObj ?? Period::where('is_active', true)->orderByDesc('start_date')->first();
        $effectiveStart  = !empty($this->startDate)
            ? $this->startDate
            : (!empty($activePeriodObj?->start_date) ? Carbon::parse($activePeriodObj->start_date)->format('Y-m-d') : null);
        $effectiveEnd    = !empty($this->endDate)
            ? $this->endDate
            : (!empty($activePeriodObj?->end_date) ? Carbon::parse($activePeriodObj->end_date)->format('Y-m-d') : null);

        $employees = $query->latest()->get();
        $data = [];

        foreach ($employees as $emp) {
            $masuk = 0; $libur = 0; $ph = 0; $izin = 0;
            $alfa = 0; $sakit = 0; $cuti = 0; $terlambat = 0;

            $empCodes = collect([$emp->nip, $emp->employee_id])
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values();

            $attRecord = null;
            if (empty($this->startDate) && empty($this->endDate) && $empCodes->isNotEmpty()) {
                $attQuery = EmployeeAttendance::where(function ($q) use ($empCodes) {
                    $q->whereIn('nip', $empCodes)
                      ->orWhereIn('employee_id', $empCodes);
                });

                if (!empty($this->periode)) {
                    $periodCode = $selectedPeriodObj?->code ?? $this->periode;
                    $attQuery->where(function ($q) use ($periodCode) {
                        $q->where('period_code', $periodCode)
                          ->orWhere('period_code', $this->periode);
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

            $data[] = [
                $emp->employee_id ?? '-',
                $emp->nip ?? '-',
                $emp->full_name ?? '-',
                $emp->department?->name ?? '-',
                $emp->subDepartment?->name ?? '-',
                $emp->position?->title ?? $emp->position?->name ?? '-',
                $emp->grade?->name ?? '-',
                $emp->workScheduleScheme?->name ?? $emp->workScheduleScheme?->code ?? '5-2',
                $masuk,
                $libur,
                $ph,
                $izin,
                $alfa,
                $sakit,
                $cuti,
                $terlambat,
            ];
        }

        return $data;
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
            'Skema',
            'Masuk',
            'Libur',
            'PH',
            'Izin',
            'Alfa',
            'Sakit',
            'Cuti',
            'Terlambat',
        ];
    }
}