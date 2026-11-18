<?php

namespace App\Http\Controllers;

use App\Models\AnnualEvent;
use Illuminate\Http\Request;

class AnnualEventController extends Controller
{
    public function index(Request $request)
    {
        $query = AnnualEvent::query();

        // 1. Filter Pencarian (berdasarkan title atau description)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. Filter Status Aktif / Non Aktif
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        // 3. Filter Kategori (Nasional / Perusahaan) -> is_national_holiday
        if ($request->has('category') && $request->category !== '') {
            $isHoliday = filter_var($request->category, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_national_holiday', $isHoliday);
        }

        // 4. Filter Rentang Tanggal (start_date & end_date)
        if ($request->has('start_date') && !empty($request->start_date) && $request->has('end_date') && !empty($request->end_date)) {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $query->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function($subQ) use ($startDate, $endDate) {
                      $subQ->where('start_date', '<=', $startDate)
                           ->where('end_date', '>=', $endDate);
                  });
            });
        } elseif ($request->has('start_date') && !empty($request->start_date)) {
            $query->where('end_date', '>=', $request->start_date);
        } elseif ($request->has('end_date') && !empty($request->end_date)) {
            $query->where('start_date', '<=', $request->end_date);
        }

        // 5. Server-side Pagination
        $perPage = $request->input('per_page', 25);
        $events = $query->latest('start_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $events->items(),
            'meta'    => [
                'current_page' => $events->currentPage(),
                'from'         => $events->firstItem(),
                'last_page'    => $events->lastPage(),
                'per_page'     => $events->perPage(),
                'to'           => $events->lastItem(),
                'total'        => $events->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'is_national_holiday' => 'boolean',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $event = AnnualEvent::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Annual event created successfully',
            'data'    => $event
        ], 201);
    }

    public function show($id)
    {
        $event = AnnualEvent::findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $event
        ]);
    }

    public function update(Request $request, $id)
    {
        $event = AnnualEvent::findOrFail($id);

        $validated = $request->validate([
            'title'               => 'required|string|max:255',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'is_national_holiday' => 'boolean',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
        ]);

        $event->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Annual event updated successfully',
            'data'    => $event
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