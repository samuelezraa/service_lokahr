<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('subDepartments')->latest()->get();
        return response()->json([
            'success' => true,
            'data' => $departments
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