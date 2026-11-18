<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

use App\Exports\EmployeeExport;
use App\Imports\EmployeeImport;
use App\Exports\EmployeeSampleExport;
use Maatwebsite\Excel\Facades\Excel; 

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with([
            'department', 
            'subDepartment', 
            'position', 
            'grade', 
            'workScheduleScheme'
        ]);

        // 1. Search Global (NIP, Nama, Email, Telepon, Employee ID)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 2. Filter Berdasarkan Relasi Master Data
        if ($request->has('department_id') && !empty($request->department_id)) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->has('sub_department_id') && !empty($request->sub_department_id)) {
            $query->where('sub_department_id', $request->sub_department_id);
        }
        if ($request->has('position_id') && !empty($request->position_id)) {
            $query->where('position_id', $request->position_id);
        }
        if ($request->has('grade_id') && !empty($request->grade_id)) {
            $query->where('grade_id', $request->grade_id);
        }
        if ($request->has('status') && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        $perPage = $request->input('per_page', 25);
        $employees = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $employees->items(),
            'meta'    => [
                'current_page' => $employees->currentPage(),
                'from'         => $employees->firstItem(),
                'last_page'    => $employees->lastPage(),
                'per_page'     => $employees->perPage(),
                'to'           => $employees->lastItem(),
                'total'        => $employees->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'             => 'nullable|string|unique:employees,employee_id',
            'nip'                     => 'required|string|unique:employees,nip',
            'full_name'               => 'required|string|max:255',
            'email'                   => 'nullable|email|max:255',
            'phone'                   => 'nullable|string|max:14',
            'department_id'           => 'nullable|exists:departments,id',
            'sub_department_id'       => 'nullable|exists:sub_departments,id',
            'position_id'             => 'nullable|exists:positions,id',
            'grade_id'                => 'nullable|exists:grades,id',
            'work_schedule_scheme_id' => 'nullable|exists:work_schedule_schemes,id',
            'join_date'               => 'nullable|date',
            'birth_date'              => 'nullable|date',
            'is_active'               => 'boolean',
        ]);

        // Terapkan fungsi konversi nomor telepon
        if (isset($validated['phone'])) {
            $validated['phone'] = Employee::phoneconvert($validated['phone']);
        }

        // Hitung langsung Umur & Lama Kerja (dibulatkan tanpa koma)
        $now = Carbon::now();
        $validated['age_years'] = !empty($validated['birth_date']) 
            ? (int) floor(Carbon::parse($validated['birth_date'])->diffInYears($now)) 
            : null;
        $validated['work_duration_months'] = !empty($validated['join_date']) 
            ? (int) floor(Carbon::parse($validated['join_date'])->diffInMonths($now)) 
            : null;

        // Set default password adalah NIP
        $validated['employee_password'] = Hash::make($validated['nip']);

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
            'employee_id'             => 'nullable|string|unique:employees,employee_id,' . $id,
            'nip'                     => 'required|string|unique:employees,nip,' . $id,
            'full_name'               => 'required|string|max:255',
            'email'                   => 'nullable|email|max:255',
            'phone'                   => 'nullable|string|max:14',
            'department_id'           => 'nullable|exists:departments,id',
            'sub_department_id'       => 'nullable|exists:sub_departments,id',
            'position_id'             => 'nullable|exists:positions,id',
            'grade_id'                => 'nullable|exists:grades,id',
            'work_schedule_scheme_id' => 'nullable|exists:work_schedule_schemes,id',
            'join_date'               => 'nullable|date',
            'birth_date'              => 'nullable|date',
            'is_active'               => 'boolean',
        ]);

        // Terapkan fungsi konversi nomor telepon jika diubah
        if (isset($validated['phone'])) {
            $validated['phone'] = Employee::phoneconvert($validated['phone']);
        }

        // Hitung ulang Umur & Lama Kerja (dibulatkan tanpa koma)
        $now = Carbon::now();
        $validated['age_years'] = !empty($validated['birth_date']) 
            ? (int) floor(Carbon::parse($validated['birth_date'])->diffInYears($now)) 
            : null;
        $validated['work_duration_months'] = !empty($validated['join_date']) 
            ? (int) floor(Carbon::parse($validated['join_date'])->diffInMonths($now)) 
            : null;

        // Jika NIP berubah, update juga default password jika diperlukan
        if (isset($validated['nip']) && $validated['nip'] !== $employee->nip) {
            $validated['employee_password'] = Hash::make($validated['nip']);
        }

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

    public function export(Request $request)
    {
        $idsParam = $request->input('ids');
        $selectedIds = !empty($idsParam) ? explode(',', $idsParam) : [];

        $filters = $request->only([
            'search',
            'department_id',
            'sub_department_id',
            'position_id',
            'grade_id',
            'status',
        ]);

        return Excel::download(
            new EmployeeExport($selectedIds, $filters), 
            'data-karyawan-' . date('Y-m-d') . '.csv', 
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:5120', // Maksimal 5MB
        ]);

        try {
            Excel::import(new EmployeeImport, $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Data karyawan berhasil diimpor dan dikalkulasi ulang!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor file: ' . $e->getMessage()
            ], 422);
        }
    }

    public function bulkStatus(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

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

    public function bulkResetPassword(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'exists:employees,id',
        ]);

        $employees = Employee::whereIn('id', $validated['ids'])->get();

        foreach ($employees as $employee) {
            $employee->update([
                'employee_password' => Hash::make((string) $employee->nip)
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password karyawan berhasil di-reset ke NIP masing-masing.'
        ]);
    }

    public function downloadSample()
    {
        return Excel::download(
            new EmployeeSampleExport, 
            'template-import-karyawan.csv', 
            \Maatwebsite\Excel\Excel::CSV
        );
    }
}