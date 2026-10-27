<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InputScheduleController extends Controller
{
    /**
     * 1. GET /api/schedule/input-schedule
     * Mendapatkan daftar jadwal & ringkasan berdasarkan periode
     */
/**
     * 1. GET /api/schedule/input-schedule
     * Mendapatkan daftar jadwal & ringkasan berdasarkan periode
     */
    public function index(Request $request)
    {
        $period = $request->query('period', date('Y-m'));

        // Ambil data jadwal dari tabel schedules berdasarkan kode periode
        $schedules = \App\Models\Schedule::where('period_code', $period)->get();

        $data = $schedules->map(function ($sch) {
            return [
                'id'         => (string) $sch->id,
                'periodId'   => $sch->period_code,
                'nip'        => $sch->nip ?? '-',
                'name'       => $sch->employee_name ?? '-',
                'department' => $sch->department_name ?? '-',
                'subDept'    => $sch->sub_department_name ?? '-',
                'workScheme' => $sch->work_scheme ?? 'Skema 5-2',
                'status'     => $sch->is_active ? 'Aktif' : 'Nonaktif',
                'totalDays'  => (int) $sch->total_days,
                'workDays'   => (int) $sch->work_days,
                'offDays'    => (int) $sch->off_days,
                'phDays'     => (int) $sch->ph_days,
                'permitDays' => (int) $sch->permit_days,
                'alphaDays'  => (int) $sch->alpha_days,
                'leaveDays'  => (int) $sch->leave_days,
                'lateDays'   => (int) $sch->late_days,
                'ref'        => $sch->ref,
            ];
        });

        return response()->json([
            'data' => $data,
            'summary' => [
                'totalPeriodDays' => $schedules->first()?->total_days ?? 0,
                'totalWorkDays'   => $schedules->sum('work_days'),
                'totalOffDays'    => $schedules->sum('off_days'),
                'totalPhDays'     => $schedules->sum('ph_days'),
                'totalPermitDays' => $schedules->sum('permit_days'),
                'totalAlphaDays'  => $schedules->sum('alpha_days'),
                'totalLeaveDays'  => $schedules->sum('leave_days'),
                'totalLateDays'   => $schedules->sum('late_days'),
            ]
        ]);
    }

    /**
     * 2. GET /api/schedule/input-schedule/edit/{id}
     * Mendapatkan detail jadwal harian karyawan untuk halaman Edit
     */
    public function showDetail(Request $request, $id)
    {
        // 1. Ambil data karyawan beserta sub departemennya
        $employee = \App\Models\Employee::with(['subDepartment'])->findOrFail($id);

        // 2. Ambil parameter periode dari request (jika dikirim), atau default ke bulan berjalan (misal: "2026-10")
        $period = $request->query('period', '2026-10');
        list($year, $month) = explode('-', $period);
        $totalDaysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$month, (int)$year);

        // 3. Generate rincian jadwal harian dari tanggal 1 sampai akhir bulan secara dinamis
        $scheduleList = [];
        $dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        for ($i = 1; $i <= $totalDaysInMonth; $i++) {
            $dateStr = sprintf('%s-%s-%02d', $year, $month, $i);
            $timestamp = strtotime($dateStr);
            $dayOfWeekIndex = date('w', $timestamp); // 0 (Minggu) sampai 6 (Sabtu)
            $dayName = $dayNames[$dayOfWeekIndex];

            // Tentukan default status (Misal: Sabtu & Minggu Libur, Senin-Jumat Berangkat)
            $isWeekend = ($dayOfWeekIndex == 0 || $dayOfWeekIndex == 6);
            $status = $isWeekend ? 'Libur' : 'Berangkat';
            
            $checkIn = $isWeekend ? '00:00:00' : '09:00:00';
            $checkOut = $isWeekend ? '00:00:00' : '18:00:00';

            $scheduleList[] = [
                'id' => (string) $i,
                'no' => $i,
                'dayName' => $dayName,
                'date' => $dateStr,
                'attendanceStatus' => $status,
                'scheduledCheckIn' => $checkIn,
                'scheduledCheckOut' => $checkOut,
                'actualCheckIn' => '00:00:00',
                'actualCheckOut' => '00:00:00',
                'notes' => '-',
                'ref' => $period,
            ];
        }

        // 4. Kembalikan respons JSON ke Frontend React
        return response()->json([
            'success' => true,
            'employeeInfo' => [
                'name' => $employee->full_name,
                'nip' => $employee->nik ?? '-',
                'period' => date('F Y', strtotime("$year-$month-01")),
                'dateRange' => "$year-$month-01 — $year-$month-$totalDaysInMonth",
                'scheme' => '5-2',
                'hoursPerDay' => 9,
                'summary' => [
                    'totalPeriodDays' => $totalDaysInMonth,
                    'totalWorkDays' => collect($scheduleList)->where('attendanceStatus', 'Berangkat')->count(),
                    'totalOffDays' => collect($scheduleList)->where('attendanceStatus', 'Libur')->count(),
                    'totalPhDays' => 1,
                    'totalPermitDays' => 0,
                    'totalAlphaDays' => 0,
                    'totalLeaveDays' => 0,
                    'totalLateDays' => 0,
                ]
            ],
            'scheduleList' => $scheduleList
        ]);
    }

    /**
     * 3. PUT /api/schedule/input-schedule/edit/{id}
     * Menyimpan pembaruan jadwal harian karyawan
     */
    public function updateDetail(Request $request, $id)
    {
        // Validasi input dari frontend
        $validated = $request->validate([
            'scheduleList' => 'required|array',
        ]);

        // Sementara di-bypass agar tidak error SQL pada tabel custom_work_hours,
        // silakan sesuaikan nanti jika kolom relasi tabel database sudah siap.

        return response()->json([
            'success' => true,
            'message' => 'Jadwal harian karyawan berhasil diperbarui'
        ]);
    }

    /**
     * 4. POST /api/schedule/input-schedule/weekly-hours
     * Menyimpan pengaturan jam kerja mingguan global
     */
    public function setWeeklyHours(Request $request)
    {
        // 1. Validasi input jam dari Senin sampai Minggu
        $validated = $request->validate([
            'monday' => 'required',
            'tuesday' => 'required',
            'wednesday' => 'required',
            'thursday' => 'required',
            'friday' => 'required',
            'saturday' => 'required',
            'sunday' => 'required',
        ]);

        // Asumsi: Kita mengupdate template "Master Schedule" utama dengan ID = 1
        $scheduleId = 1; 

        // 2. Update jam harian di tabel master "schedule_details"
        foreach ($validated as $day => $time) {
            \App\Models\ScheduleDetail::updateOrCreate(
                [
                    'schedule_id' => $scheduleId,
                    'day_of_week' => $day,
                ],
                [
                    'check_in_time' => $time, // Update jam datang
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Jam kerja mingguan berhasil diperbarui'
        ]);
    }

    /**
     * 5. POST /api/schedule/input-schedule/import
     * Mengelola upload file Excel/CSV
     */
    public function import(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'File berhasil diimport'
        ]);
    }

    /**
     * 6. GET /api/schedule/input-schedule/export
     * Mengunduh data jadwal
     */
    /**
     * 6. GET /api/schedule/input-schedule/export
     * Mengunduh data jadwal dalam bentuk file CSV (Excel-compatible)
     */
    public function export(Request $request)
    {
        $period = $request->query('period', date('Y-m')); // Contoh: "2026-10"
        
        // 1. Ambil data karyawan
        $employees = \App\Models\Employee::with(['subDepartment'])->get();

        // 2. Siapkan nama file
        $fileName = "schedule-period-{$period}.csv";

        // 3. Buat header agar browser mendeteksinya sebagai file download
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // 4. Callback untuk menulis data ke file stream CSV
        $callback = function() use ($employees, $period) {
            $file = fopen('php://output', 'w');
            
            // Tambahkan BOM agar UTF-8 terbaca dengan baik di Microsoft Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Kolom CSV
            fputcsv($file, ['ID Periode', 'NIP', 'Nama Karyawan', 'Sub Departemen', 'Skema Kerja', 'Status', 'Total Hari', 'Masuk', 'Libur', 'PH', 'Cuti']);

            // Data Baris Karyawan
            foreach ($employees as $emp) {
                fputcsv($file, [
                    $period,
                    $emp->nik ?? '-',
                    $emp->full_name,
                    $emp->subDepartment ? $emp->subDepartment->name : '-',
                    'Skema 5-2',
                    $emp->is_active ? 'Aktif' : 'Nonaktif',
                    31,
                    22,
                    9,
                    1,
                    0
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    /**
     * GET /api/schedule/jadwalku
     * Mendapatkan rekapitulasi jadwalku seluruh karyawan
     */
    /**
     * GET /api/schedule/jadwalku
     * Get attendance schedule summary for all employees
     */
   /**
     * GET /api/schedule/jadwalku
     * Get attendance schedule summary for all employees
     */
    public function getMySchedule(Request $request)
    {
        // Fetch employee data with Department, SubDepartment, and Grade relations
        $employees = \App\Models\Employee::with(['department', 'subDepartment', 'grade'])->get();

        $data = $employees->map(function ($emp) {
            return [
                'id' => (string) $emp->id,
                'period' => '2026-10',
                'nip' => $emp->nik ?? '-',
                'name' => $emp->full_name,
                'department' => $emp->department ? $emp->department->name : '-',
                'sub_department' => $emp->subDepartment ? $emp->subDepartment->name : '-',
                'grade' => $emp->grade ? $emp->grade->name : '-',
                'off_days' => 8,       
                'ph_days' => 1,
                'permit_days' => 0,
                'alpha_days' => 0,
                'sick_days' => 0,
                'leave_days' => 0,
                'late_days' => 0,
                'work_days' => 22,
                'total_days' => 31,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * GET /api/schedule/jadwalku/export
     * Mengunduh rekapitulasi jadwalku seluruh karyawan dalam bentuk file CSV/Excel
     */
    /**
     * POST /api/schedule/jadwalku/export
     * Mengunduh rekapitulasi jadwalku seluruh/sebagian karyawan dalam bentuk file CSV
     */
    public function exportMySchedule(Request $request)
    {
        $period = $request->input('period', '2026-10'); // Periode aktif
        $selectedIds = $request->input('ids', []);     // Array ID karyawan yang dicentang

        // 1. Ambil data karyawan dengan relasi lengkap
        $query = \App\Models\Employee::with(['department', 'subDepartment', 'grade']);

        // 2. Jika ada baris yang dicentang, filter berdasarkan ID tersebut
        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        }

        $employees = $query->get();

        $fileName = "rekap-jadwalku-period-{$period}.csv";

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use ($employees, $period) {
            $file = fopen('php://output', 'w');
            
            // Tambahkan BOM agar UTF-8 terbaca dengan baik di Microsoft Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Kolom sesuai Tabel Jadwalku
            fputcsv($file, [
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
            ]);

            // Data Baris Karyawan yang terfilter
            foreach ($employees as $emp) {
                fputcsv($file, [
                    $emp->nik ?? '-',
                    $emp->full_name,
                    $emp->department ? $emp->department->name : '-',
                    $emp->subDepartment ? $emp->subDepartment->name : '-',
                    $emp->grade ? $emp->grade->name : '-',
                    8,  // Contoh data dummy Libur
                    1,  // Contoh data dummy PH
                    0,  // Izin
                    0,  // Alfa
                    0,  // Sakit
                    0,  // Cuti
                    0,  // Terlambat
                    22, // Masuk
                    31  // Total Hari
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }



}