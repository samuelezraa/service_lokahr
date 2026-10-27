<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use Illuminate\Http\Request;

// Ganti nama class dari AttendanceLogDetailController menjadi AttendanceLogController
class AttendanceLogController extends Controller
{
    public function index()
    {
        $logs = AttendanceLog::with('details')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }
}