<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        $grades = Grade::latest()->get();
        return response()->json([
            'success' => true,
            'data'    => $grades
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'      => 'required|string|unique:grades,code',
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $grade = Grade::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Grade created successfully',
            'data'    => $grade
        ], 201);
    }

    public function show($id)
    {
        $grade = Grade::where('id', $id)->orWhere('code', $id)->firstOrFail();
        return response()->json([
            'success' => true,
            'data'    => $grade
        ]);
    }

    public function update(Request $request, $id)
    {
        $grade = Grade::where('id', $id)->orWhere('code', $id)->firstOrFail();

        $validated = $request->validate([
            'code'      => 'required|string|unique:grades,code,' . $grade->id,
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $grade->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Grade updated successfully',
            'data'    => $grade
        ]);
    }

    public function destroy($id)
    {
        $grade = Grade::where('id', $id)->orWhere('code', $id)->firstOrFail();
        $grade->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grade deleted successfully'
        ]);
    }
}