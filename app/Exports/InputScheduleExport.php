<?php

namespace App\Exports;

use App\Models\Period;
use App\Models\Schedule;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InputScheduleExport implements FromArray, WithEvents, ShouldAutoSize, WithCustomCsvSettings
{
    protected ?string $period;
    protected ?string $search;
    protected ?string $departmentId;
    protected ?string $subDepartmentId;
    protected ?string $status;
    protected string $periodCode;
    protected array $dates = [];

    public function __construct(
        ?string $period = null,
        ?string $search = null,
        ?string $departmentId = null,
        ?string $subDepartmentId = null,
        ?string $status = null
    ) {
        $this->period          = $period;
        $this->search          = $search;
        $this->departmentId    = $departmentId;
        $this->subDepartmentId = $subDepartmentId;
        $this->status          = $status;

        $periodModel = $this->findPeriodModel($period);

        if ($periodModel) {
            $this->periodCode = $periodModel->code;
            $startDate        = Carbon::parse($periodModel->start_date);
            $endDate          = Carbon::parse($periodModel->end_date);
        } else {
            $this->periodCode = $period ?: date('Y-m');
            try {
                $startDate = Carbon::createFromFormat('Y-m', $this->periodCode)->startOfMonth();
            } catch (\Exception $e) {
                $startDate = Carbon::now()->startOfMonth();
            }
            $endDate = $startDate->copy()->endOfMonth();
        }

        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $this->dates[] = $cursor->copy();
            $cursor->addDay();
        }
    }

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'use_bom'   => true,
        ];
    }

    protected function findPeriodModel(?string $period): ?Period
    {
        if (!empty($period)) {
            $byCode = Period::where('code', $period)->first();
            if ($byCode) {
                return $byCode;
            }

            if (preg_match('/^\d{4}-\d{2}$/', $period)) {
                $byEndMonth = Period::where('is_active', true)
                    ->where('end_date', 'like', "{$period}%")
                    ->first();
                if ($byEndMonth) {
                    return $byEndMonth;
                }
            }
        }

        return Period::where('is_active', true)->orderBy('start_date')->first();
    }

    public function array(): array
    {
        $dayCount = count($this->dates);

        // Baris 1 Header (identik dengan InputScheduleSampleExport)
        $headerRow1 = [
            'Periode',
            'NIP',
            'Nama Karyawan',
            'Departemen',
            'Sub Departemen',
            'Skema Hari Kerja',
            'Jam masuk',
        ];
        for ($i = 1; $i < $dayCount; $i++) {
            $headerRow1[] = '';
        }

        // Baris 2 Header (nomor tanggal sesuai start_date s.d. end_date)
        $headerRow2 = ['', '', '', '', '', ''];
        foreach ($this->dates as $dateObj) {
            $headerRow2[] = (int) $dateObj->format('j');
        }

        $relations = ['employee', 'department', 'subDepartment', 'scheduleDetails'];
        if (method_exists(Schedule::class, 'dailyDetails')) {
            $relations[] = 'dailyDetails';
        }

        $query = Schedule::with($relations)->whereNotNull('employee_id');

        $targetPeriod = $this->period ?: $this->periodCode;
        if (!empty($targetPeriod)) {
            $query->where('period_code', $targetPeriod);
        }

        if (!empty($this->search)) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (!empty($this->departmentId)) {
            $query->where('department_id', $this->departmentId);
        }

        if (!empty($this->subDepartmentId)) {
            $query->where('sub_department_id', $this->subDepartmentId);
        }

        if ($this->status !== null && $this->status !== '') {
            $boolStatus = filter_var($this->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $boolStatus);
        }

        $schedules = $query->orderBy('employee_name')->get();
        $rows      = [$headerRow1, $headerRow2];

        foreach ($schedules as $sch) {
            $detailsByDay = $sch->scheduleDetails
                ? $sch->scheduleDetails->keyBy('day_of_week')
                : collect();

            $dailyByDate = (isset($sch->dailyDetails) && $sch->dailyDetails)
                ? $sch->dailyDetails->keyBy(function ($d) {
                    return Carbon::parse($d->date)->format('Y-m-d');
                })
                : collect();

            $row = [
                $sch->period_code ?? $this->periodCode,
                $sch->nip ?? $sch->employee?->nip ?? '-',
                $sch->employee_name ?? $sch->employee?->full_name ?? '-',
                $sch->department_name ?? $sch->department?->name ?? '-',
                $sch->sub_department_name ?? $sch->subDepartment?->name ?? '-',
                $sch->work_scheme ?? $sch->employee?->work_scheme ?? 'Skema 5-2',
            ];

            foreach ($this->dates as $dateObj) {
                $dateStr          = $dateObj->format('Y-m-d');
                $dayOfWeekEnglish = strtolower($dateObj->format('l'));
                $dailyDetail      = $dailyByDate->get($dateStr);
                $weeklyDetail     = $detailsByDay->get($dayOfWeekEnglish);

                if ($dailyDetail) {
                    $status  = (string) ($dailyDetail->attendance_status ?? 'Berangkat');
                    $checkIn = $dailyDetail->scheduled_check_in ?? '00:00:00';
                    $row[]   = ($status === 'Berangkat') ? ($checkIn ?: '08:00:00') : '00:00:00';
                } elseif ($weeklyDetail) {
                    $isActive = (bool) ($weeklyDetail->is_active ?? true);
                    $row[]    = $isActive ? ($weeklyDetail->check_in_time ?? '08:00:00') : '00:00:00';
                } else {
                    $isWeekend = in_array((int) $dateObj->format('w'), [0, 6], true);
                    $row[]     = $isWeekend ? '00:00:00' : '08:00:00';
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet         = $event->sheet->getDelegate();
                $dayCount      = count($this->dates);
                $lastColIndex  = 6 + $dayCount;
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);

                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                if ($dayCount > 0) {
                    $sheet->mergeCells("G1:{$lastColLetter}1");
                }

                $sheet->getStyle("A1:{$lastColLetter}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);
            },
        ];
    }
}
