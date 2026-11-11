<?php

namespace App\Exports;

use App\Models\Department;
use App\Models\AttendanceLogDetail;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize; // 🟢 Tambahkan AutoSize

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
        $query = Department::query();

        if (!empty($this->search)) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        $departments = $query->get();
        $data = [];
        $index = 1;
        $grandTotalPax = 0;

        foreach ($departments as $dept) {
            $paxQuery = AttendanceLogDetail::whereHas('employee', function ($q) use ($dept) {
                $q->where('department_id', $dept->id);
            });

            if (!empty($this->startDate) && !empty($this->endDate)) {
                $paxQuery->whereBetween('date', [$this->startDate, $this->endDate]);
            } elseif (!empty($this->periode)) {
                $paxQuery->where('date', 'like', "{$this->periode}%");
            }

            $totalPax = $paxQuery->count();
            if ($totalPax === 0 && empty($this->startDate) && empty($this->periode)) {
                $totalPax = $dept->employees()->where('is_active', true)->count();
            }

            $grandTotalPax += $totalPax;

            $data[] = [
                $index++,
                $dept->name ?? '-',
                $totalPax,
            ];
        }

        // Baris Grand Total
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