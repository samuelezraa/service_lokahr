<?php

namespace App\Http\Controllers;

use App\Models\AnnualEvent;
use Illuminate\Http\Request;

class AnnualEventController extends Controller
{
    public function index()
    {
        // Ubah dari event_date menjadi start_date
        $events = AnnualEvent::latest('start_date')->get();
        return response()->json([
            'success' => true,
            'data' => $events
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_national_holiday' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $event = AnnualEvent::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Annual event created successfully',
            'data' => $event
        ], 201);
    }

    public function show($id)
    {
        $event = AnnualEvent::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $event
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = AnnualEvent::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_national_holiday' => 'boolean',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $event->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Annual event updated successfully',
            'data' => $event
        ]);
    }

    public function destroy($id)
    {
        $event = AnnualEvent::findOrFail($id);
        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Annual event deleted successfully'
        ]);
    }
}