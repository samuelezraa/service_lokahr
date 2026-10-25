<?php

namespace App\Http\Controllers;

use App\Models\ScheduleScheme;
use Illuminate\Http\Request;

class ScheduleSchemeController extends Controller
{
    public function index()
    {
        $schemes = ScheduleScheme::with(['schedule', 'workScheduleScheme'])->get();
        return response()->json([
            'success' => true,
            'data' => $schemes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'work_schedule_scheme_id' => 'required|exists:work_schedule_schemes,id',
            'is_active' => 'boolean',
        ]);

        $scheme = ScheduleScheme::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule scheme mapped successfully',
            'data' => $scheme->load(['schedule', 'workScheduleScheme'])
        ], 201);
    }

    public function show($id)
    {
        $scheme = ScheduleScheme::with(['schedule', 'workScheduleScheme'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $scheme
        ]);
    }

    public function update(Request $request, $id)
    {
        $scheme = ScheduleScheme::findOrFail($id);

        $validated = $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
            'work_schedule_scheme_id' => 'required|exists:work_schedule_schemes,id',
            'is_active' => 'boolean',
        ]);

        $scheme->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule scheme mapping updated successfully',
            'data' => $scheme->load(['schedule', 'workScheduleScheme'])
        ]);
    }

    public function destroy($id)
    {
        $scheme = ScheduleScheme::findOrFail($id);
        $scheme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule scheme mapping deleted successfully'
        ]);
    }
}