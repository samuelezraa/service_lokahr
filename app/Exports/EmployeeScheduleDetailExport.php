<?php

namespace App\Exports;

use App\Models\Schedule;
use App\Models\Period;
use App\Models\EmployeeAttendanceDetail;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Enumerable;

class EmployeeScheduleDetailExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected Schedule $schedule;
    protected string $periodCode;
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct(Schedule $schedule, ?string $periodCode = null)
    {
        $this->schedule   = $schedule->load([
            'employee',
            'department',
            'subDepartment',
            'scheduleDetails',
            'dailyDetails',
        ]);
        $this->periodCode = $periodCode ?: ($this->schedule->period_code ?? '');

        $periodModel = $this->periodCode
            ? Period::where('code', $this->periodCode)->first()
            : null;

        if ($periodModel) {
            $this->startDate = Carbon::parse($periodModel->start_date);
            $this->endDate   = Carbon::parse($periodModel->end_date);
        } elseif (!empty($this->periodCode) && str_contains($this->periodCode, '-')) {
            [$year, $month]  = explode('-', $this->periodCode);
            $this->startDate = Carbon::createFromDate((int) $year, (int) $month, 1);
            $this->endDate   = $this->startDate->copy()->endOfMonth();
        } else {
            $this->startDate = Carbon::now()->startOfMonth();
            $this->endDate   = $this->startDate->copy()->endOfMonth();
        }
    }

    public function collection(): Enumerable
    {
        $details = $this->schedule->scheduleDetails->keyBy('day_of_week');

        $dailyDetails = ($this->schedule->dailyDetails ?? collect())->keyBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
        });

        $nip = trim((string) ($this->schedule->nip ?? $this->schedule->employee?->nip ?? ''));

        $actualAttendances = EmployeeAttendanceDetail::whereHas('employeeAttendance', function ($q) use ($nip) {
            $q->where('nip', $nip)
              ->where('period_code', $this->periodCode);
        })->get()->keyBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
        });

        $dayNames = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];

        $rows   = collect();
        $cursor = $this->startDate->copy();

        while ($cursor->lte($this->endDate)) {
            $dateStr          = $cursor->format('Y-m-d');
            $dayIdx           = (int) $cursor->format('w');
            $dayName          = $dayNames[$dayIdx] ?? $cursor->format('l');
            $dayOfWeekEnglish = strtolower($cursor->format('l'));

            $detail      = $details->get($dayOfWeekEnglish);
            $dailyDetail = $dailyDetails->get($dateStr);
            $actualLog   = $actualAttendances->get($dateStr);

            $isWeekend = in_array($dayIdx, [0, 6], true);
            $isLibur   = $detail ? (bool) ($detail->is_day_off || !$detail->is_active) : $isWeekend;

            // Default rekap harian (1 = ya pada hari tersebut, 0 = tidak)
            $masuk     = 0;
            $libur     = 0;
            $ph        = 0;
            $izin      = 0;
            $alfa      = 0;
            $sakit     = 0;
            $cuti      = 0;
            $terlambat = 0;

            // Prioritaskan status harian dari dailyDetails (hasil Edit Jadwal Karyawan)
            $dailyStatus = trim((string) ($dailyDetail->attendance_status ?? ''));

            if ($dailyStatus !== '') {
                switch (strtolower($dailyStatus)) {
                    case 'libur':
                        $libur = 1;
                        break;
                    case 'ph':
                        $ph = 1;
                        break;
                    case 'izin':
                        $izin = 1;
                        break;
                    case 'cuti':
                        $cuti = 1;
                        break;
                    case 'berangkat':
                    case 'masuk':
                    default:
                        $masuk = 1;
                        break;
                }
            } else {
                // Fallback ke log kehadiran / detail mingguan jika belum ada record di dailyDetails
                $libur = $isLibur ? 1 : 0;

                $rawStatus = strtolower(trim((string) (
                    $actualLog->status
                    ?? $actualLog->attendance_status
                    ?? $detail->attendance_status
                    ?? ''
                )));
                $rawNotes = strtolower(trim((string) (
                    $actualLog->notes
                    ?? $dailyDetail->notes
                    ?? $detail->annual_event_name
                    ?? ''
                )));

                if (str_contains($rawStatus, 'ph') || str_contains($rawStatus, 'holiday') || (!empty($detail?->annual_event_name) && $isLibur)) {
                    $ph    = 1;
                    $libur = 0;
                } elseif (str_contains($rawStatus, 'cuti') || str_contains($rawStatus, 'leave') || str_contains($rawNotes, 'cuti')) {
                    $cuti  = 1;
                    $libur = 0;
                } elseif (str_contains($rawStatus, 'sakit') || str_contains($rawStatus, 'sick') || str_contains($rawNotes, 'sakit')) {
                    $sakit = 1;
                    $libur = 0;
                } elseif (str_contains($rawStatus, 'izin') || str_contains($rawStatus, 'permit') || str_contains($rawNotes, 'izin')) {
                    $izin  = 1;
                    $libur = 0;
                } elseif (str_contains($rawStatus, 'alfa') || str_contains($rawStatus, 'alpha') || str_contains($rawStatus, 'absent')) {
                    $alfa  = 1;
                    $libur = 0;
                } elseif (!$isLibur) {
                    $masuk = 1;
                }
            }

            // Deteksi terlambat jika masuk / bekerja dan actual_check_in melewati scheduledCheckIn
            if ($masuk === 1 || ($dailyStatus === '' && !$isLibur)) {
                $schedIn  = $dailyDetail->scheduled_check_in ?? ($detail->check_in_time ?? null);
                $actualIn = $actualLog->actual_check_in ?? null;

                if (
                    ($schedIn && $actualIn && $actualIn !== '00:00:00' && $schedIn !== '00:00:00' && $actualIn > $schedIn)
                ) {
                    $terlambat = 1;
                }
            }

            $rows->push((object) [
                'day_name'  => $dayName,
                'date'      => $dateStr,
                'masuk'     => $masuk,
                'libur'     => $libur,
                'ph'        => $ph,
                'izin'      => $izin,
                'alfa'      => $alfa,
                'sakit'     => $sakit,
                'cuti'      => $cuti,
                'terlambat' => $terlambat,
            ]);

            $cursor->addDay();
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Hari',
            'Tanggal',
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

    public function map($row): array
    {
        return [
            $row->day_name,
            $row->date,
            $row->masuk,
            $row->libur,
            $row->ph,
            $row->izin,
            $row->alfa,
            $row->sakit,
            $row->cuti,
            $row->terlambat,
        ];
    }
}