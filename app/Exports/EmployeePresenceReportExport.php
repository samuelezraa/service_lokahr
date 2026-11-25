<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\Period;
use App\Models\AttendanceLogDetail;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class EmployeePresenceReportExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected ?string $search;
    protected ?string $periode;
    protected ?string $startDate;
    protected ?string $endDate;

    public function __construct(
        ?string $search = null,
        ?string $periode = null,
        ?string $startDate = null,
        ?string $endDate = null
    ) {
        $this->search    = $search;
        $this->periode   = $periode;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function array(): array
    {
        $effectiveStart = !empty($this->startDate) ? Carbon::parse($this->startDate)->format('Y-m-d') : null;
        $effectiveEnd   = !empty($this->endDate) ? Carbon::parse($this->endDate)->format('Y-m-d') : null;

        if (!empty($this->periode) && (!$effectiveStart || !$effectiveEnd)) {
            $periodObj = Period::where('code', $this->periode)
                ->orWhere('id', $this->periode)
                ->first();

            if ($periodObj) {
                $effectiveStart = $effectiveStart ?? Carbon::parse($periodObj->start_date)->format('Y-m-d');
                $effectiveEnd   = $effectiveEnd   ?? Carbon::parse($periodObj->end_date)->format('Y-m-d');
            }
        }

        $isDateFiltered = (bool) ($effectiveStart || $effectiveEnd || !empty($this->periode));

        $applyDateFilter = function ($q) use ($effectiveStart, $effectiveEnd) {
            if ($effectiveStart && $effectiveEnd) {
                $q->whereBetween('date', [$effectiveStart, $effectiveEnd]);
            } elseif ($effectiveStart) {
                $q->whereDate('date', '>=', $effectiveStart);
            } elseif ($effectiveEnd) {
                $q->whereDate('date', '<=', $effectiveEnd);
            } elseif (!empty($this->periode)) {
                $q->where('date', 'like', "{$this->periode}%");
            }
        };

        $query = Department::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        if ($isDateFiltered) {
            $query->whereHas('employees.attendanceLogDetails', function ($q) use ($applyDateFilter) {
                $applyDateFilter($q);
            });
        }

        $departments = $query->get();
        $data = [];
        $index = 1;
        $grandTotalPax = 0;

        foreach ($departments as $dept) {
            $paxQuery = AttendanceLogDetail::where(function ($q) use ($dept) {
                $q->where('department_id', $dept->id)
                  ->orWhere('department_name', $dept->name)
                  ->orWhereHas('employee', function ($empQ) use ($dept) {
                      $empQ->where('department_id', $dept->id);
                  });
            });

            if ($isDateFiltered) {
                $applyDateFilter($paxQuery);
                $totalPax = $paxQuery->count();
            } else {
                $totalPax = $paxQuery->count();
                if ($totalPax === 0) {
                    $totalPax = $dept->employees()->where('is_active', true)->count();
                }
            }

            $grandTotalPax += $totalPax;

            $data[] = [
                $index++,
                $dept->name ?? '-',
                $totalPax,
            ];
        }

        $data[] = [
            '',
            'TOTAL SELURUHNYA',
            $grandTotalPax,
        ];

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Departemen',
            'Total Pax Kehadiran',
        ];
    }
}