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

        // 1. Query Rekap Presensi Kehadiran
        $query = DB::table('employee_attendances');

        if ($period) {
            $query->where('period_code', $period);
        }

        $attendanceStats = $query->selectRaw('
            SUM(total_work_days) as present,
            SUM(total_late_days) as late,
            SUM(total_sick_days) as sick,
            SUM(total_alpha_days) as alpha
        ')->first();

        // 2. Query Data Karyawan Master (Aktif & Non Aktif)
        $activeEmployees = DB::table('employees')->where('is_active', true)->count();
        $inactiveEmployees = DB::table('employees')->where('is_active', false)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'present' => (int) ($attendanceStats->present ?? 0),
                'late' => (int) ($attendanceStats->late ?? 0),
                'sick' => (int) ($attendanceStats->sick ?? 0),
                'alpha' => (int) ($attendanceStats->alpha ?? 0),
                'active_employees' => $activeEmployees,
                'inactive_employees' => $inactiveEmployees,
            ]
        ]);
    }
}