<?php

namespace App\Http\Controllers;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::latest()->get();
        return response()->json([
            'success' => true,
            'data' => $positions
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
        // Cari berdasarkan ID database atau berdasarkan kolom 'code' jika $id bertipe string/code
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