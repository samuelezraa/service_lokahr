<?php

namespace App\Http\Controllers;

use App\Models\WorkScheduleScheme;
use Illuminate\Http\Request;

class WorkScheduleSchemeController extends Controller
{
    public function index()
    {
        $schemes = WorkScheduleScheme::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $schemes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:work_schedule_schemes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $scheme = WorkScheduleScheme::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Work schedule scheme created successfully',
            'data' => $scheme
        ], 201);
    }

    public function show($id)
    {
        // Mendukung pencarian berdasarkan ID angka ataupun Code string
        $scheme = is_numeric($id) 
            ? WorkScheduleScheme::findOrFail($id) 
            : WorkScheduleScheme::where('code', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $scheme
        ]);
    }

    public function update(Request $request, $id)
    {
        // Mencari data berdasarkan ID angka atau Code string
        $scheme = is_numeric($id) 
            ? WorkScheduleScheme::findOrFail($id) 
            : WorkScheduleScheme::where('code', $id)->firstOrFail();

        $validated = $request->validate([
            'code' => 'required|string|unique:work_schedule_schemes,code,' . $scheme->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $scheme->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Work schedule scheme updated successfully',
            'data' => $scheme
        ]);
    }

    public function destroy($id)
    {
        $scheme = is_numeric($id) 
            ? WorkScheduleScheme::findOrFail($id) 
            : WorkScheduleScheme::where('code', $id)->firstOrFail();

        $scheme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work schedule scheme deleted successfully'
        ]);
    }
}