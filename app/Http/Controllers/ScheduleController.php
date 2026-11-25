<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\ScheduleDetail; // 👈 Wajib ditambahkan
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Employee;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ScheduleExport;
use App\Imports\ScheduleImport;
use App\Exports\ScheduleSampleExport;

class ScheduleController extends Controller
{
 public function index(Request $request)
    {
        $query = Schedule::with([
            'scheduleDetails',
            'scheduleSchemes.workScheduleScheme',
            'employee',
            'department',
            'subDepartment'
        ])->whereNotNull('employee_id');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('employee_name', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('employee', function($empQ) use ($search) {
                      $empQ->where('employee_id', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->has('department_id') && !empty($request->department_id)) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->has('sub_department_id') && !empty($request->sub_department_id)) {
            $query->where('sub_department_id', $request->sub_department_id);
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $status);
        }

        $perPage = $request->input('per_page', 25);
        $schedules = $query->latest()->paginate($perPage);

        // 🟢 MAPPING: Ambil employee_id asli karyawan (EMP-001) dari relasi 'employee'
        $mappedItems = collect($schedules->items())->map(function ($sch) {
            $schData = $sch->toArray();
            
            // Ambil string ID Karyawan (misal EMP-001), jika tidak ada baru fallback ke nip
            $employeeCode = $sch->employee?->employee_id ?? $sch->nip ?? '-';

            $schData['employee_id_code'] = $employeeCode;
            $schData['display_id']       = $employeeCode;
            return $schData;
        });

        return response()->json([
            'success' => true,
            'data'    => $mappedItems,
            'meta'    => [
                'current_page' => $schedules->currentPage(),
                'from'         => $schedules->firstItem(),
                'last_page'    => $schedules->lastPage(),
                'per_page'     => $schedules->perPage(),
                'to'           => $schedules->lastItem(),
                'total'        => $schedules->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'nullable|string|max:255',
            'employee_id'       => 'nullable|exists:employees,id',
            'department_id'     => 'nullable|exists:departments,id',
            'sub_department_id' => 'nullable|exists:sub_departments,id',
            'is_active'         => 'boolean',
        ]);

        $schedule = Schedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data'    => $schedule
        ], 201);
    }

    public function show($id)
    {
        $schedule = Schedule::with([
            'scheduleDetails',
            'scheduleSchemes.workScheduleScheme',
            'employee',
            'department',
            'subDepartment'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $schedule
        ]);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        // 1. Validasi field induk & payload mingguan 7 hari
        $validated = $request->validate([
            'name'              => 'sometimes|nullable|string|max:255',
            'department_id'     => 'sometimes|nullable|exists:departments,id',
            'sub_department_id' => 'sometimes|nullable|exists:sub_departments,id',
            'is_active'         => 'boolean',
            'monday'            => 'nullable|array',
            'tuesday'           => 'nullable|array',
            'wednesday'         => 'nullable|array',
            'thursday'          => 'nullable|array',
            'friday'            => 'nullable|array',
            'saturday'          => 'nullable|array',
            'sunday'            => 'nullable|array',
        ]);

        // 2. Update data jadwal induk
        $schedule->update($validated);

        // 3. Simpan / Update rincian jam kerja 7 hari di tabel schedule_details
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            if ($request->has($day) && is_array($request->$day)) {
                $dayData = $request->$day;
                
                $status = $dayData['status'] ?? 'Berangkat';
                $checkIn = $dayData['check_in_time'] ?? '00:00:00';
                $checkOut = $dayData['check_out_time'] ?? '00:00:00';
                $isDayOff = ($status === 'Libur');

                ScheduleDetail::updateOrCreate(
                    [
                        'schedule_id' => $schedule->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'check_in_time'  => $checkIn,
                        'check_out_time' => $checkOut,
                        'is_active'      => !$isDayOff,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data'    => $schedule->load([
                'scheduleDetails',
                'scheduleSchemes.workScheduleScheme',
                'employee',
                'department',
                'subDepartment'
            ])
        ]);
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    }

   public function export(Request $request)
    {
        $selectedIds = $request->query('selectedIds');
        $idsArray = $selectedIds ? explode(',', $selectedIds) : [];

        $filters = [
            'search'            => $request->query('search'),
            'department_id'     => $request->query('department_id'),
            'sub_department_id' => $request->query('sub_department_id'),
            'status'            => $request->query('status'),
        ];

        return Excel::download(
            new ScheduleExport($idsArray, $filters),
            'Jam_Kerja_Skema_5-2_' . date('Y-m-d') . '.csv'
        );
    }

    public function downloadSample()
    {
        return Excel::download(
            new ScheduleSampleExport(),
            'Sample_Import_Jam_Kerja_Skema_5-2.csv'
        );
    }
}