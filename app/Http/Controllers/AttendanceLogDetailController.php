<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLogDetail;
use Illuminate\Http\Request;

class AttendanceLogDetailController extends Controller
{
    public function index()
    {
        $details = AttendanceLogDetail::with([
            'attendanceLog',
            'department',
            'employee.subDepartment',
            'employee.position',
            'employee.grade'
        ])
        ->latest('attendance_time')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $details
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_log_id' => 'nullable|exists:attendance_logs,id',
            'date'              => 'required|date',
            'nik'               => 'required|string',
            'department_id'     => 'nullable|exists:departments,id',
            'department_name'   => 'required|string',
            'employee_name'     => 'required|string',
            'attendance_time'   => 'required|date',
            'attendance_type'   => 'required|string',
        ]);

        $detail = AttendanceLogDetail::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance log detail created successfully',
            'data'    => $detail
        ], 201);
    }

    public function show($id)
    {
        $detail = AttendanceLogDetail::with([
            'attendanceLog',
            'department',
            'employee.subDepartment',
            'employee.position',
            'employee.grade'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $detail
        ]);
    }

    public function destroy($id)
    {
        $detail = AttendanceLogDetail::findOrFail($id);
        $detail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance log detail deleted successfully'
        ]);
    }
}