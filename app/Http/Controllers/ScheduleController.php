<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the schedules with details and mapped work schedule schemes.
     */
    public function index()
    {
        $schedules = Schedule::with([
            'scheduleDetails',
            'scheduleSchemes.workScheduleScheme',
        ])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Store a newly created schedule in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $schedule = Schedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule
        ], 201);
    }

    /**
     * Display the specified schedule with details and mapped work schedule schemes.
     */
    public function show($id)
    {
        $schedule = Schedule::with([
            'scheduleDetails',
            'scheduleSchemes.workScheduleScheme',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    /**
     * Update the specified schedule in storage.
     */
    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule->load([
                'scheduleDetails',
                'scheduleSchemes.workScheduleScheme',
            ])
        ]);
    }

    /**
     * Remove the specified schedule from storage.
     */
    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    }
}