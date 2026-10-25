<?php

namespace App\Http\Controllers;

use App\Models\SubDepartment;
use Illuminate\Http\Request;

class SubDepartmentController extends Controller
{
    public function index()
    {
        $subDepartments = SubDepartment::with('department')->latest()->get();
        return response()->json([
            'success' => true,
            'data'    => $subDepartments
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_code' => 'required|exists:departments,code',
            'code'            => 'required|string|unique:sub_departments,code',
            'name'            => 'required|string|max:255',
            'is_active'       => 'boolean',
        ]);

        $subDepartment = SubDepartment::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sub Department created successfully',
            'data'    => $subDepartment->load('department')
        ], 201);
    }

    public function show($id)
    {
        $subDepartment = SubDepartment::with('department')
            ->where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $subDepartment
        ]);
    }

    public function update(Request $request, $id)
    {
        $subDepartment = SubDepartment::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'department_code' => 'required|exists:departments,code',
            'code'            => 'required|string|unique:sub_departments,code,' . $subDepartment->id,
            'name'            => 'required|string|max:255',
            'is_active'       => 'boolean',
        ]);

        $subDepartment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sub Department updated successfully',
            'data'    => $subDepartment->load('department')
        ]);
    }

    public function destroy($id)
    {
        $subDepartment = SubDepartment::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        $subDepartment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sub Department deleted successfully'
        ]);
    }
}