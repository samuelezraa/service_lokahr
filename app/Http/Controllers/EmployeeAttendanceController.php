<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceDetail;
use Illuminate\Http\Request;

class EmployeeAttendanceController extends Controller
{
   public function index(Request $request)
    {
        $period  = $request->query('period');
        $search  = $request->query('search');
        $dept    = $request->query('dept');
        $subDept = $request->query('sub_dept');
        $perPage = (int) $request->input('per_page', 25);

        $query = EmployeeAttendance::query();

        if ($period) {
            $periodCodes = collect([$period]);

            $periodModel = \App\Models\Period::where('code', $period)
                ->orWhere('id', $period)
                ->first();

            if (!$periodModel && preg_match('/^\d{4}-\d{2}$/', $period)) {
                $periodModel = \App\Models\Period::where('start_date', 'like', "{$period}%")
                    ->orWhere('end_date', 'like', "{$period}%")
                    ->first();
            }

            if ($periodModel?->code) {
                $periodCodes->push($periodModel->code);
            }

            $codes = $periodCodes->filter()->unique()->values();
            $query->where(function ($q) use ($codes, $period) {
                $q->whereIn('period_code', $codes)
                  ->orWhere('period_code', 'like', "%{$period}%");
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('department_name', 'like', "%{$search}%");
            });
        }

        if ($dept) {
            $query->where('department_name', $dept);
        }

        if ($subDept) {
            $query->where('sub_department_name', $subDept);
        }

        $paginated = $query->paginate($perPage);

        // ... sisa kodenya tetap sama ...

       $mappedItems = collect($paginated->items())->map(function ($item) {
            return [
                'id'              => (string) $item->id,
                'employeeId'      => $item->employee_id ?? '-',
                'nip'             => $item->nip,
                'name'            => $item->employee_name,           // 👈 Ubah dari 'nama' menjadi 'name'
                'department'      => $item->department_name ?? '-',  // 👈 Ubah dari 'departemen' menjadi 'department'
                'subDepartment'   => $item->sub_department_name ?? '-', // 👈 Ubah dari 'subDepartemen'
                'subDept'         => $item->sub_department_name ?? '-',
                'pos'             => $item->pos_name ?? '-',
                'grade'           => $item->grade_name ?? '-',
                'workScheme'      => $item->work_scheme,             // 👈 Ubah dari 'skemaKerja' menjadi 'workScheme'
                'totalOffDays'    => (int) $item->total_off_days,
                'totalPhDays'     => (int) $item->total_ph_days,
                'totalPermitDays' => (int) $item->total_permit_days,
                'totalAlphaDays'  => (int) $item->total_alpha_days,
                'totalSickDays'   => (int) $item->total_sick_days,
                'totalLeaveDays'  => (int) $item->total_leave_days,
                'totalLateDays'   => (int) $item->total_late_days,
                'totalWorkDays'   => (int) $item->total_work_days,   // 👈 Ubah dari 'totalMasuk' menjadi 'totalWorkDays'
                'totalPeriodDays' => (int) $item->total_period_days, // 👈 Ubah dari 'totalHari' menjadi 'totalPeriodDays'
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $mappedItems,
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'from'         => $paginated->firstItem(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'to'           => $paginated->lastItem(),
                'total'        => $paginated->total(),
            ]
        ]);
    }

    public function show($id)
    {
        $attendance = EmployeeAttendance::with('details')->findOrFail($id);

        return response()->json([
            'success' => true,
            'karyawanInfo' => [
                'name'            => $attendance->employee_name,    // 🟢 Diubah ke English
                'nip'             => $attendance->nip,
                'period'          => $attendance->period_code,      // 🟢 Diubah ke English
                'dateRange'       => "{$attendance->period_code}-01 — {$attendance->period_code}-{$attendance->total_period_days}", // 🟢 Diubah ke English
                'scheme'          => $attendance->work_scheme,      // 🟢 Diubah ke English
                'totalWorkDays'   => $attendance->total_work_days,  // 🟢 Diubah ke English
                'hoursPerDay'     => 9,                             // 🟢 Diubah ke English
            ],
            'jadwalList' => $attendance->details->map(function ($d) {
                return [
                    'id'               => (string) $d->id,
                    'no'               => $d->day_no,
                    'day'              => $d->day_name,             // 🟢 Diubah ke English
                    'date'             => $d->date ? $d->date->format('Y-m-d') : '',
                    'attendanceStatus' => $d->attendance_status,    // 🟢 Diubah ke English
                    'scheduledCheckIn' => $d->scheduled_check_in,   // 🟢 Diubah ke English
                    'scheduledCheckOut'=> $d->scheduled_check_out,  // 🟢 Diubah ke English
                    'actualCheckIn'    => $d->actual_check_in,      // 🟢 Diubah ke English
                    'actualCheckOut'   => $d->actual_check_out,     // 🟢 Diubah ke English
                    'status'           => $d->status ?? '-',
                    'notes'            => $d->notes ?? '-',         // 🟢 Diubah ke English
                    'ref'              => $d->ref ?? '',
                ];
            }),
        ]);
    }

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
                    'attendance_status'   => $row['attendanceStatus'] ?? 'Berangkat',
                    'scheduled_check_in'  => $row['scheduledCheckIn'] ?? '09:00:00',
                    'scheduled_check_out' => $row['scheduledCheckOut'] ?? '18:00:00',
                    'actual_check_in'     => $row['actualCheckIn'] ?? '00:00:00',
                    'actual_check_out'    => $row['actualCheckOut'] ?? '00:00:00',
                    'status'              => $row['status'] ?? '-',
                    'notes'               => $row['notes'] ?? '-',
                ]);
        }

        $details = EmployeeAttendanceDetail::where('employee_attendance_id', $attendance->id)->get();

        $attendance->update([
            'total_work_days'   => $details->whereIn('attendance_status', ['Berangkat', 'Masuk', 'Hadir'])->count(),
            'total_off_days'    => $details->where('attendance_status', 'Libur')->count(),
            'total_ph_days'     => $details->whereIn('attendance_status', ['PH', 'Public Holiday'])->count(),
            'total_permit_days' => $details->where('attendance_status', 'Izin')->count(),
            'total_alpha_days'  => $details->whereIn('attendance_status', ['Alfa', 'Alpha'])->count(),
            'total_sick_days'   => $details->where('attendance_status', 'Sakit')->count(),
            'total_leave_days'  => $details->where('attendance_status', 'Cuti')->count(),
            'total_late_days'   => $details->where('status', 'Terlambat')->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data Absensi Karyawan berhasil disimpan!',
        ]);
    }
}