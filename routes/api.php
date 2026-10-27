<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SubDepartmentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\WorkScheduleSchemeController;
use App\Http\Controllers\AnnualEventController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CustomWorkHourController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScheduleDetailController;
use App\Http\Controllers\ScheduleSchemeController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\AttendanceLogDetailController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\InputScheduleController;
use App\Http\Controllers\PivotReportController;
use App\Http\Controllers\EmployeePresenceReportController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LogHistoryController;


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


// Laporan Routes
Route::get('/reports/pivot', [PivotReportController::class, 'index']);

// Employee Custom Routes (Harus ditaruh sebelum apiResource)
Route::post('/employees/bulk-status', [EmployeeController::class, 'bulkStatus']);
Route::post('/employees/bulk-reset-password', [EmployeeController::class, 'bulkResetPassword']);
Route::get('/employees/export', [EmployeeController::class, 'export']);
Route::get('/employees/{id}/schedule-detail', [CustomWorkHourController::class, 'getEmployeeScheduleDetail']);
Route::apiResource('employees', EmployeeController::class);

// Schedule Routes
Route::apiResource('schedules', ScheduleController::class);
Route::apiResource('schedule-details', ScheduleDetailController::class);
Route::apiResource('schedule-schemes', ScheduleSchemeController::class);

// Attendance Log Routes
Route::apiResource('attendance-logs', AttendanceLogController::class);
Route::apiResource('attendance-log-details', AttendanceLogDetailController::class);

// Jadwal & Input Schedule Routes
Route::get('/schedule/jadwalku', [InputScheduleController::class, 'getMySchedule']);
Route::post('/schedule/jadwalku/export', [InputScheduleController::class, 'exportMySchedule']);

Route::get('/reports/employee-presence', [EmployeePresenceReportController::class, 'index']);

Route::apiResource('employee-attendances', EmployeeAttendanceController::class);



Route::get('/log-histories', [LogHistoryController::class, 'index']);
Route::post('/log-histories', [LogHistoryController::class, 'store']);

Route::prefix('schedule/input-schedule')->group(function () {
    Route::get('/', [InputScheduleController::class, 'index']);
    Route::post('/weekly-hours', [InputScheduleController::class, 'setWeeklyHours']);
    Route::post('/import', [InputScheduleController::class, 'import']);
    Route::get('/export', [InputScheduleController::class, 'export']);
    
    Route::get('/edit/{id}', [InputScheduleController::class, 'showDetail']);
    Route::put('/edit/{id}', [InputScheduleController::class, 'updateDetail']);
});