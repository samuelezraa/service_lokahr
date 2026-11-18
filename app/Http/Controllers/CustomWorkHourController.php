<?php

namespace App\Http\Controllers;

use App\Models\CustomWorkHour;
use Illuminate\Http\Request;

class CustomWorkHourController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomWorkHour::query();

        // 1. Search Global (Code, Work Scheme, Description)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('work_scheme', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Filter Status (Aktif / Non Aktif)
        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        $perPage = $request->input('per_page', 25);
        $customWorkHours = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $customWorkHours->items(),
            'meta'    => [
                'current_page' => $customWorkHours->currentPage(),
                'from'         => $customWorkHours->firstItem(),
                'last_page'    => $customWorkHours->lastPage(),
                'per_page'     => $customWorkHours->perPage(),
                'to'           => $customWorkHours->lastItem(),
                'total'        => $customWorkHours->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'work_scheme' => 'required|string|max:50',
            'clock_in'    => 'required|date_format:H:i:s',
            'tolerance'   => 'nullable|date_format:H:i:s',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $latestRecord = CustomWorkHour::latest('id')->first();
        $nextNumber = $latestRecord ? $latestRecord->id + 1 : 1;
        $validated['code'] = 'CUS' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $customWorkHour = CustomWorkHour::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour created successfully',
            'data'    => $customWorkHour
        ], 201);
    }

    public function show($id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data'    => $customWorkHour
        ]);
    }

    public function update(Request $request, $id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);

        $validated = $request->validate([
            'start_date'  => 'sometimes|required|date',
            'end_date'    => 'sometimes|required|date|after_or_equal:start_date',
            'work_scheme' => 'sometimes|required|string|max:50',
            'clock_in'    => 'sometimes|required|date_format:H:i:s',
            'tolerance'   => 'nullable|date_format:H:i:s',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        $customWorkHour->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour updated successfully',
            'data'    => $customWorkHour
        ]);
    }

    public function destroy($id)
    {
        $customWorkHour = CustomWorkHour::findOrFail($id);
        $customWorkHour->delete();

        return response()->json([
            'success' => true,
            'message' => 'Custom work hour deleted successfully'
        ]);
    }
}