<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with([
            'department', 
            'subDepartment', 
            'position', 
            'grade', 
            'workScheduleScheme'
        ])
        ->latest()
        ->get();

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik'                     => 'required|string|unique:employees,nik',
            'full_name'               => 'required|string|max:255',
            'email'                   => 'nullable|email|max:255',
            'phone'                   => 'nullable|string|max:20',
            'department_id'           => 'nullable|exists:departments,id',
            'sub_department_id'       => 'nullable|exists:sub_departments,id',
            'position_id'             => 'nullable|exists:positions,id',
            'grade_id'                => 'nullable|exists:grades,id',
            'work_schedule_scheme_id' => 'nullable|exists:work_schedule_schemes,id',
            'join_date'               => 'nullable|date',
            'birth_date'              => 'nullable|date',
            'is_active'               => 'boolean',
        ]);

        $employee = Employee::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data'    => $employee->load([
                'department', 
                'subDepartment', 
                'position', 
                'grade', 
                'workScheduleScheme'
            ])
        ], 201);
    }

    public function show($id)
    {
        $employee = Employee::with([
            'department', 
            'subDepartment', 
            'position', 
            'grade', 
            'workScheduleScheme'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $employee
        ]);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'nik'                     => 'required|string|unique:employees,nik,' . $id,
            'full_name'               => 'required|string|max:255',
            'email'                   => 'nullable|email|max:255',
            'phone'                   => 'nullable|string|max:20',
            'department_id'           => 'nullable|exists:departments,id',
            'sub_department_id'       => 'nullable|exists:sub_departments,id',
            'position_id'             => 'nullable|exists:positions,id',
            'grade_id'                => 'nullable|exists:grades,id',
            'work_schedule_scheme_id' => 'nullable|exists:work_schedule_schemes,id',
            'join_date'               => 'nullable|date',
            'birth_date'              => 'nullable|date',
            'is_active'               => 'boolean',
        ]);

        $employee->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data'    => $employee->load([
                'department', 
                'subDepartment', 
                'position', 
                'grade', 
                'workScheduleScheme'
            ])
        ]);
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully'
        ]);
    }

   public function export()
    {
        $fileName = 'data-karyawan-' . date('Y-m-d') . '.csv';
        
        $employees = Employee::with([
            'department', 
            'subDepartment', 
            'position', 
            'grade', 
            'workScheduleScheme'
        ])->get();

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = [
            'NIK', 'Nama Lengkap', 'Email', 'Telepon', 
            'Departemen', 'Sub Departemen', 'POS', 'Grade', 
            'Skema Hari Kerja', 'Tanggal Bergabung', 'Tanggal Lahir', 'Status'
        ];

        $callback = function() use($employees, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($employees as $emp) {
                fputcsv($file, [
                    $emp->nik,
                    $emp->full_name,
                    $emp->email,
                    $emp->phone,
                    $emp->department?->name,
                    $emp->subDepartment?->name,
                    $emp->position?->title ?? $emp->position?->name,
                    $emp->grade?->name,
                    $emp->workScheduleScheme?->name,
                    // Diubah agar aman dari error string format
                    $emp->join_date ? date('Y-m-d', strtotime($emp->join_date)) : '',
                    $emp->birth_date ? date('Y-m-d', strtotime($emp->birth_date)) : '',
                    $emp->is_active ? 'Aktif' : 'Non Aktif',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

        // Ambil data karyawan yang dipilih lalu toggle status is_active-nya
        $employees = Employee::whereIn('id', $validated['ids'])->get();

        foreach ($employees as $employee) {
            $employee->update([
                'is_active' => !$employee->is_active
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status karyawan berhasil diperbarui secara massal.'
        ]);
    }
}