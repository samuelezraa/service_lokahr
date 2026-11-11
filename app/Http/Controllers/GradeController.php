<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $query = Grade::query();

        // 1. Filter Search (berdasarkan name atau code)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 2. Filter Status Aktif/Nonaktif
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        $perPage = $request->input('per_page', 10);
        
        // Urutkan berdasarkan level tertinggi ke terendah, lalu paginate
        $grades = $query->orderBy('level', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $grades->items(),
            'meta'    => [
                'current_page' => $grades->currentPage(),
                'from'         => $grades->firstItem(),
                'last_page'    => $grades->lastPage(),
                'per_page'     => $grades->perPage(),
                'to'           => $grades->lastItem(),
                'total'        => $grades->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'      => 'required|string|unique:grades,code',
            'name'      => 'required|string|max:255',
            'level'     => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $grade = Grade::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Grade created successfully',
            'data'    => $grade
        ], 201);
    }

    public function show($id)
    {
        $grade = Grade::where('id', $id)->orWhere('code', $id)->firstOrFail();
        return response()->json([
            'success' => true,
            'data'    => $grade
        ]);
    }

    public function update(Request $request, $id)
    {
        $grade = Grade::where('id', $id)->orWhere('code', $id)->firstOrFail();

        $validated = $request->validate([
            'code'      => 'required|string|unique:grades,code,' . $grade->id,
            'name'      => 'required|string|max:255',
            'level'     => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $grade->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Grade updated successfully',
            'data'    => $grade
        ]);
    }

    public function destroy($id)
    {
        $grade = Grade::where('id', $id)->orWhere('code', $id)->firstOrFail();
        $grade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grade deleted successfully'
        ]);
    }
}