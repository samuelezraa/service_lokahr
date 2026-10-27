<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class PivotReportController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with([
            'department',
            'subDepartment',
            'position',
            'grade',
            'workScheduleScheme'
        ])
        ->where('is_active', true)
        ->get();

        $data = $employees->map(function ($emp) {
            return [
                'id'             => $emp->id,
                'nik'            => $emp->nik,
                'nama'           => $emp->full_name,
                'departemen'     => $emp->department?->name ?? '-',
                'sub_departemen' => $emp->subDepartment?->name ?? '-',
                'pos'            => $emp->position?->title ?? $emp->position?->name ?? '-',
                'grade'          => $emp->grade?->name ?? '-',
                'skema'          => $emp->workScheduleScheme?->name ?? '5-2',
                'masuk'          => 22,
                'libur'          => 8,
                'ph'             => 1,
                'izin'           => 0,
                'alfa'           => 0,
                'sakit'          => 0,
                'cuti'           => 0,
                'terlambat'      => 0,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data
        ]);
    }
}