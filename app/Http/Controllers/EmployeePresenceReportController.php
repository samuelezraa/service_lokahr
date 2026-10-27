<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\AttendanceLogDetail;
use Illuminate\Http\Request;

class EmployeePresenceReportController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::withCount('employees')->get();

        $data = $departments->map(function ($dept, $index) {
            return [
                'id'         => $dept->id,
                'department' => $dept->name,
                'totalPax'   => $dept->employees_count ?? rand(5, 30),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }
}