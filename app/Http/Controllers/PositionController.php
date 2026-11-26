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

        // 1. Filter Status Aktif / Nonaktif (jika kosong / semua status, tampilkan semua)
        if ($request->filled('status') || ($request->has('status') && $request->input('status') !== '' && $request->input('status') !== 'all')) {
            $status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($status !== null) {
                $query->where('is_active', $status);
            }
        }

        // 2. Filter Search (berdasarkan title atau code)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 3. Filter Departemen (mendukung department_code atau department_id)
        $deptInput = $request->input('department_code', $request->input('department_id'));
        if (!empty($deptInput)) {
            $dept = Department::where('code', $deptInput)
                ->orWhere('name', $deptInput)
                ->when(is_numeric($deptInput), fn($q) => $q->orWhere('id', (int) $deptInput))
                ->first();

            $deptCode = $dept ? $dept->code : null;
            $deptName = $dept ? $dept->name : null;

            $query->where(function ($q) use ($deptInput, $deptCode, $deptName) {
                $q->where('department_code', $deptInput);
                if ($deptCode) {
                    $q->orWhere('department_code', $deptCode);
                }
                if ($deptName) {
                    $q->orWhere('department_code', $deptName);
                }
            });
        }

        // 4. Filter Sub Departemen (mendukung sub_department_code atau sub_department_id)
        $subInput = $request->input('sub_department_code', $request->input('sub_department_id'));
        if (!empty($subInput)) {
            $subDept = SubDepartment::where('code', $subInput)
                ->orWhere('name', $subInput)
                ->when(is_numeric($subInput), fn($q) => $q->orWhere('id', (int) $subInput))
                ->first();

            $subCode = $subDept ? $subDept->code : null;
            $subName = $subDept ? $subDept->name : null;

            $query->where(function ($q) use ($subInput, $subCode, $subName) {
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
        $request->merge([
            'code' => is_string($request->input('code'))
                ? strtoupper(trim($request->input('code')))
                : $request->input('code'),
            'title' => is_string($request->input('title'))
                ? trim($request->input('title'))
                : $request->input('title'),
        ]);

        $validated = $request->validate([
            'code'                => ['required', 'string', 'regex:/^POS\d{1,4}$/', 'unique:positions,code'],
            'title'               => ['required', 'string', 'max:255', 'unique:positions,title'],
            'department_code'     => 'nullable|string',
            'sub_department_code' => 'nullable|string',
            'is_active'           => 'boolean',
        ], [
            'code.required'  => 'ID POS wajib diisi.',
            'code.regex'     => 'Format ID POS harus diawali POS dan diikuti 1 sampai 4 digit angka (contoh: POS01).',
            'code.unique'    => 'ID POS sudah digunakan. Gunakan ID lain yang belum terdaftar.',
            'title.required' => 'Nama POS wajib diisi.',
            'title.unique'   => 'Nama POS sudah digunakan. Gunakan nama lain yang belum terdaftar.',
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

        $request->merge([
            'title' => is_string($request->input('title'))
                ? trim($request->input('title'))
                : $request->input('title'),
        ]);

        $validated = $request->validate([
            'code'                => 'required|string|unique:positions,code,' . $position->id,
            'title'               => ['required', 'string', 'max:255', 'unique:positions,title,' . $position->id],
            'department_code'     => 'nullable|string',
            'sub_department_code' => 'nullable|string',
            'is_active'           => 'boolean',
        ], [
            'title.required' => 'Nama POS wajib diisi.',
            'title.unique'   => 'Nama POS sudah digunakan. Gunakan nama lain yang belum terdaftar.',
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
