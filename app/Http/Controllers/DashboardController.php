<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $period = $request->query('period');

        // 🌟 1. Gunakan tabel attendance_log_details yang sesuai dengan AttendanceLogDetailSeeder
        $query = DB::table('attendance_log_details');

        if ($startDate && $endDate) {
            // Filter berdasarkan tanggal pada tabel log detail
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($period) {
            // Jika menggunakan period_code, pastikan kolomnya ada atau sesuaikan
            $query->where('period_code', $period);
        }

        // Karena seeder mencatat karyawan yang masuk berdasarkan log 'in',
        // kita hitung jumlah total karyawan yang absen (hadir) pada rentang tanggal tersebut.
        $present = (clone $query)->where('attendance_type', 'in')->count();
        $late = 0;    // Jika belum ada data keterlambatan di seeder ini
        $sick = 0;    // Jika belum ada data sakit di seeder ini
        $alpha = 0;   // Jika belum ada data alfa di seeder ini

        // 2. Query Data Karyawan Master (Aktif & Non Aktif)
        $activeEmployees = DB::table('employees')->where('is_active', true)->count();
        $inactiveEmployees = DB::table('employees')->where('is_active', false)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'present'            => (int) $present,
                'late'               => (int) $late,
                'sick'               => (int) $sick,
                'alpha'              => (int) $alpha,
                'active_employees'   => $activeEmployees,
                'inactive_employees' => $inactiveEmployees,
            ]
        ]);
    }
}