<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Period;
use App\Models\Schedule;
use App\Models\ScheduleDailyDetail;
use App\Models\ScheduleDetail;
use App\Models\SubDepartment;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToArray;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class InputScheduleImport implements ToArray
{
    protected ?string $overridePeriod;

    public function __construct(?string $overridePeriod = null)
    {
        $this->overridePeriod = $overridePeriod;
    }

    private function parseExcelTime($value): string
    {
        if ($value === null || $value === '' || $value === '-' || $value === '0') {
            return '00:00:00';
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('H:i:s');
            }
            return Carbon::parse((string) $value)->format('H:i:s');
        } catch (\Exception $e) {
            return '00:00:00';
        }
    }

    private function mapHeaderDaysToDates(string $periodCode, array $dateColumns): array
    {
        $periodModel = Period::where('code', $periodCode)->first();
        if (!$periodModel && preg_match('/^\d{4}-\d{2}$/', $periodCode)) {
            $periodModel = Period::where('is_active', true)
                ->where('end_date', 'like', "{$periodCode}%")
                ->first();
        }

        $mappedDates = [];

        if ($periodModel) {
            $cursor           = Carbon::parse($periodModel->start_date);
            $endDate          = Carbon::parse($periodModel->end_date);
            $periodDatesByDay = [];

            while ($cursor->lte($endDate)) {
                $dayNum = (int) $cursor->format('j');
                if (!isset($periodDatesByDay[$dayNum])) {
                    $periodDatesByDay[$dayNum] = $cursor->copy();
                }
                $cursor->addDay();
            }

            foreach ($dateColumns as $colIndex => $dayNumber) {
                if (isset($periodDatesByDay[$dayNumber])) {
                    $mappedDates[$colIndex] = $periodDatesByDay[$dayNumber];
                }
            }

            return $mappedDates;
        }

        if (str_contains($periodCode, '-')) {
            [$year, $month] = explode('-', $periodCode);
            foreach ($dateColumns as $colIndex => $dayNumber) {
                if (checkdate((int) $month, $dayNumber, (int) $year)) {
                    $mappedDates[$colIndex] = Carbon::createFromDate((int) $year, (int) $month, $dayNumber);
                }
            }
        }

        return $mappedDates;
    }

    public function array(array $rows): void
    {
        if (count($rows) < 3) {
            return;
        }

        // Baris index 1 (baris ke-2 Excel) berisi nomor tanggal mulai kolom G (index 6)
        $dateHeaderRow = $rows[1];
        $dateColumns   = [];
        for ($col = 6; $col < count($dateHeaderRow); $col++) {
            $dayNum = trim((string) ($dateHeaderRow[$col] ?? ''));
            if ($dayNum !== '' && is_numeric($dayNum)) {
                $dateColumns[$col] = (int) $dayNum;
            }
        }

        $hasDailyDetailModel = class_exists(ScheduleDailyDetail::class);

        // Data karyawan dimulai pada baris index 2 (baris ke-3 Excel)
        for ($r = 2; $r < count($rows); $r++) {
            $row = $rows[$r];

            $periodCell = trim((string) ($row[0] ?? ''));
            $nip        = trim((string) ($row[1] ?? ''));
            $nama       = trim((string) ($row[2] ?? ''));
            $deptVal    = trim((string) ($row[3] ?? ''));
            $subDeptVal = trim((string) ($row[4] ?? ''));
            $workScheme = trim((string) ($row[5] ?? ''));

            if ($nip === '') {
                continue;
            }

            $periodCode  = $this->overridePeriod ?: ($periodCell !== '' ? $periodCell : date('Y-m'));
            $mappedDates = $this->mapHeaderDaysToDates($periodCode, $dateColumns);

            $employee = Employee::with(['department', 'subDepartment'])
                ->where('nip', $nip)
                ->first();

            $departmentId   = $employee?->department_id;
            $departmentName = $employee?->department?->name;
            if ($deptVal !== '' && $deptVal !== '-') {
                $deptObj = is_numeric($deptVal)
                    ? Department::find($deptVal)
                    : Department::where('name', $deptVal)->first();
                if ($deptObj) {
                    $departmentId   = $deptObj->id;
                    $departmentName = $deptObj->name;
                } else {
                    $departmentName = $deptVal;
                }
            }

            $subDepartmentId   = $employee?->sub_department_id;
            $subDepartmentName = $employee?->subDepartment?->name;
            if ($subDeptVal !== '' && $subDeptVal !== '-') {
                $subDeptObj = is_numeric($subDeptVal)
                    ? SubDepartment::find($subDeptVal)
                    : SubDepartment::where('name', $subDeptVal)->first();
                if ($subDeptObj) {
                    $subDepartmentId   = $subDeptObj->id;
                    $subDepartmentName = $subDeptObj->name;
                } else {
                    $subDepartmentName = $subDeptVal;
                }
            }

            $resolvedScheme = ($workScheme !== '' && $workScheme !== '-')
                ? $workScheme
                : ($employee?->work_scheme ?? $employee?->schedule_scheme ?? 'Skema 5-2');

            $workDays               = 0;
            $offDays                = 0;
            $dailyScheduleByDayName = [];
            $dailyEntriesByDate     = [];

            foreach ($dateColumns as $colIndex => $dayNumber) {
                $checkIn = $this->parseExcelTime($row[$colIndex] ?? '00:00:00');
                $isLibur = ($checkIn === '00:00:00');
                $checkOut = $isLibur ? '00:00:00' : Carbon::parse($checkIn)->addHours(9)->format('H:i:s');

                if ($isLibur) {
                    $offDays++;
                } else {
                    $workDays++;
                }

                if (isset($mappedDates[$colIndex])) {
                    $dateObj   = $mappedDates[$colIndex];
                    $dayOfWeek = strtolower($dateObj->format('l'));

                    if (!isset($dailyScheduleByDayName[$dayOfWeek]) || !$isLibur) {
                        $dailyScheduleByDayName[$dayOfWeek] = [
                            'check_in_time'  => $checkIn,
                            'check_out_time' => $checkOut,
                            'is_active'      => !$isLibur,
                        ];
                    }

                    $dailyEntriesByDate[$dateObj->format('Y-m-d')] = [
                        'day_of_week'         => $dayOfWeek,
                        'attendance_status'   => $isLibur ? 'Libur' : 'Berangkat',
                        'scheduled_check_in'  => $checkIn,
                        'scheduled_check_out' => $checkOut,
                    ];
                }
            }

            $totalDays = count($dateColumns) > 0 ? count($dateColumns) : 30;

            $schedule = Schedule::updateOrCreate(
                [
                    'period_code' => $periodCode,
                    'nip'         => $nip,
                ],
                [
                    'employee_id'         => $employee?->id,
                    'employee_name'       => ($nama !== '' && $nama !== '-') ? $nama : ($employee?->full_name ?? '-'),
                    'department_id'       => $departmentId,
                    'department_name'     => $departmentName,
                    'sub_department_id'   => $subDepartmentId,
                    'sub_department_name' => $subDepartmentName,
                    'work_scheme'         => $resolvedScheme,
                    'is_active'           => true,
                    'total_days'          => $totalDays,
                    'work_days'           => $workDays,
                    'off_days'            => $offDays,
                ]
            );

            foreach ($dailyScheduleByDayName as $dayOfWeek => $detailData) {
                ScheduleDetail::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'day_of_week' => $dayOfWeek,
                    ],
                    $detailData
                );
            }

            if ($hasDailyDetailModel) {
                foreach ($dailyEntriesByDate as $dateStr => $entryData) {
                    ScheduleDailyDetail::updateOrCreate(
                        [
                            'schedule_id' => $schedule->id,
                            'date'        => $dateStr,
                        ],
                        $entryData
                    );
                }
            }
        }
    }
}