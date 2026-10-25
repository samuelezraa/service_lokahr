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

Route::apiResource('periods', PeriodController::class);

Route::apiResource('departments', DepartmentController::class);
Route::apiResource('sub-departments', SubDepartmentController::class);
Route::apiResource('positions', PositionController::class);
Route::apiResource('grades', GradeController::class);
Route::apiResource('work-schedule-schemes', WorkScheduleSchemeController::class);
Route::apiResource('annual-events', AnnualEventController::class);


// routes/api.php
Route::post('/employees/bulk-status', [EmployeeController::class, 'bulkStatus']);
Route::apiResource('employees', EmployeeController::class);
// Posisikan rute export di sini (sebelum apiResource employees)
Route::get('/employees/export', [EmployeeController::class, 'export']);

Route::post('/employees/bulk-status', [EmployeeController::class, 'bulkStatus']);
Route::post('/employees/bulk-reset-password', [EmployeeController::class, 'bulkResetPassword']);
Route::apiResource('employees', EmployeeController::class);

Route::apiResource('employees', EmployeeController::class);
Route::apiResource('custom-work-hours', CustomWorkHourController::class);
Route::apiResource('schedules', ScheduleController::class);
Route::apiResource('schedule-details', ScheduleDetailController::class);
Route::apiResource('schedule-schemes', ScheduleSchemeController::class);
Route::apiResource('attendance-logs', AttendanceLogController::class);
Route::apiResource('attendance-log-details', AttendanceLogDetailController::class);