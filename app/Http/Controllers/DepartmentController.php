<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $withSub = $request->boolean('with_sub', true);
        $query = $withSub ? Department::with('subDepartments') : Department::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($status)) {
                $query->where('is_active', $status);
            }
        }

        $perPage = (int) $request->input('per_page', 25);
        $perPage = $perPage > 0 ? min($perPage, 100) : 25;

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
        $request->merge([
            'code' => is_string($request->input('code'))
                ? strtoupper(trim($request->input('code')))
                : $request->input('code'),
            'name' => is_string($request->input('name'))
                ? preg_replace('/\s+/', ' ', trim($request->input('name')))
                : $request->input('name'),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'regex:/^DP\d{3}$/',
                Rule::unique('departments', 'code'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name'),
            ],
            'is_active' => 'boolean',
        ], [
            'code.required' => 'ID Departemen wajib diisi.',
            'code.regex'    => 'Format ID Departemen harus diawali DP dan diikuti 3 digit angka (contoh: DP001).',
            'code.unique'   => 'ID Departemen sudah digunakan, silakan gunakan ID lain.',
            'name.required' => 'Nama Departemen wajib diisi.',
            'name.unique'   => 'Nama Departemen sudah terdaftar, tidak boleh sama.',
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

        $request->merge([
            'code' => is_string($request->input('code'))
                ? strtoupper(trim($request->input('code')))
                : $request->input('code'),
            'name' => is_string($request->input('name'))
                ? preg_replace('/\s+/', ' ', trim($request->input('name')))
                : $request->input('name'),
        ]);

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'regex:/^DP\d{3}$/',
                Rule::unique('departments', 'code')->ignore($department->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
            'is_active' => 'boolean',
        ], [
            'code.required' => 'ID Departemen wajib diisi.',
            'code.regex'    => 'Format ID Departemen harus diawali DP dan diikuti 3 digit angka (contoh: DP001).',
            'code.unique'   => 'ID Departemen sudah digunakan, silakan gunakan ID lain.',
            'name.required' => 'Nama Departemen wajib diisi.',
            'name.unique'   => 'Nama Departemen sudah terdaftar, tidak boleh sama.',
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