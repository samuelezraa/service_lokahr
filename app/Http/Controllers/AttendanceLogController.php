<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Illuminate\Http\Request;

class AttendanceLogController extends Controller
{
    public function index()
    {
        $logs = AttendanceLog::with('attendanceLogDetails')->latest('date')->get();
        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'machine_ip' => 'nullable|ip',
            'payload_data' => 'nullable|array',
        ]);

        $log = AttendanceLog::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance log recorded successfully',
            'data' => $log
        ], 201);
    }

    public function show($id)
    {
        $log = AttendanceLog::with('attendanceLogDetails')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $log
        ]);
    }

    public function destroy($id)
    {
        $log = AttendanceLog::findOrFail($id);
        $log->delete();

        return response()->json([
            'success' => true,
            'message' => 'Attendance log deleted successfully'
        ]);
    }
}