<?php

namespace App\Http\Controllers;

use App\Models\WorkScheduleScheme;
use Illuminate\Http\Request;

class ScheduleSchemeController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkScheduleScheme::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== '' && $request->input('status') !== 'all') {
            $status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($status !== null) {
                $query->where('is_active', $status);
            }
        }

        $perPage = (int) $request->input('per_page', 25);
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
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'code' => is_string($request->input('code'))
                ? strtoupper(trim($request->input('code')))
                : $request->input('code'),
            'name' => is_string($request->input('name'))
                ? trim($request->input('name'))
                : $request->input('name'),
        ]);

        $validated = $request->validate([
            'code'        => ['required', 'string', 'regex:/^SK\d{3}$/', 'unique:work_schedule_schemes,code'],
            'name'        => ['required', 'string', 'max:255', 'unique:work_schedule_schemes,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['boolean'],
        ], [
            'code.required' => 'ID Skema wajib diisi.',
            'code.regex'    => 'Format ID Skema harus SK diikuti 3 digit angka (contoh: SK001).',
            'code.unique'   => 'ID Skema sudah digunakan. Gunakan ID lain yang belum terdaftar.',
            'name.required' => 'Nama Skema wajib diisi.',
            'name.unique'   => 'Nama Skema sudah digunakan. Gunakan nama lain yang belum terdaftar.',
        ]);

        $scheme = WorkScheduleScheme::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule scheme created successfully',
            'data'    => $scheme,
        ], 201);
    }

    public function show($id)
    {
        $scheme = WorkScheduleScheme::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $scheme,
        ]);
    }

    public function update(Request $request, $id)
    {
        $scheme = WorkScheduleScheme::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        $request->merge([
            'name' => is_string($request->input('name'))
                ? trim($request->input('name'))
                : $request->input('name'),
        ]);

        $validated = $request->validate([
            'code'        => ['sometimes', 'required', 'string', 'regex:/^SK\d{3}$/', 'unique:work_schedule_schemes,code,' . $scheme->id],
            'name'        => ['required', 'string', 'max:255', 'unique:work_schedule_schemes,name,' . $scheme->id],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['boolean'],
        ], [
            'code.regex'    => 'Format ID Skema harus SK diikuti 3 digit angka (contoh: SK001).',
            'name.required' => 'Nama Skema wajib diisi.',
            'name.unique'   => 'Nama Skema sudah digunakan. Gunakan nama lain yang belum terdaftar.',
        ]);

        $scheme->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule scheme updated successfully',
            'data'    => $scheme,
        ]);
    }

    public function destroy($id)
    {
        $scheme = WorkScheduleScheme::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        $scheme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule scheme deleted successfully',
        ]);
    }
}