<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class LogHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])
            ->where(function ($q) {
                $q->where('description', 'not like', 'Memperbarui data %')
                  ->orWhere(function ($sub) {
                      $sub->whereNotNull('properties')
                          ->where('properties', '!=', '[]')
                          ->where('properties', '!=', '{}');
                  });
            });

        // 1. Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59',
            ]);
        }

        // 2. Filter Search Server-Side
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('log_name', 'like', "%{$search}%")
                  ->orWhere('event', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%")
                  ->orWhere('properties', 'like', "%{$search}%")
                  ->orWhereHas('causer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 3. Server-Side Pagination (Default 25)
        $perPage = (int) $request->input('per_page', 25);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 25;
        }

        $paginated = $query->latest('id')->paginate($perPage);

        // Pemetaan Nama Model -> Sub Menu sesuai Sidebar
        $subMenuMap = [
            'Department'               => 'Departemen',
            'SubDepartment'            => 'Sub Departemen',
            'Position'                 => 'POS',
            'Grade'                    => 'Grade',
            'WorkScheduleScheme'       => 'Skema Hari Kerja',
            'AnnualEvent'              => 'Event Tahunan',
            'Holiday'                  => 'Event Tahunan',
            'Employee'                 => 'Data Karyawan',
            'CustomWorkHour'           => 'Custom Jam Kerja',
            'CustomWorkSchedule'       => 'Custom Jam Kerja',
            'CustomSchedule'           => 'Custom Jam Kerja',
            'ScheduleScheme'           => 'Jam Kerja (Skema 52)',
            'WorkShift'                => 'Jam Kerja (Skema 52)',
            'Shift'                    => 'Jam Kerja (Skema 52)',
            'Schedule'                 => 'Input Jadwal',
            'ScheduleDetail'           => 'Input Jadwal',
            'ScheduleDailyDetail'      => 'Input Jadwal',
            'AttendanceLog'            => 'Absensi',
            'AttendanceLogDetail'      => 'Absensi',
            'Compliment'               => 'Komplimen',
            'Period'                   => 'Periode',
            'EmployeeAttendance'       => 'Absensi Karyawan',
            'EmployeeAttendanceDetail' => 'Absensi Karyawan',
            'User'                     => 'Manajemen User',
        ];

        // Pemetaan Nama Model -> Menu Utama sesuai Sidebar
        $menuMap = [
            'Department'               => 'Master Data',
            'SubDepartment'            => 'Master Data',
            'Position'                 => 'Master Data',
            'Grade'                    => 'Master Data',
            'WorkScheduleScheme'       => 'Master Data',
            'AnnualEvent'              => 'Master Data',
            'Holiday'                  => 'Master Data',
            'Employee'                 => 'Master Data',
            'CustomWorkHour'           => 'Master Data',
            'CustomWorkSchedule'       => 'Master Data',
            'CustomSchedule'           => 'Master Data',
            'ScheduleScheme'           => 'Jadwal',
            'WorkShift'                => 'Jadwal',
            'Shift'                    => 'Jadwal',
            'Schedule'                 => 'Jadwal',
            'ScheduleDetail'           => 'Jadwal',
            'ScheduleDailyDetail'      => 'Jadwal',
            'AttendanceLog'            => 'Laporan',
            'AttendanceLogDetail'      => 'Laporan',
            'Compliment'               => 'Laporan',
            'Period'                   => 'HR Management',
            'EmployeeAttendance'       => 'HR Management',
            'EmployeeAttendanceDetail' => 'HR Management',
            'User'                     => 'System',
        ];

        // Kamus pemetaan kolom foreign key -> Model & kolom nama
        $relationResolvers = [
            'department_id'           => ['model' => \App\Models\Department::class, 'column' => 'name', 'label' => 'Department'],
            'sub_department_id'       => ['model' => \App\Models\SubDepartment::class, 'column' => 'name', 'label' => 'Sub Department'],
            'position_id'             => ['model' => \App\Models\Position::class, 'column' => 'name', 'label' => 'Position'],
            'grade_id'                => ['model' => \App\Models\Grade::class, 'column' => 'name', 'label' => 'Grade'],
            'work_schedule_scheme_id' => ['model' => \App\Models\WorkScheduleScheme::class, 'column' => 'name', 'label' => 'Skema Hari Kerja'],
            'schedule_scheme_id'      => ['model' => \App\Models\ScheduleScheme::class, 'column' => 'name', 'label' => 'Jam Kerja (Skema 52)'],
            'period_id'               => ['model' => \App\Models\Period::class, 'column' => 'name', 'label' => 'Periode'],
            'employee_id'             => ['model' => \App\Models\Employee::class, 'column' => 'full_name', 'label' => 'Karyawan'],
        ];

        $relationCache = [];
        $resolveRelationValue = function (string $field, mixed $value) use ($relationResolvers, &$relationCache) {
            if ($value === null || $value === '' || !isset($relationResolvers[$field])) {
                return $value;
            }

            $config     = $relationResolvers[$field];
            $modelClass = $config['model'];
            $column     = $config['column'];

            if (!isset($relationCache[$modelClass])) {
                if (!class_exists($modelClass)) {
                    $relationCache[$modelClass] = [];
                } elseif ($field === 'grade_id') {
                    // Format khusus Grade: "Nama Grade (ID Grade - Level X)"
                    $relationCache[$modelClass] = $modelClass::get()
                        ->mapWithKeys(function ($g) {
                            $code  = $g->code ?? $g->grade_code ?? null;
                            $lvl   = $g->level ?? null;
                            $extra = array_filter([
                                $code,
                                $lvl !== null ? "Level {$lvl}" : null,
                            ]);
                            $label = $g->name . (!empty($extra) ? ' (' . implode(' - ', $extra) . ')' : '');
                            return [$g->id => $label];
                        })
                        ->toArray();
                } else {
                    $relationCache[$modelClass] = $modelClass::pluck($column, 'id')->toArray();
                }
            }

            return $relationCache[$modelClass][$value] ?? $value;
        };

        // Helper untuk mengambil identitas record subjek (misal: nama grade, nama karyawan, dll.)
        $resolveSubjectLabel = function ($activity, array $changesOld = [], array $changesNew = []): ?string {
            $subject = $activity->subject;

            $name = $subject?->full_name
                ?? $subject?->name
                ?? $subject?->title
                ?? $subject?->periode
                ?? $changesNew['full_name']
                ?? $changesNew['name']
                ?? $changesNew['title']
                ?? $changesOld['full_name']
                ?? $changesOld['name']
                ?? $changesOld['title']
                ?? null;

            $code = $subject?->nip
                ?? $subject?->employee_id
                ?? $subject?->code
                ?? $subject?->grade_code
                ?? $changesNew['nip']
                ?? $changesNew['code']
                ?? $changesOld['nip']
                ?? $changesOld['code']
                ?? null;

            if ($name && $code) {
                return "{$name} ({$code})";
            }

            return $name ?: ($code ?: null);
        };

        $formattedLogs = collect($paginated->items())->map(function ($activity) use (
            $subMenuMap,
            $menuMap,
            $relationResolvers,
            $resolveRelationValue,
            $resolveSubjectLabel
        ) {
            $rawEvent = strtolower(trim((string) ($activity->event ?? '')));
            $rawDesc  = strtolower(trim((string) ($activity->description ?? '')));

            $isUpdate = in_array($rawEvent, ['updated', 'update', 'edit'], true)
                || str_contains($rawDesc, 'updated')
                || str_contains($rawDesc, 'memperbarui')
                || str_contains($rawDesc, 'mengubah');

            $isDelete = in_array($rawEvent, ['deleted', 'delete', 'destroy'], true)
                || str_contains($rawDesc, 'deleted')
                || str_contains($rawDesc, 'menghapus');

            $event = $isUpdate
                ? 'updated'
                : ($isDelete ? 'deleted' : ($rawEvent ?: 'created'));

            $type = match ($event) {
                'updated' => 'Edit',
                'deleted' => 'Delete',
                default   => 'Create',
            };

            $actionLabel = match ($event) {
                'updated' => 'Diperbarui',
                'deleted' => 'Dihapus',
                'created' => 'Dibuat',
                default   => ucfirst($event),
            };

            $pic = 'System / Admin';
            if ($activity->causer) {
                $pic = $activity->causer->name
                    ?? $activity->causer->full_name
                    ?? $activity->causer->username
                    ?? 'User';
            }

            $properties = $activity->properties ? $activity->properties->toArray() : [];
            $changesNew = $properties['attributes'] ?? [];
            $changesOld = $properties['old'] ?? [];

            $ignoredFields = [
                'updated_at',
                'created_at',
                'deleted_at',
                'id',
                'employee_password',
                'password',
            ];

            $beforeData  = [];
            $afterData   = [];
            $changeItems = [];
            $detailsText = [];

            if ($event === 'updated' && !empty($changesNew)) {
                foreach ($changesNew as $field => $newValue) {
                    if (in_array($field, $ignoredFields, true)) {
                        continue;
                    }

                    $rawOldValue = $changesOld[$field] ?? null;
                    $oldValue    = $resolveRelationValue($field, $rawOldValue);
                    $resolvedNew = $resolveRelationValue($field, $newValue);

                    $beforeData[$field] = $oldValue;
                    $afterData[$field]  = $resolvedNew;

                    $label = $relationResolvers[$field]['label'] ?? Str::headline($field);
                    $changeItems[] = [
                        'key'    => $field,
                        'label'  => $label,
                        'before' => $oldValue,
                        'after'  => $resolvedNew,
                    ];

                    $oldStr = is_null($oldValue) || $oldValue === ''
                        ? 'kosong'
                        : (is_array($oldValue) ? json_encode($oldValue) : (string) $oldValue);
                    $newStr = is_null($resolvedNew) || $resolvedNew === ''
                        ? 'kosong'
                        : (is_array($resolvedNew) ? json_encode($resolvedNew) : (string) $resolvedNew);

                    $detailsText[] = "mengubah {$label} dari '{$oldStr}' menjadi '{$newStr}'";
                }
            } elseif ($event === 'created' && !empty($changesNew)) {
                foreach ($changesNew as $field => $newValue) {
                    if (in_array($field, $ignoredFields, true)) {
                        continue;
                    }

                    $resolvedNew       = $resolveRelationValue($field, $newValue);
                    $afterData[$field] = $resolvedNew;

                    $label = $relationResolvers[$field]['label'] ?? Str::headline($field);
                    $changeItems[] = [
                        'key'    => $field,
                        'label'  => $label,
                        'before' => null,
                        'after'  => $resolvedNew,
                    ];
                }
            } elseif ($event === 'deleted' && !empty($changesOld)) {
                foreach ($changesOld as $field => $oldValue) {
                    if (in_array($field, $ignoredFields, true)) {
                        continue;
                    }

                    $oldValue           = $resolveRelationValue($field, $oldValue);
                    $beforeData[$field] = $oldValue;

                    $label = $relationResolvers[$field]['label'] ?? Str::headline($field);
                    $changeItems[] = [
                        'key'    => $field,
                        'label'  => $label,
                        'before' => $oldValue,
                        'after'  => null,
                    ];
                }
            }

            $subjectName  = class_basename($activity->subject_type ?? 'Data');
            $subMenuLabel = $subMenuMap[$subjectName] ?? Str::headline($subjectName);
            $subjectLabel = $resolveSubjectLabel($activity, $changesOld, $changesNew);
            $targetTitle  = $subjectLabel ? "{$subMenuLabel} ({$subjectLabel})" : $subMenuLabel;

            $rawLogName = trim((string) ($activity->log_name ?? ''));
            $menuName   = (!empty($rawLogName) && strtolower($rawLogName) !== 'default')
                ? $rawLogName
                : ($menuMap[$subjectName] ?? 'System / HR');

           $description = $activity->description;

            if ($event === 'updated') {
                $description = "Memperbarui {$targetTitle}";
            } elseif ($event === 'created' && (empty($description) || $description === 'created')) {
                $description = "Menambahkan {$targetTitle} baru";
            } elseif ($event === 'deleted' && (empty($description) || $description === 'deleted')) {
                $description = "Menghapus {$targetTitle}";
            }

            $createdAtWib = $activity->created_at?->copy()->setTimezone('Asia/Jakarta');

            return [
                'id'               => 'LOG' . str_pad((string) $activity->id, 3, '0', STR_PAD_LEFT),
                'raw_id'           => $activity->id,
                'type'             => $type,
                'menu'             => $menuName,
                'subMenu'          => $subMenuLabel,
                'pic'              => $pic,
                'date'             => $createdAtWib?->format('Y-m-d') ?? '-',
                'time'             => $createdAtWib?->format('H:i:s') ?? '-',
                'description'      => $description ?: "Aktivitas pada {$targetTitle}",
                'log_name'         => $activity->log_name,
                'event'            => $event,
                'action_label'     => $actionLabel,
                'properties'       => $properties,
                'subject'          => [
                    'id'   => $activity->subject_id,
                    'type' => $activity->subject_type,
                ],
                'user'             => $activity->causer ? [
                    'id'    => $activity->causer->id,
                    'name'  => $pic,
                    'email' => $activity->causer->email ?? null,
                ] : null,
                'changes'          => [
                    'before' => (object) $beforeData,
                    'after'  => (object) $afterData,
                    'items'  => $changeItems,
                ],
                'created_at'       => $activity->created_at?->toIso8601String(),
                'created_at_human' => $activity->created_at?->diffForHumans(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data'    => $formattedLogs,
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'from'         => $paginated->firstItem(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'to'           => $paginated->lastItem(),
                'total'        => $paginated->total(),
            ],
        ]);
    }
}