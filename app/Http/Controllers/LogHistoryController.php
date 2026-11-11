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

        // Petakan data Spatie agar cocok dengan struktur interface frontend (LogHistory)
        $formattedLogs = $activities->map(function ($activity, $index) {
            // Tentukan tipe aksi berdasarkan event atau log name
            $type = 'Create';
            if ($activity->event === 'updated') $type = 'Edit';
            if ($activity->event === 'deleted') $type = 'Delete';

            // Ambil nama user/PIC jika ada relasi causer, atau gunakan default
            $pic = 'System / Admin';
            if ($activity->causer) {
                $pic = $activity->causer->name ?? $activity->causer->username ?? 'User';
            }

            return [
                'id'          => 'LOG' . str_pad($activity->id, 3, '0', STR_PAD_LEFT),
                'type'        => $type,
                'menu'        => $activity->log_name ?? 'Master Data',
                'subMenu'     => class_basename($activity->subject_type ?? 'System'),
                'pic'         => $pic,
                'date'        => $activity->created_at->format('Y-m-d'),
                'description' => $activity->description ?? 'Aktivitas pada ' . class_basename($activity->subject_type),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $formattedLogs
        ]);
    }
}