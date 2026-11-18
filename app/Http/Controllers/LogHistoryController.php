<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class LogHistoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::query();

        // Filter rentang tanggal jika ada
        if ($request->has('start_date') && $request->has('end_date') && !empty($request->start_date) && !empty($request->end_date)) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $activities = $query->latest()->get();

        $formattedLogs = $activities->map(function ($activity, $index) {
            $type = 'Create';
            if ($activity->event === 'updated') $type = 'Edit';
            if ($activity->event === 'deleted') $type = 'Delete';

            $pic = 'System / Admin';
            if ($activity->causer) {
                $pic = $activity->causer->name ?? $activity->causer->username ?? 'User';
            }

            // 🟢 LOGIKA PENYUSUNAN DESKRIPSI DETAIL (OLD vs NEW)
            $description = $activity->description;
            $properties = $activity->properties ?? [];
            $changesNew = $properties['attributes'] ?? [];
            $changesOld = $properties['old'] ?? [];

            if ($activity->event === 'updated' && !empty($changesNew)) {
                $details = [];
                foreach ($changesNew as $field => $newValue) {
                    // Abaikan timestamp atau field internal jika tidak perlu
                    if (in_array($field, ['updated_at', 'created_at', 'id'])) continue;
                    
                    $oldValue = $changesOld[$field] ?? 'kosong';
                    $newValueStr = is_array($newValue) ? json_encode($newValue) : (string) $newValue;
                    $oldValueStr = is_array($oldValue) ? json_encode($oldValue) : (string) $oldValue;

                    $details[] = "mengubah {$field} dari '{$oldValueStr}' menjadi '{$newValueStr}'";
                }

                if (!empty($details)) {
                    $subjectName = class_basename($activity->subject_type ?? 'Data');
                    $description = "Memperbarui {$subjectName}: " . implode(', ', $details);
                }
            } elseif ($activity->event === 'created') {
                $subjectName = class_basename($activity->subject_type ?? 'Data');
                $description = "Menambahkan {$subjectName} baru";
            } elseif ($activity->event === 'deleted') {
                $subjectName = class_basename($activity->subject_type ?? 'Data');
                $description = "Menghapus {$subjectName}";
            }

            return [
                'id'          => 'LOG' . str_pad($activity->id, 3, '0', STR_PAD_LEFT),
                'type'        => $type,
                'menu'        => $activity->log_name ?? 'Master Data',
                'subMenu'     => class_basename($activity->subject_type ?? 'System'),
                'pic'         => $pic,
                'date' => $activity->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d'),
                'description' => $description ?: ('Aktivitas pada ' . class_basename($activity->subject_type)),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedLogs
        ]);
    }
}