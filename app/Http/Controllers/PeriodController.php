<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    public function index()
    {
        $periods = Period::latest('start_date')->get();
        return response()->json([
            'success' => true,
            'data' => $periods
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_closed' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $period = Period::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Period created successfully',
            'data' => $period
        ], 201);
    }

    public function show($id)
    {
        $period = Period::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $period
        ]);
    }

    public function update(Request $request, $id)
    {
        $period = Period::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_closed' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $period->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Period updated successfully',
            'data' => $period
        ]);
    }

    public function destroy($id)
    {
        $period = Period::findOrFail($id);
        $period->delete();

        return response()->json([
            'success' => true,
            'message' => 'Period deleted successfully'
        ]);
    }
}