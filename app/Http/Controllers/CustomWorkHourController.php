<?php

namespace App\Http\Controllers;

use App\Models\CustomWorkHour;
use Illuminate\Http\Request;

class CustomWorkHourController extends Controller
{
    public function index()
    {
        $customWorkHours = CustomWorkHour::latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $customWorkHours
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'work_scheme' => 'required|string|max:50',
            'clock_in'    => 'required|date_format:H:i:s',
            'tolerance'   => 'nullable|date_format:H:i:s',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // Generate ID / Code otomatis (misal: CUS001, CUS002)
        $latestRecord = CustomWorkHour::latest('id')->first();
        $nextNumber = $latestRecord ? $latestRecord->id + 1 : 1;
        $validated['code'] = 'CUS' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $customWorkHour = CustomWorkHour::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour created successfully',
            'data'    => $customWorkHour
        ], 201);
    }

    public function show($id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data'    => $customWorkHour
        ]);
    }

    public function update(Request $request, $id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);

        $validated = $request->validate([
            'start_date'  => 'sometimes|required|date',
            'end_date'    => 'sometimes|required|date|after_or_equal:start_date',
            'work_scheme' => 'sometimes|required|string|max:50',
            'clock_in'    => 'sometimes|required|date_format:H:i:s',
            'tolerance'   => 'nullable|date_format:H:i:s',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $customWorkHour->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour updated successfully',
            'data'    => $customWorkHour
        ]);
    }

    public function destroy($id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);
        $customWorkHour->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour deleted successfully'
        ]);
    }
}