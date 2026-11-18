<?php

namespace App\Http\Controllers;

use App\Models\Position;
use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index(Request $request)
    {
        $query = Position::query();

        // 1. Filter Status Aktif / Nonaktif
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        } else {
            $query->where('is_active', true);
        }

        // 2. Filter Search (berdasarkan title atau code)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 3. Filter Departemen secara Fleksibel (ID, Code, atau Nama)
        if ($request->has('department_id') && !empty($request->department_id)) {
            $deptInput = $request->department_id;
            $dept = Department::find($deptInput); // Cari objek berdasarkan ID jika berupa angka

            $deptCode = $dept ? $dept->code : null;
            $deptName = $dept ? $dept->name : null;

            $query->where(function($q) use ($deptInput, $deptCode, $deptName) {
                $q->where('department_code', $deptInput);
                if ($deptCode) {
                    $q->orWhere('department_code', $deptCode);
                }
                if ($deptName) {
                    $q->orWhere('department_code', $deptName);
                }
            });
        }

        // 4. Filter Sub Departemen secara Fleksibel (ID, Code, atau Nama)
        if ($request->has('sub_department_id') && !empty($request->sub_department_id)) {
            $subInput = $request->sub_department_id;
            $subDept = SubDepartment::find($subInput); // Cari objek berdasarkan ID jika berupa angka

            $subCode = $subDept ? $subDept->code : null;
            $subName = $subDept ? $subDept->name : null;

            $query->where(function($q) use ($subInput, $subCode, $subName) {
                $q->where('sub_department_code', $subInput);
                if ($subCode) {
                    $q->orWhere('sub_department_code', $subCode);
                }
                if ($subName) {
                    $q->orWhere('sub_department_code', $subName);
                }
            });
        }

        if ($request->boolean('all')) {
            $positions = $query->get();
            return response()->json([
                'success' => true,
                'data'    => $positions,
            ]);
        }

        $perPage = $request->input('per_page', 25);
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