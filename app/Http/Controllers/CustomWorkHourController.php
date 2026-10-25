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
            'data' => $customWorkHours
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
        'name' => 'required|string|max:255',
        'clock_in' => 'required|date_format:H:i:s',
        'clock_out' => 'required|date_format:H:i:s',
        'tolerance' => 'nullable|date_format:H:i:s', // 👈 Validasi format jam:menit:detik
        'description' => 'nullable|string',
        'is_active' => 'boolean',
    ]);

        $customWorkHour = CustomWorkHour::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour created successfully',
            'data' => $customWorkHour
        ], 201);
    }

    public function show($id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $customWorkHour
        ]);
    }

    public function update(Request $request, $id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);

       $validated = $request->validate([
        'name' => 'required|string|max:255',
        'clock_in' => 'required|date_format:H:i:s',
        'clock_out' => 'required|date_format:H:i:s',
        'tolerance' => 'nullable|date_format:H:i:s', // 👈 Validasi format jam:menit:detik
        'description' => 'nullable|string',
        'is_active' => 'boolean',
    ]);

        $customWorkHour->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour updated successfully',
            'data' => $customWorkHour
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