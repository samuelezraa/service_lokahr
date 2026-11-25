<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate   = $request->query('end_date');
        $period    = $request->query('period');

        // 1. Resolusi rentang tanggal (jika menggunakan filter period, ambil start_date & end_date dari tabel periods)
        if ($period && (!$startDate || !$endDate)) {
            $periodObj = Period::where('code', $period)->orWhere('id', $period)->first();
            if ($periodObj) {
                $startDate = $startDate ?? Carbon::parse($periodObj->start_date)->format('Y-m-d');
                $endDate   = $endDate   ?? Carbon::parse($periodObj->end_date)->format('Y-m-d');
            }
        }

        // 2. Query ringkasan kehadiran dari attendance_log_details
        $query = DB::table('attendance_log_details');

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        // Menghitung total log masuk ('in') pada rentang tanggal terpilih
        $present = (clone $query)->where('attendance_type', 'in')->count();
        $late    = 0;
        $sick    = 0;
        $alpha   = 0;

        // 3. Gabungkan query Karyawan Aktif & Non Aktif menjadi 1 kali query saja
        $employeeStats = DB::table('employees')
            ->selectRaw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_count')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_count')
            ->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'present'            => (int) $present,
                'late'               => (int) $late,
                'sick'               => (int) $sick,
                'alpha'              => (int) $alpha,
                'active_employees'   => (int) ($employeeStats->active_count ?? 0),
                'inactive_employees' => (int) ($employeeStats->inactive_count ?? 0),
            ]
        ]);
    }
}