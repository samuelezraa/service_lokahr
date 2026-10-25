<?php

namespace App\Http\Controllers;

use App\Models\ScheduleDetail;
use Illuminate\Http\Request;

class ScheduleDetailController extends Controller
{
    public function index()
    {
        $details = ScheduleDetail::with('schedule')->get();
        return response()->json([
            'success' => true,
            'data' => $details
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'check_in_time' => 'required|date_format:H:i:s',
            'check_out_time' => 'required|date_format:H:i:s',
            'is_day_off' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $detail = ScheduleDetail::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule detail created successfully',
            'data' => $detail->load('schedule')
        ], 201);
    }

    public function show($id)
    {
        $detail = ScheduleDetail::with('schedule')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $detail
        ]);
    }

    public function update(Request $request, $id)
    {
        $detail = ScheduleDetail::findOrFail($id);

        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'check_in_time' => 'required|date_format:H:i:s',
            'check_out_time' => 'required|date_format:H:i:s',
            'is_day_off' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $detail->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule detail updated successfully',
            'data' => $detail->load('schedule')
        ]);
    }

    public function destroy($id)
    {
        $detail = ScheduleDetail::findOrFail($id);
        $detail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule detail deleted successfully'
        ]);
    }
}