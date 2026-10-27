<?php

namespace App\Http\Controllers;

use App\Models\LogHistory;
use Illuminate\Http\Request;

class LogHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = LogHistory::query();

        // Optional: Filter berdasarkan rentang tanggal jika dikirim dari frontend
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $logs = $query->latest('date')->latest('id')->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:Create,Edit,Delete',
            'menu' => 'required|string',
            'sub_menu' => 'required|string',
            'pic' => 'required|string',
            'date' => 'required|date',
            'description' => 'required|string',
        ]);

        $log = LogHistory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Log history recorded successfully',
            'data' => $log
        ], 201);
    }
}