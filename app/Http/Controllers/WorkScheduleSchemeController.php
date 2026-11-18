<?php

namespace App\Http\Controllers;

use App\Models\WorkScheduleScheme;
use Illuminate\Http\Request;

class WorkScheduleSchemeController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkScheduleScheme::query();

        // 1. Filter Pencarian (berdasarkan name atau code)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 2. Filter Status Aktif / Non Aktif
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        // 3. Server-side Pagination
        $perPage = $request->input('per_page', 25);
        $schemes = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $schemes->items(),
            'meta'    => [
                'current_page' => $schemes->currentPage(),
                'from'         => $schemes->firstItem(),
                'last_page'    => $schemes->lastPage(),
                'per_page'     => $schemes->perPage(),
                'to'           => $schemes->lastItem(),
                'total'        => $schemes->total(),
            ]
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