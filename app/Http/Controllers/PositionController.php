<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = Position::query();

        // 🟢 1. Jika request dari dropdown master filter (misal: all=true / per_page=100)
        if ($request->boolean('all')) {
            $positions = $query->where('is_active', true)->get();
            return response()->json([
                'success' => true,
                'data'    => $positions,
            ]);
        }

        // 2. Filter Search (berdasarkan title atau code)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 3. Filter Departemen (via Code)
        if ($request->has('department_code') && !empty($request->department_code)) {
            $query->where('department_code', $request->department_code);
        }

        // 4. Filter Sub Departemen (via Code)
        if ($request->has('sub_department_code') && !empty($request->sub_department_code)) {
            $query->where('sub_department_code', $request->sub_department_code);
        }

        // 5. Filter Status Aktif / Nonaktif
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        $perPage = $request->input('per_page', 10);
        $positions = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $positions->items(),
            'meta'    => [
                'current_page' => $positions->currentPage(),
                'from'         => $positions->firstItem(),
                'last_page'    => $positions->lastPage(),
                'per_page'     => $positions->perPage(),
                'to'           => $positions->lastItem(),
                'total'        => $positions->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'                => 'required|string|unique:positions,code',
            'title'               => 'required|string|max:255',
            'department_code'     => 'nullable|string',
            'sub_department_code' => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $position = Position::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Position created successfully',
            'data'    => $position
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $position = Position::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'code'                => 'required|string|unique:positions,code,' . $position->id,
            'title'               => 'required|string|max:255',
            'department_code'     => 'nullable|string',
            'sub_department_code' => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $position->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Position updated successfully',
            'data'    => $position
        ]);
    }

    public function destroy($id)
    {
        $position = Position::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();
            
        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Position deleted successfully'
        ]);
    }
}