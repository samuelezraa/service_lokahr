<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SubDepartmentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\WorkScheduleSchemeController;
use App\Http\Controllers\AnnualEventController;
use App\Http\Controllers\CustomWorkHourController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleDetailController;
use App\Http\Controllers\ScheduleSchemeController;
use App\Http\Controllers\InputScheduleController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\AttendanceLogDetailController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\PivotReportController;
use App\Http\Controllers\EmployeePresenceReportController;
use App\Http\Controllers\LogHistoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. ROUTE PUBLIK (Tidak memerlukan token)
// ==========================================
Route::post('/login', [AuthController::class, 'login']);


// ==========================================
// 2. ROUTE PRIVAT (Wajib membawa Token Bearer)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Cek User yang sedang login
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Dashboard Routes
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

    // Master Data Routes
    Route::apiResource('periods', PeriodController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('sub-departments', SubDepartmentController::class);
    Route::apiResource('positions', PositionController::class);
    Route::apiResource('grades', GradeController::class);
    Route::apiResource('work-schedule-schemes', WorkScheduleSchemeController::class);
    Route::apiResource('annual-events', AnnualEventController::class);
    Route::apiResource('custom-work-hours', CustomWorkHourController::class);

    // Employee Routes
    Route::get('/employees/download-sample', [EmployeeController::class, 'downloadSample']); 
    Route::get('/employees/export', [EmployeeController::class, 'export']);
    Route::post('/employees/import', [EmployeeController::class, 'import']);             
    Route::post('/employees/bulk-status', [EmployeeController::class, 'bulkStatus']);
    Route::post('/employees/bulk-reset-password', [EmployeeController::class, 'bulkResetPassword']);
    Route::get('/employees/{id}/schedule-detail', [CustomWorkHourController::class, 'getEmployeeScheduleDetail']);
    Route::apiResource('employees', EmployeeController::class);

    // Schedule & Jam Kerja Routes (Skema 5-2)
    Route::get('/schedules/export', [ScheduleController::class, 'export']);
    Route::get('/schedules/download-sample', [ScheduleController::class, 'downloadSample']); 
    Route::post('/schedules/import', [ScheduleController::class, 'import']);
    Route::apiResource('schedules', ScheduleController::class);
    Route::apiResource('schedule-details', ScheduleDetailController::class);
    Route::apiResource('schedule-schemes', ScheduleSchemeController::class);

    // Modul Input Jadwal & Jadwalku
    Route::get('/schedule/jadwalku', [InputScheduleController::class, 'getMySchedule']);
    Route::post('/schedule/jadwalku/export', [InputScheduleController::class, 'exportMySchedule']);
    Route::get('/schedule/input-schedule/weekly-hours', [InputScheduleController::class, 'getWeeklyHours']);

    Route::prefix('schedule/input-schedule')->group(function () {
        Route::get('/', [InputScheduleController::class, 'index']);
        Route::post('/weekly-hours', [InputScheduleController::class, 'setWeeklyHours']);
        Route::get('/weekly-hours', [InputScheduleController::class, 'getWeeklyHours']);
        Route::get('/sample', [InputScheduleController::class, 'downloadSample']);
        Route::post('/import', [InputScheduleController::class, 'import']);
        Route::get('/export', [InputScheduleController::class, 'export']);
        Route::get('/edit/{id}', [InputScheduleController::class, 'showDetail']);
        Route::put('/edit/{id}', [InputScheduleController::class, 'updateDetail']);
    });

    // Attendance & Log Routes
    Route::get('/attendance-log-details/export', [AttendanceLogDetailController::class, 'export']);
    Route::apiResource('attendance-log-details', AttendanceLogDetailController::class);
    Route::apiResource('attendance-logs', AttendanceLogController::class);
    Route::apiResource('employee-attendances', EmployeeAttendanceController::class);

    // Reports Routes
    Route::get('/reports/pivot/export', [PivotReportController::class, 'export']);
    Route::get('/reports/pivot', [PivotReportController::class, 'index']);         
    Route::get('/reports/employee-presence/export', [EmployeePresenceReportController::class, 'export']);
    Route::get('/reports/employee-presence', [EmployeePresenceReportController::class, 'index']);

    // System Log History Routes
    Route::get('/log-histories', [LogHistoryController::class, 'index']);
    Route::post('/log-histories', [LogHistoryController::class, 'store']);
});