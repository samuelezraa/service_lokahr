<?php

namespace App\Http\Controllers;

use App\Models\SubDepartment;
use Illuminate\Http\Request;

class SubDepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = SubDepartment::with('department');

        // Filter Pencarian (nama atau kode sub departemen)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter Departemen Induk
        if ($request->has('department_code') && !empty($request->department_code)) {
            $query->where('department_code', $request->department_code);
        }

        // Filter Status Aktif/Non Aktif
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        $perPage = $request->input('per_page', 25);
        $subDepartments = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $subDepartments->items(),
            'meta'    => [
                'current_page' => $subDepartments->currentPage(),
                'from'         => $subDepartments->firstItem(),
                'last_page'    => $subDepartments->lastPage(),
                'per_page'     => $subDepartments->perPage(),
                'to'           => $subDepartments->lastItem(),
                'total'        => $subDepartments->total(),
            ]
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