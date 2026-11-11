<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with('subDepartments');

        // Tangkap parameter pencarian (berdasarkan nama atau kode)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Tangkap parameter filter status
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        // Ambil parameter per_page dari request (default 10 atau 25)
        $perPage = $request->input('per_page', 10);

        // Gunakan paginate() alih-alih get()
        $departments = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $departments->items(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'from' => $departments->firstItem(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'to' => $departments->lastItem(),
                'total' => $departments->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:departments,code',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $department = Department::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully',
            'data' => $department
        ], 201);
    }

    public function show($id)
    {
        $department = Department::with('subDepartments')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $department
        ]);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:departments,code,' . $id,
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $department->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully',
            'data' => $department
        ]);
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully'
        ]);
    }
}