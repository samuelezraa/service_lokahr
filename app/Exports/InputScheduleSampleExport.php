<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Period;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InputScheduleSampleExport implements FromArray, WithEvents, ShouldAutoSize
{
    protected string $periodCode;
    protected array $dates = [];

    public function __construct(?string $period = null)
    {
        $periodModel = $this->findPeriodModel($period);

        if ($periodModel) {
            $this->periodCode = $periodModel->code;
            $startDate = Carbon::parse($periodModel->start_date);
            $endDate   = Carbon::parse($periodModel->end_date);
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

    protected function findPeriodModel(?string $period): ?Period
    {
        if (!empty($period)) {
            // 1. Cari persis berdasarkan code (misal: '220001')
            $byCode = Period::where('code', $period)->first();
            if ($byCode) {
                return $byCode;
            }

            // 2. Jika frontend mengirim format 'YYYY-MM' (misal: '2026-10'), cari periode aktif yang berakhir/mencakup bulan tsb
            if (preg_match('/^\d{4}-\d{2}$/', $period)) {
                $byEndMonth = Period::where('is_active', true)
                    ->where('end_date', 'like', "{$period}%")
                    ->first();
                if ($byEndMonth) {
                    return $byEndMonth;
                }
            }
        }

        // Fallback ke periode aktif pertama di master
        return Period::where('is_active', true)->orderBy('start_date')->first();
    }

    public function array(): array
    {
        $dayCount = count($this->dates);

        // Baris 1 Header
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

        // Baris 2 Header (Nomor tanggal sesuai start_date s.d. end_date, misal 21..30 lalu 1..20)
        $headerRow2 = ['', '', '', '', '', ''];
        foreach ($this->dates as $dateObj) {
            $headerRow2[] = (int) $dateObj->format('j');
        }

        $rows = [$headerRow1, $headerRow2];

        // Ambil semua karyawan Aktif
        $employees = Employee::with(['department', 'subDepartment'])
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get();

        foreach ($employees as $emp) {
            $schemeName = $emp->work_scheme
                ?? $emp->schedule_scheme
                ?? 'Skema 5-2';

            $row = [
                $this->periodCode,
                $emp->nip ?? '-',
                $emp->full_name ?? '-',
                $emp->department?->name ?? '-',
                $emp->subDepartment?->name ?? '-',
                $schemeName,
            ];

            for ($i = 0; $i < $dayCount; $i++) {
                $row[] = '00:00:00';
            }

            $rows[] = $row;
        }

        // Fallback contoh jika database karyawan aktif masih kosong
        if ($employees->isEmpty()) {
            $sampleRow = [$this->periodCode, '1001', 'Antika Lorien', 'IT', 'Frontend Engineering', 'Skema 5-2'];
            for ($i = 0; $i < $dayCount; $i++) {
                $sampleRow[] = '00:00:00';
            }
            $rows[] = $sampleRow;
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $dayCount = count($this->dates);
                $lastColIndex = 6 + $dayCount;
                $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);

                // Merge vertikal kolom A..F (baris 1 & 2)
                foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $col) {
                    $sheet->mergeCells("{$col}1:{$col}2");
                }

                // Merge horizontal "Jam masuk" dari G1 sampai kolom tanggal terakhir
                if ($dayCount > 0) {
                    $sheet->mergeCells("G1:{$lastColLetter}1");
                }

                // Styling Header
                $sheet->getStyle("A1:{$lastColLetter}2")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D1D5DB'],
                        ],
                    ],
                ]);
            },
        ];
    }
}