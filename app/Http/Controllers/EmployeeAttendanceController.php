<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;
use Illuminate\Http\Request;

class EmployeeAttendanceController extends Controller
{
    /**
     * GET /api/employee-attendances?period=2026-10
     */
    public function index(Request $request)
    {
        $period = $request->query('period');

        $query = EmployeeAttendance::query();
        if ($period) {
            $query->where('period_code', $period);
        }

        $attendances = $query->get();

        $data = $attendances->map(function ($item) {
            return [
                'id'            => (string) $item->id,
                'nip'           => $item->nip,
                'nama'          => $item->employee_name,
                'departemen'    => $item->department_name ?? '-',
                'subDepartemen' => $item->sub_department_name ?? '-',
                'subDept'       => $item->sub_department_name ?? '-',
                'pos'           => $item->pos_name ?? '-',
                'grade'         => $item->grade_name ?? '-',
                'skemaKerja'    => $item->work_scheme,
                'totalLibur'    => (int) $item->total_off_days,
                'totalPH'       => (int) $item->total_ph_days,
                'totalIzin'     => (int) $item->total_permit_days,
                'totalAlfa'     => (int) $item->total_alpha_days,
                'totalSakit'    => (int) $item->total_sick_days,
                'totalCuti'     => (int) $item->total_leave_days,
                'totalTerlambat'=> (int) $item->total_late_days,
                'totalMasuk'    => (int) $item->total_work_days,
                'totalHari'     => (int) $item->total_period_days,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * GET /api/employee-attendances/{id}
     */
    public function show($id)
    {
        $attendance = EmployeeAttendance::with('details')->findOrFail($id);

        return response()->json([
            'success' => true,
            'karyawanInfo' => [
                'nama'            => $attendance->employee_name,
                'nip'             => $attendance->nip,
                'periode'         => $attendance->period_code,
                'rentangTanggal'  => "{$attendance->period_code}-01 — {$attendance->period_code}-{$attendance->total_period_days}",
                'skema'           => $attendance->work_scheme,
                'totalHariKerja'  => $attendance->total_work_days,
                'jamKerjaPerHari' => 9,
            ],
            'jadwalList' => $attendance->details->map(function ($d) {
                return [
                    'id'          => (string) $d->id,
                    'no'          => $d->day_no,
                    'hari'        => $d->day_name,
                    'tanggal'     => $d->date ? $d->date->format('Y-m-d') : '',
                    'kehadiran'   => $d->attendance_status,
                    'jamDatang'   => $d->scheduled_check_in,
                    'jamPulang'   => $d->scheduled_check_out,
                    'absenDatang' => $d->actual_check_in,
                    'absenPulang' => $d->actual_check_out,
                    'status'      => $d->status ?? '-',
                    'keterangan'  => $d->notes ?? '-',
                    'ref'         => $d->ref ?? '',
                ];
            }),
        ]);
    }

    /**
     * PUT /api/employee-attendances/{id}
     */
    public function update(Request $request, $id)
    {
        $attendance = EmployeeAttendance::findOrFail($id);
        $validated = $request->validate([
            'jadwalList' => 'required|array',
        ]);

        foreach ($validated['jadwalList'] as $row) {
            EmployeeAttendanceDetail::where('id', $row['id'])
                ->where('employee_attendance_id', $id)
                ->update([
                    'attendance_status'   => $row['kehadiran'] ?? 'Berangkat',
                    'scheduled_check_in'  => $row['jamDatang'] ?? '09:00:00',
                    'scheduled_check_out' => $row['jamPulang'] ?? '18:00:00',
                    'actual_check_in'     => $row['absenDatang'] ?? '00:00:00',
                    'actual_check_out'    => $row['absenPulang'] ?? '00:00:00',
                    'status'              => $row['status'] ?? '-',
                    'notes'               => $row['keterangan'] ?? '-',
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data Absensi Karyawan berhasil disimpan!',
        ]);
    }
}